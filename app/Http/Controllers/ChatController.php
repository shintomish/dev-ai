<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Attachment;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class ChatController extends Controller
{
    /**
     * チャット画面を表示
     */
    public function index(Request $request)
    {
        $conversationId = $request->query('conversation');
        $conversation = null;
        $messages = [];

        if ($conversationId) {
            $conversation = Conversation::with(['messages.attachments', 'tags'])->find($conversationId);
            if ($conversation) {
                $messages = $conversation->messages;
            }
        }

        $favoriteConversations = Conversation::where('is_favorite', true)
            ->with('tags')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $recentConversations = Conversation::where('is_favorite', false)
            ->with('tags')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return view('chat', [
            'conversation' => $conversation,
            'messages' => $messages,
            'favoriteConversations' => $favoriteConversations,
            'recentConversations' => $recentConversations,
            'allTags' => \App\Models\Tag::orderBy('name')->get(),
        ]);
    }

    /**
     * Claude APIにメッセージを送信（通常版・ファイル対応）
     */
    public function send(Request $request)
    {
        // 1. バリデーション
        $request->validate([
            'message' => 'required|string|max:10000',
            'mode' => 'required|in:dev,study',
            'conversation_id' => 'nullable|exists:conversations,id',
            'files.*' => 'nullable|file|max:5120', // 5MB まで
        ]);

        // 2. 変数取得
        $messageText = $request->input('message');
        $mode = $request->input('mode');
        $conversationId = $request->input('conversation_id');

        // 3. 会話取得または作成
        if ($conversationId) {
            $conversation = Conversation::findOrFail($conversationId);
        } else {
            $conversation = Conversation::create(['mode' => $mode]);
        }

        // 4. ユーザーメッセージ保存
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $messageText,
        ]);

        // 5. ファイルアップロード処理
        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $filename = time() . '_' . $originalName;
                $path = $file->storeAs('attachments', $filename, 'public');

                // テキストファイルの場合は内容を読み込む
                $content = null;
                $mimeType = $file->getMimeType();
                if (str_starts_with($mimeType, 'text/') ||
                    in_array($file->getClientOriginalExtension(), ['log', 'txt', 'php', 'js', 'py', 'java', 'cpp', 'h', 'md', 'json', 'xml', 'yaml', 'yml'])) {
                    $content = file_get_contents($file->getRealPath());
                }

                // 添付ファイルを保存
                $attachment = Attachment::create([
                    'message_id' => $userMessage->id,
                    'filename' => $path,
                    'original_filename' => $originalName,
                    'mime_type' => $mimeType,
                    'size' => $file->getSize(),
                    'content' => $content,
                ]);

                $uploadedFiles[] = [
                    'name' => $originalName,
                    'size' => $attachment->human_readable_size,
                    'content' => $content,
                ];
            }
        }

        // 6. メッセージにファイル内容を追加
        $fullMessage = $messageText;
        if (!empty($uploadedFiles)) {
            $fullMessage .= "\n\n【添付ファイル】\n";
            foreach ($uploadedFiles as $file) {
                $fullMessage .= "\nファイル名: {$file['name']} (サイズ: {$file['size']})\n";
                if ($file['content']) {
                    $fullMessage .= "内容:\n```\n" . substr($file['content'], 0, 10000) . "\n```\n";
                }
            }
        }

        // 7. タイトル自動生成
        $conversation->generateTitle();

        // 8. システムプロンプト
        $systemPrompt = $this->getSystemPrompt($mode);

        // 9. Claude API呼び出し
        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model', 'claude-sonnet-4-20250514'),
                'max_tokens' => 4096,
                'system' => $systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $fullMessage,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['content'][0]['text'] ?? 'レスポンスが空です';

                // アシスタントメッセージを保存
                $assistantMessage = Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $content,
                    'metadata' => [
                        'usage' => $data['usage'] ?? null,
                        'model' => $data['model'] ?? null,
                    ],
                ]);

                $conversation->touch();

                return response()->json([
                    'success' => true,
                    'response' => $content,
                    'conversation_id' => $conversation->id,
                    'message_id' => $assistantMessage->id,
                    'usage' => $data['usage'] ?? null,
                ]);
            }

            Log::error('Claude API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'API呼び出しに失敗しました: ' . $response->body(),
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('Claude API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'エラーが発生しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ストリーミングでメッセージを送信
     * ストリーミングレスポンス（ファイルアップロード非対応）
     */
    public function sendStream(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:10000',
            'mode' => 'required|in:dev,study',
            'conversation_id' => 'nullable|exists:conversations,id',
        ]);

        try {
            // 会話の取得または作成
            if ($validated['conversation_id']) {
                $conversation = Conversation::findOrFail($validated['conversation_id']);
            } else {
                $conversation = Conversation::create([
                    'title' => Str::limit($validated['message'], 50),
                    'mode' => $validated['mode'],
                ]);
            }

            // ユーザーメッセージを保存
            $userMessage = $conversation->messages()->create([
                'role' => 'user',
                'content' => $validated['message'],
            ]);

            // 会話履歴を取得
            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($msg) {
                    return [
                        'role' => $msg->role,
                        'content' => $msg->content,
                    ];
                })
                ->toArray();

            // システムプロンプト
            $systemPrompt = $validated['mode'] === 'dev'
                ? "あなたは開発支援AIアシスタントです。コードレビュー、バグ修正、実装アドバイスを提供してください。"
                : "あなたは学習支援AIアシスタントです。分かりやすく、丁寧に説明してください。";

            // ストリーミングレスポンス
            return response()->stream(function () use ($messages, $systemPrompt, $conversation) {
                $client = new \GuzzleHttp\Client();

                try {
                    $response = $client->post('https://api.anthropic.com/v1/messages', [
                        'headers' => [
                            'x-api-key' => config('services.anthropic.api_key'),
                            'anthropic-version' => '2023-06-01',
                            'content-type' => 'application/json',
                        ],
                        'json' => [
                            'model' => config('services.anthropic.model'),
                            'max_tokens' => 4096,
                            'system' => $systemPrompt,
                            'messages' => $messages,
                            'stream' => true,
                        ],
                        'stream' => true,
                    ]);

                    $body = $response->getBody();
                    $fullResponse = '';

                    while (!$body->eof()) {
                        $chunk = $body->read(1024);
                        $lines = explode("\n", $chunk);

                        foreach ($lines as $line) {
                            $line = trim($line);

                            if (empty($line) || !str_starts_with($line, 'data: ')) {
                                continue;
                            }

                            $data = substr($line, 6);

                            if ($data === '[DONE]') {
                                break;
                            }

                            try {
                                $json = json_decode($data, true);

                                if (isset($json['type'])) {
                                    if ($json['type'] === 'content_block_delta') {
                                        if (isset($json['delta']['text'])) {
                                            $text = $json['delta']['text'];
                                            $fullResponse .= $text;

                                            echo "data: " . json_encode([
                                                'text' => $text,
                                                'done' => false,
                                            ]) . "\n\n";

                                            if (ob_get_level() > 0) {
                                                ob_flush();
                                            }
                                            flush();
                                        }
                                    } elseif ($json['type'] === 'message_stop') {
                                        break;
                                    }
                                }
                            } catch (\Exception $e) {
                                \Log::error('Stream parse error: ' . $e->getMessage());
                            }
                        }
                    }

                    // アシスタントメッセージを保存
                    $conversation->messages()->create([
                        'role' => 'assistant',
                        'content' => $fullResponse,
                    ]);

                    // 完了通知
                    echo "data: " . json_encode([
                        'done' => true,
                        'conversation_id' => $conversation->id,
                    ]) . "\n\n";

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();

                } catch (\Exception $e) {
                    \Log::error('Streaming error: ' . $e->getMessage());
                    echo "data: " . json_encode([
                        'error' => $e->getMessage(),
                        'done' => true,
                    ]) . "\n\n";

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]);

        } catch (\Exception $e) {
            \Log::error('Stream setup error: ' . $e->getMessage());

            return response()->stream(function () use ($e) {
                echo "data: " . json_encode([
                    'error' => $e->getMessage(),
                    'done' => true,
                ]) . "\n\n";
                flush();
            }, 500, [
                'Content-Type' => 'text/event-stream',
            ]);
        }
    }

    /**
     * 会話を削除
     */
    public function destroy(Conversation $conversation)
    {
        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => '会話を削除しました',
        ]);
    }

    /**
     * お気に入りのトグル
     */
    public function toggleFavorite(Conversation $conversation)
    {
        $conversation->is_favorite = !$conversation->is_favorite;
        $conversation->save();

        return response()->json([
            'success' => true,
            'is_favorite' => $conversation->is_favorite,
        ]);
    }

    /**
     * 新しい会話を開始
     */
    public function newConversation()
    {
        return redirect()->route('chat.index');
    }

    /**
     * 会話をエクスポート
     */
    public function export(Conversation $conversation, Request $request)
    {
        $format = $request->query('format', 'markdown');
        $conversation->load('messages');

        switch ($format) {
            case 'json':
                return $this->exportJson($conversation);
            case 'txt':
                return $this->exportText($conversation);
            case 'markdown':
            default:
                return $this->exportMarkdown($conversation);
        }
    }

    private function exportMarkdown(Conversation $conversation): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = sprintf(
            'conversation_%d_%s.md',
            $conversation->id,
            now()->format('Ymd_His')
        );

        return response()->streamDownload(function() use ($conversation) {
            echo "# {$conversation->title}\n\n";
            echo "**作成日時**: " . $conversation->created_at->format('Y-m-d H:i:s') . "\n";
            echo "**モード**: " . ($conversation->mode === 'dev' ? '開発支援' : '学習支援') . "\n\n";
            echo "---\n\n";

            foreach ($conversation->messages as $message) {
                if ($message->role === 'user') {
                    echo "## 👤 ユーザー\n\n";
                } else {
                    echo "## 🤖 AI\n\n";
                }
                echo $message->content . "\n\n";
                echo "---\n\n";
            }
        }, $filename, [
            'Content-Type' => 'text/markdown',
        ]);
    }

    private function exportJson(Conversation $conversation): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = sprintf(
            'conversation_%d_%s.json',
            $conversation->id,
            now()->format('Ymd_His')
        );

        $data = [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'mode' => $conversation->mode,
            'created_at' => $conversation->created_at->toIso8601String(),
            'updated_at' => $conversation->updated_at->toIso8601String(),
            'messages' => $conversation->messages->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
                'created_at' => $msg->created_at->toIso8601String(),
            ])->toArray(),
        ];

        return response()->streamDownload(function() use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    private function exportText(Conversation $conversation): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = sprintf(
            'conversation_%d_%s.txt',
            $conversation->id,
            now()->format('Ymd_His')
        );

        return response()->streamDownload(function() use ($conversation) {
            echo "{$conversation->title}\n";
            echo str_repeat('=', mb_strlen($conversation->title)) . "\n\n";
            echo "作成日時: " . $conversation->created_at->format('Y-m-d H:i:s') . "\n";
            echo "モード: " . ($conversation->mode === 'dev' ? '開発支援' : '学習支援') . "\n\n";
            echo str_repeat('-', 80) . "\n\n";

            foreach ($conversation->messages as $message) {
                if ($message->role === 'user') {
                    echo "[ユーザー]\n";
                } else {
                    echo "[AI]\n";
                }
                echo $message->content . "\n\n";
                echo str_repeat('-', 80) . "\n\n";
            }
        }, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * タグを追加
     */
    public function attachTag(Conversation $conversation, Request $request)
    {
        $request->validate([
            'tag_id' => 'required|exists:tags,id',
        ]);

        $conversation->tags()->attach($request->tag_id);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * タグを削除
     */
    public function detachTag(Conversation $conversation, Request $request)
    {
        $request->validate([
            'tag_id' => 'required|exists:tags,id',
        ]);

        $conversation->tags()->detach($request->tag_id);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * モード別のシステムプロンプトを返す
     */
    private function getSystemPrompt(string $mode): string
    {
        return match($mode) {
            'dev' => <<<'PROMPT'
あなたは経験豊富な技術サポートAIです。以下の技術スタックに特化して支援します：

【専門分野】
- Laravel (PHP) - ルーティング、Eloquent、Blade、バリデーション、認証
- Linux サーバー管理 - AlmaLinux、VPS設定、SSH、パーミッション
- Git / GitLab - バージョン管理、CI/CD、マージ戦略
- Excel VBA - マクロ開発、自動化、デバッグ
- Apache / Nginx - Web サーバー設定

【対応スタイル】
- エラーログを貼られたら、原因特定 → 具体的な解決手順を提示
- コード相談には、動くサンプルコード + 説明を返す
- セキュリティリスクがある場合は必ず指摘
- 複数の解決策がある場合は、推奨度順に提示
- コマンド実行例は必ずコピペ可能な形式で記載

【回答形式】
- 結論を先に（3行以内）
- 必要に応じて詳細説明
- コードブロックは言語指定（```php、```bash等）
- 長い説明は避け、実践的な内容に絞る

日本語で、技術者向けの簡潔な口調で回答してください。
PROMPT,

            'study' => <<<'PROMPT'
あなたは初心者に優しいプログラミング講師AIです。

【教え方】
- 専門用語は必ず平易な言葉で説明
- 例え話を使って直感的に理解させる
- 「なぜそうなるのか」を丁寧に説明
- 段階的に理解を深めるアプローチ
- 質問しやすい雰囲気を作る

【対応範囲】
- プログラミング基礎（変数、条件分岐、ループ）
- Web開発の仕組み（HTML/CSS/JavaScript/PHP）
- Laravelフレームワーク入門
- Git の基本操作
- コマンドライン操作の基礎

【回答スタイル】
- 励ましの言葉を忘れずに
- 失敗は学びのチャンスと伝える
- 専門用語には（かっこ書きで補足説明）
- サンプルコードには詳細なコメントを付ける
- 「次のステップ」を提示して学習を促進

日本語で、優しく丁寧な口調で回答してください。
PROMPT,
        };
    }
}
