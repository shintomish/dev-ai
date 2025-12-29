<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            $conversation = Conversation::with('messages')->find($conversationId);
            if ($conversation) {
                $messages = $conversation->messages;
            }
        }

        // お気に入りと最近の会話を分けて取得
        $favoriteConversations = Conversation::where('is_favorite', true)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $recentConversations = Conversation::where('is_favorite', false)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return view('chat', [
            'conversation' => $conversation,
            'messages' => $messages,
            'favoriteConversations' => $favoriteConversations,
            'recentConversations' => $recentConversations,
        ]);
    }
    /**
     * Claude APIにメッセージを送信
     */
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'mode' => 'required|in:dev,study',
            'conversation_id' => 'nullable|exists:conversations,id',
        ]);

        $messageText = $request->input('message');
        $mode = $request->input('mode');
        $conversationId = $request->input('conversation_id');

        // 会話の取得または作成
        if ($conversationId) {
            $conversation = Conversation::findOrFail($conversationId);
        } else {
            $conversation = Conversation::create(['mode' => $mode]);
        }

        // ユーザーメッセージを保存
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $messageText,
        ]);

        // タイトル自動生成（最初のメッセージの場合）
        $conversation->generateTitle();

        // 会話履歴を取得（コンテキスト用）
        $conversationHistory = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();

        // システムプロンプト
        $systemPrompt = $this->getSystemPrompt($mode);

        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model', 'claude-sonnet-4-20250514'),
                'max_tokens' => 4096,
                'system' => $systemPrompt,
                'messages' => $conversationHistory,
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

                // updated_atを更新
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