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
        // デバッグ用: 認証確認
        \Log::info('ChatController index START');
        \Log::info('Auth check: ' . (auth()->check() ? 'YES' : 'NO'));
        \Log::info('User ID: ' . auth()->id());
        \Log::info('User: ' . (auth()->user() ? auth()->user()->name : 'NULL'));

        $conversationId = $request->query('conversation');

        // 自分の会話のみ取得
        $conversation = $conversationId
            ? Conversation::where('user_id', auth()->id())
                          ->findOrFail($conversationId)
            : null;

        $messages = $conversation
            ? $conversation->messages()->orderBy('created_at', 'asc')->get()
            : collect();

        // 自分の会話のみ取得
        $recentConversations = Conversation::where('user_id', auth()->id())
            ->where('is_favorite', false)
            ->latest()
            ->limit(10)
            ->get();

        $favoriteConversations = Conversation::where('user_id', auth()->id())
            ->where('is_favorite', true)
            ->latest()
            ->get();

        $allTags = Tag::all();

        // 今月の統計を取得
        $monthlyStats = $this->getMonthlyStats();

        Log::info('ChatController index END');

        return view('chat', compact(
            'conversation',
            'messages',
            'recentConversations',
            'favoriteConversations',
            'allTags',
            'monthlyStats'
        ));
    }

    /**
     * Claude APIにメッセージを送信（通常版・ファイル対応）
     */
    public function send(Request $request)
    {
        // デバッグ用: 認証確認
        \Log::info('ChatController send START');
        \Log::info('Auth check: ' . (auth()->check() ? 'YES' : 'NO'));
        \Log::info('User ID: ' . auth()->id());
        \Log::info('User: ' . (auth()->user() ? auth()->user()->name : 'NULL'));

        // 認証チェック（念のため）
        if (!auth()->check()) {
            \Log::error('User not authenticated in send()');
            return response()->json(['error' => '認証が必要です'], 401);
        }

        // 1. バリデーション
        $request->validate([
            'message' => 'required|string|max:10000',
            'conversation_id' => 'nullable|integer|exists:conversations,id',
            'mode' => 'required|in:dev,study,sales',  // sales を追加
            'files.*' => 'nullable|file|max:10240',
        ]);

        // 2. 変数取得
        $messageText = $request->input('message');
        $conversationId = $request->input('conversation_id');
        $mode = $request->input('mode', 'dev');

        \Log::info('send() - Input - User ID: ' . auth()->id() . ', Conversation ID: ' . ($conversationId ?? 'null') . ', Mode: ' . $mode);

        // 3. 会話取得または作成
        if ($conversationId) {
            \Log::info('既存の会話を取得: ' . $conversationId);

            // 自分の会話のみ取得
            $conversation = Conversation::where('user_id', auth()->id())
                                       ->findOrFail($conversationId);

            \Log::info('会話取得成功 - ID: ' . $conversation->id . ', User ID: ' . $conversation->user_id);

        } else {
            \Log::info('新しい会話を作成中 - User ID: ' . auth()->id());

            // 新しい会話を作成（user_idを明示的に設定）
            $userId = auth()->id();
            \Log::info('取得したUser ID: ' . $userId);

            if (!$userId) {
                \Log::error('User ID is null!');
                return response()->json(['error' => 'ユーザーIDを取得できませんでした'], 500);
            }

            // 新しい会話を作成（user_idを設定）
            $conversation = Conversation::create([
                // 'user_id' => auth()->id(),
                'user_id' => $userId,
                'title' => '新しい会話',
                'mode' => $mode,
            ]);

            \Log::info('新しい会話を作成 - ID: ' . $conversation->id . ', User ID: ' . $conversation->user_id);
        }

        // 4. ユーザーメッセージ保存
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $messageText,
        ]);

        \Log::info('ユーザーメッセージ保存 - ID: ' . $userMessage->id);

        // 5. ファイルアップロード処理
        $uploadedFiles = [];
        $imageContents = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $filename = time() . '_' . uniqid() . '_' . $originalName;
                $path = $file->storeAs('attachments', $filename, 'public');
                $mimeType = $file->getMimeType();
                $isImage = str_starts_with($mimeType, 'image/');

                // テキストファイルの場合は内容を読み込む
                $content = null;
                if (!$isImage && (
                    str_starts_with($mimeType, 'text/') ||
                    in_array($file->getClientOriginalExtension(), ['log', 'txt', 'php', 'js', 'py', 'java', 'cpp', 'h', 'md', 'json', 'xml', 'yaml', 'yml'])
                )) {
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
                    'is_image' => $isImage,
                ]);

                // 画像の場合はBase64エンコード
                if ($isImage) {
                    $imageData = base64_encode(file_get_contents($file->getRealPath()));
                    $imageContents[] = [
                        'type' => 'image',
                        'source' => [
                            'type' => 'base64',
                            'media_type' => $mimeType,
                            'data' => $imageData,
                        ],
                    ];
                }

                $uploadedFiles[] = [
                    'name' => $originalName,
                    'size' => $attachment->human_readable_size,
                    'content' => $content,
                    'is_image' => $isImage,
                ];
            }
        }

        // 6. メッセージにファイル内容を追加
        $fullMessage = $messageText;
        if (!empty($uploadedFiles)) {
            $fullMessage .= "\n\n【添付ファイル】\n";
            foreach ($uploadedFiles as $file) {
                if (!$file['is_image']) {
                    $fullMessage .= "\nファイル名: {$file['name']} (サイズ: {$file['size']})\n";
                    if ($file['content']) {
                        $fullMessage .= "内容:\n```\n" . substr($file['content'], 0, 10000) . "\n```\n";
                    }
                } else {
                    $fullMessage .= "\n画像: {$file['name']} (サイズ: {$file['size']})\n";
                }
            }
        }

        // 7. タイトル自動生成
        $conversation->generateTitle();

        // 8. システムプロンプト
        $systemPrompt = $this->getSystemPrompt($mode);

        // 9. Claude API呼び出し
        try {
            // メッセージコンテンツを構築
            $messageContent = [];

            // 画像がある場合は先に追加
            if (!empty($imageContents)) {
                $messageContent = array_merge($messageContent, $imageContents);
            }

            // テキストメッセージを追加
            $messageContent[] = [
                'type' => 'text',
                'text' => $fullMessage,
            ];

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
                        'content' => $messageContent,
                    ],
                ],
            ]);

            // レスポンス処理
            if ($response->successful()) {
                $data = $response->json();
                $content = $data['content'][0]['text'] ?? 'レスポンスが空です';

                // 使用トークン情報を取得
                $usage = $data['usage'] ?? null;
                $inputTokens = $usage['input_tokens'] ?? null;
                $outputTokens = $usage['output_tokens'] ?? null;
                $totalTokens = $inputTokens && $outputTokens ? $inputTokens + $outputTokens : null;

                // アシスタントメッセージを保存（トークン情報を含む）
                $assistantMessage = Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $content,
                    'metadata' => [
                        'usage' => $usage,
                        'model' => $data['model'] ?? null,
                    ],
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'total_tokens' => $totalTokens,
                ]);

                $conversation->touch();

                Log::info('ChatController send END');

                return response()->json([
                    'success' => true,
                    'response' => $content,
                    'conversation_id' => $conversation->id,
                    'message_id' => $assistantMessage->id,
                    'usage' => $usage,
                    'tokens' => [
                        'input' => $inputTokens,
                        'output' => $outputTokens,
                        'total' => $totalTokens,
                    ],
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
            'mode' => 'required|in:dev,study,sales',  // sales を追加
            'conversation_id' => 'nullable|exists:conversations,id',
        ]);

        try {
            $messageText = $request->input('message');
            $conversationId = $request->input('conversation_id');
            $mode = $request->input('mode', 'dev');

            \Log::info('sendStream() - User ID: ' . auth()->id() . ', Conversation ID: ' . $conversationId);

            // 2. 会話の取得または作成
            if ($conversationId) {
                // 自分の会話のみ取得
                $conversation = Conversation::where('user_id', auth()->id())
                                        ->findOrFail($conversationId);
            } else {
                // 新しい会話を作成（user_idを設定）
                $conversation = Conversation::create([
                    'user_id' => auth()->id(),
                    'title' => '新しい会話',
                    'mode' => $mode,
                ]);

                \Log::info('新しい会話を作成(Stream) - ID: ' . $conversation->id . ', User ID: ' . $conversation->user_id);
            }

            // 3. ユーザーメッセージを保存
            $userMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $messageText,
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
        // 自分の会話かチェック
        if ($conversation->user_id !== auth()->id()) {
            abort(403, 'この会話にアクセスする権限がありません');
        }

        $conversation->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * お気に入りのトグル
     */
    public function toggleFavorite(Conversation $conversation)
    {
        // 自分の会話かチェック
        if ($conversation->user_id !== auth()->id()) {
            abort(403, 'この会話にアクセスする権限がありません');
        }

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
    public function new()
    {
        // デバッグ用
        \Log::info('new() - User ID: ' . auth()->id() . ', User: ' . auth()->user()->name);

        // 自分の会話のみ取得
        $recentConversations = Conversation::where('user_id', auth()->id())
            ->where('is_favorite', false)
            ->latest()
            ->limit(10)
            ->get();

        // デバッグ用
        \Log::info('Recent conversations count: ' . $recentConversations->count());

        $favoriteConversations = Conversation::where('user_id', auth()->id())
            ->where('is_favorite', true)
            ->latest()
            ->get();

        // デバッグ用
        \Log::info('Favorite conversations count: ' . $favoriteConversations->count());

        $allTags = Tag::all();

        // 今月の統計を取得
        $monthlyStats = $this->getMonthlyStats();

        return view('chat', [
            'conversation' => null,
            'messages' => collect(),
            'recentConversations' => $recentConversations,
            'favoriteConversations' => $favoriteConversations,
            'allTags' => $allTags,
            'monthlyStats' => $monthlyStats,  // 追加
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
        // 自分の会話かチェック
        if ($conversation->user_id !== auth()->id()) {
            abort(403, 'この会話にアクセスする権限がありません');
        }

        $format = $request->query('format', 'markdown');
        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();
        
        $timestamp = now()->format('Ymd_His');
        $filename = "conversation_{$conversation->id}_{$timestamp}";

        switch ($format) {
            case 'json':
                $data = [
                    'conversation' => [
                        'id' => $conversation->id,
                        'title' => $conversation->title,
                        'mode' => $conversation->mode,
                        'created_at' => $conversation->created_at,
                    ],
                    'messages' => $messages->map(function ($message) {
                        return [
                            'role' => $message->role,
                            'content' => $message->content,
                            'created_at' => $message->created_at,
                            'tokens' => [
                                'input' => $message->input_tokens,
                                'output' => $message->output_tokens,
                                'total' => $message->total_tokens,
                            ],
                        ];
                    }),
                ];
                
                // JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT で日本語を読みやすく 
                $jsonContent = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                
                return response($jsonContent)
                    ->header('Content-Type', 'application/json; charset=UTF-8')
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}.json\"");

            case 'text':
                $content = "会話: {$conversation->title}\n";
                $content .= "作成日時: {$conversation->created_at}\n";
                $content .= "モード: {$conversation->mode}\n";
                $content .= str_repeat('=', 50) . "\n\n";
                
                foreach ($messages as $message) {
                    $role = $message->role === 'user' ? 'ユーザー' : 'アシスタント';
                    $content .= "[{$role}] {$message->created_at}\n";
                    $content .= "{$message->content}\n\n";
                    $content .= str_repeat('-', 50) . "\n\n";
                }
                
                return response($content)
                    ->header('Content-Type', 'text/plain; charset=UTF-8')
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}.txt\"");

            case 'markdown':
            default:
                $content = "# {$conversation->title}\n\n";
                $content .= "- **作成日時**: {$conversation->created_at}\n";
                $content .= "- **モード**: {$conversation->mode}\n\n";
                $content .= "---\n\n";
                
                foreach ($messages as $message) {
                    $role = $message->role === 'user' ? '👤 ユーザー' : '🤖 アシスタント';
                    $content .= "## {$role}\n\n";
                    $content .= "*{$message->created_at}*\n\n";
                    $content .= "{$message->content}\n\n";
                    
                    if ($message->total_tokens) {
                        $content .= "> 📊 トークン: {$message->total_tokens} (入力: {$message->input_tokens}, 出力: {$message->output_tokens})\n\n";
                    }
                    
                    $content .= "---\n\n";
                }
                
                return response($content)
                    ->header('Content-Type', 'text/markdown; charset=UTF-8')
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}.md\"");
        }
    }

    /**
     * タグを更新（一括同期）
     */
    public function updateTags(Request $request, Conversation $conversation)
    {
        // 自分の会話かチェック
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        // バリデーション
        $request->validate([
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'new_tag' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        // タグが空の場合はすべて削除
        if (empty($request->tags) || !$request->has('tags')) {
            $conversation->tags()->detach();
            
            return response()->json([
                'success' => true,
                'tags' => [],
                'message' => 'すべてのタグを削除しました',
            ]);
        }

        $tagIds = [];
        $newTagName = $request->input('new_tag');
        $color = $request->input('color', $this->generateRandomColor());
        
        foreach ($request->tags as $tagName) {
            // ユーザー専用のタグを検索
            $tag = \App\Models\Tag::where('user_id', auth()->id())
                ->where('name', $tagName)
                ->first();
            
            if ($tag) {
                // 既存のタグ
                // 新しく追加されたタグの場合は色を更新
                if ($tagName === $newTagName && $color) {
                    $tag->color = $color;
                    $tag->save();
                }
                $tagIds[] = $tag->id;
            } else {
                // 新しいタグ（指定された色を使用）
                $isNewTag = ($tagName === $newTagName);
                $tagColor = $isNewTag ? $color : $this->generateRandomColor();
                
                $tag = \App\Models\Tag::create([
                    'user_id' => auth()->id(),
                    'name' => $tagName,
                    'color' => $tagColor,
                ]);
                
                $tagIds[] = $tag->id;
            }
        }

        // 会話にタグを紐付け
        $conversation->tags()->sync($tagIds);

        return response()->json([
            'success' => true,
            'tags' => $conversation->fresh()->tags,
            'message' => 'タグを更新しました',
        ]);
    }

    private function generateRandomColor()
    {
        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6'];
        return $colors[array_rand($colors)];
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
     * 会話を検索
     */
    public function search(Request $request)
    {
        \Log::info('Search START');
        \Log::info('User ID: ' . auth()->id());

        $query = $request->input('q');

        \Log::info('Search query: ' . $query);

        if (empty($query)) {
            \Log::info('Query is empty, returning empty array');
            return response()->json([]);
        }

        // 自分の会話のみ検索
        $conversations = Conversation::where('user_id', auth()->id())
            ->where('title', 'like', '%' . $query . '%')
            ->with('tags')
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($conversation) use ($query) {
                return [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'is_favorite' => $conversation->is_favorite,
                    'updated_at' => $conversation->updated_at->diffForHumans(),
                    'tags' => $conversation->tags->pluck('name'),
                    'highlight' => true,
                ];
            });

        \Log::info('Search results count: ' . $conversations->count());

        return response()->json($conversations);
    }

    /**
     * 今月のトークン使用統計
     */
    public function getMonthlyStats()
    {
        try {
            $startOfMonth = now()->startOfMonth();

            // 自分の会話のメッセージのみ集計
            $conversationIds = Conversation::where('user_id', auth()->id())
                ->pluck('id');

            // 会話がない場合は0を返す
            if ($conversationIds->isEmpty()) {
                return [
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'total_tokens' => 0,
                    'message_count' => 0,
                    'cost_usd' => 0,
                    'cost_jpy' => 0,
                ];
            }

            $stats = Message::whereIn('conversation_id', $conversationIds)
                ->where('created_at', '>=', $startOfMonth)
                ->whereNotNull('total_tokens')
                ->selectRaw('
                    SUM(input_tokens) as total_input,
                    SUM(output_tokens) as total_output,
                    SUM(total_tokens) as total_tokens,
                    COUNT(*) as message_count
                ')
                ->first();

            $inputCost = ($stats->total_input ?? 0) / 1_000_000 * 3;
            $outputCost = ($stats->total_output ?? 0) / 1_000_000 * 15;
            $totalCost = $inputCost + $outputCost;

            return [
                'input_tokens' => $stats->total_input ?? 0,
                'output_tokens' => $stats->total_output ?? 0,
                'total_tokens' => $stats->total_tokens ?? 0,
                'message_count' => $stats->message_count ?? 0,
                'cost_usd' => $totalCost,
                'cost_jpy' => $totalCost * 150,
            ];
        } catch (\Exception $e) {
            \Log::error('getMonthlyStats Error: ' . $e->getMessage());
            return [
                'input_tokens' => 0,
                'output_tokens' => 0,
                'total_tokens' => 0,
                'message_count' => 0,
                'cost_usd' => 0,
                'cost_jpy' => 0,
            ];
        }
    }

    /**
     * 詳細なトークン使用統計（日別、会話別）
     */
    public function getDetailedStats()
    {
        try {
            $startOfMonth = now()->startOfMonth();

            // 自分の会話IDを取得
            $conversationIds = Conversation::where('user_id', auth()->id())
                ->pluck('id');

            // 月間サマリー
            $monthlyStats = $this->getMonthlyStats();

            // 日別の統計（自分のデータのみ）
            $dailyStats = \DB::table('messages')
                ->whereIn('conversation_id', $conversationIds)
                ->where('created_at', '>=', $startOfMonth)
                ->whereNotNull('total_tokens')
                ->select(
                    \DB::raw('DATE(created_at) as date'),
                    \DB::raw('SUM(input_tokens) as input_tokens'),
                    \DB::raw('SUM(output_tokens) as output_tokens'),
                    \DB::raw('SUM(total_tokens) as total_tokens'),
                    \DB::raw('COUNT(*) as message_count')
                )
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->map(function($stat) {
                    $inputCost = ($stat->input_tokens ?? 0) / 1_000_000 * 3;
                    $outputCost = ($stat->output_tokens ?? 0) / 1_000_000 * 15;
                    return [
                        'date' => $stat->date,
                        'input_tokens' => (int)$stat->input_tokens,
                        'output_tokens' => (int)$stat->output_tokens,
                        'total_tokens' => (int)$stat->total_tokens,
                        'message_count' => (int)$stat->message_count,
                        'cost_usd' => $inputCost + $outputCost,
                        'cost_jpy' => ($inputCost + $outputCost) * 150,
                    ];
                });

            // 会話別の統計（自分の会話のみ）
            $conversationStats = \DB::table('conversations')
                ->join('messages', 'conversations.id', '=', 'messages.conversation_id')
                ->where('conversations.user_id', auth()->id())
                ->where('messages.created_at', '>=', $startOfMonth)
                ->whereNotNull('messages.total_tokens')
                ->select(
                    'conversations.id',
                    'conversations.title',
                    \DB::raw('SUM(messages.input_tokens) as input_tokens'),
                    \DB::raw('SUM(messages.output_tokens) as output_tokens'),
                    \DB::raw('SUM(messages.total_tokens) as total_tokens'),
                    \DB::raw('COUNT(messages.id) as message_count')
                )
                ->groupBy('conversations.id', 'conversations.title')
                ->orderByDesc('total_tokens')
                ->limit(10)
                ->get()
                ->map(function($stat) {
                    $inputCost = ($stat->input_tokens ?? 0) / 1_000_000 * 3;
                    $outputCost = ($stat->output_tokens ?? 0) / 1_000_000 * 15;
                    return [
                        'id' => $stat->id,
                        'title' => $stat->title ?? '無題の会話',
                        'input_tokens' => (int)$stat->input_tokens,
                        'output_tokens' => (int)$stat->output_tokens,
                        'total_tokens' => (int)$stat->total_tokens,
                        'message_count' => (int)$stat->message_count,
                        'cost_usd' => $inputCost + $outputCost,
                        'cost_jpy' => ($inputCost + $outputCost) * 150,
                    ];
                });

            return response()->json([
                'monthly' => $monthlyStats,
                'daily' => $dailyStats,
                'conversations' => $conversationStats,
            ]);

        } catch (\Exception $e) {
            \Log::error('Stats Error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'monthly' => $this->getMonthlyStats(),
                'daily' => [],
                'conversations' => [],
            ], 500);
        }
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

            'sales' => <<<'PROMPT'
あなたは経験豊富な営業コンサルタント・ビジネスアドバイザーAIです。

【専門分野】
- 新規顧客開拓 - アプローチ方法、初回訪問、関係構築
- 提案・プレゼンテーション - 提案書作成、商談準備、プレゼン技術
- 顧客対応 - メール文面、電話対応、クレーム処理
- 営業戦略 - ターゲット選定、市場分析、競合対策
- 契約・交渉 - 見積作成、価格交渉、契約書確認

【対応スタイル】
- 顧客視点を重視した提案
- 実践的で即実行できるアドバイス
- 業界標準のベストプラクティスを提示
- 具体的な文例・テンプレートの提供
- リスクや注意点も明示

【回答形式】
- 結論を先に（ポイントを3つまで）
- 具体的なアクションプランを提示
- 使える文例やテンプレートを含める
- 成功のコツと避けるべき失敗を明示
- 次のステップを示す

【目標】
- 顧客との信頼関係構築
- Win-Winの関係づくり
- 持続可能なビジネス成長
- 顧客満足度の向上

日本語で、ビジネスパーソン向けの実践的な口調で回答してください。
PROMPT,

            default => 'あなたは親切で知識豊富なAIアシスタントです。',
        };
    }
    
    /**
     * プリセットプロンプトを取得
     */
    public function getPromptPresets(string $mode)
    {
        $presets = \App\Models\PromptPreset::getByMode($mode);
        return response()->json($presets);
    }
}
