<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Events\MessageCreated;

class MessageController extends Controller
{
    /**
     * メッセージ一覧取得
     */
    public function index(Request $request, Conversation $conversation)
    {
        // 自分の会話かチェック
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'この会話にアクセスする権限がありません',
            ], 403);
        }

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'input_tokens' => $message->input_tokens,
                    'output_tokens' => $message->output_tokens,
                    'total_tokens' => $message->total_tokens,
                    'created_at' => $message->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * メッセージ送信（通常）
     */
    public function store(Request $request, Conversation $conversation)
    {
        // 自分の会話かチェック
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'この会話にアクセスする権限がありません',
            ], 403);
        }

        $request->validate([
            'message' => 'required|string|max:10000',
        ]);

        $messageText = $request->input('message');

        // ユーザーメッセージを保存
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
        $systemPrompt = match($conversation->mode) {
            'dev'   => 'あなたは優秀なプログラミングアシスタントです。コードの説明、デバッグ、最適化を支援します。具体的なコード例を提示し、ベストプラクティスに従ったアドバイスを提供してください。',
            'study' => 'あなたは親切な学習アシスタントです。分かりやすく、丁寧に説明します。専門用語を平易な言葉で説明し、例え話を使って理解を深めます。',
            'sales' => 'あなたは経験豊富な営業コンサルタントです。顧客との関係構築、提案資料の作成、商談の進め方をサポートします。実践的で具体的なアドバイスを提供し、顧客視点を重視します。',
            default => 'あなたは親切で知識豊富なAIアシスタントです。',
        };

        try {
            // Claude APIにリクエスト
            $response = Http::withHeaders([
                'x-api-key' => config('services.claude.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 4096,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Claude API request failed: ' . $response->body());
            }

            $data = $response->json();

            // Claudeの応答を取得
            $assistantContent = '';
            foreach ($data['content'] as $content) {
                if ($content['type'] === 'text') {
                    $assistantContent .= $content['text'];
                }
            }

            // トークン情報を取得
            $usage = $data['usage'] ?? null;
            $inputTokens = $usage['input_tokens'] ?? null;
            $outputTokens = $usage['output_tokens'] ?? null;
            $totalTokens = $inputTokens && $outputTokens ? $inputTokens + $outputTokens : null;

            // Claudeの応答を保存
            $assistantMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $assistantContent,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $totalTokens,
            ]);

            // ★ ここにイベント発火を追加
            event(new MessageCreated($assistantMessage));
            
            // コスト計算
            $inputCost = ($inputTokens ?? 0) / 1_000_000 * 3;
            $outputCost = ($outputTokens ?? 0) / 1_000_000 * 15;
            $totalCost = $inputCost + $outputCost;

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'user_message' => [
                    'id' => $userMessage->id,
                    'role' => 'user',
                    'content' => $userMessage->content,
                    'created_at' => $userMessage->created_at,
                ],
                'assistant_message' => [
                    'id' => $assistantMessage->id,
                    'role' => 'assistant',
                    'content' => $assistantMessage->content,
                    'created_at' => $assistantMessage->created_at,
                ],
                'tokens' => [
                    'input' => $inputTokens,
                    'output' => $outputTokens,
                    'total' => $totalTokens,
                ],
                'cost' => [
                    'usd' => $totalCost,
                    'jpy' => $totalCost * 150,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Claude API Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'メッセージの送信に失敗しました',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * メッセージ送信（ストリーミング）
     */
    public function stream(Request $request, Conversation $conversation)
    {
        // 自分の会話かチェック
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'この会話にアクセスする権限がありません',
            ], 403);
        }

        $request->validate([
            'message' => 'required|string|max:10000',
        ]);

        $messageText = $request->input('message');

        // ユーザーメッセージを保存
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
        $systemPrompt = match($conversation->mode) {
            'dev' => 'あなたは優秀なプログラミングアシスタントです。コードの説明、デバッグ、最適化を支援します。具体的なコード例を提示し、ベストプラクティスに従ったアドバイスを提供してください。',
            'study' => 'あなたは親切な学習アシスタントです。分かりやすく、丁寧に説明します。専門用語を平易な言葉で説明し、例え話を使って理解を深めます。',
            'sales' => 'あなたは経験豊富な営業コンサルタントです。顧客との関係構築、提案資料の作成、商談の進め方をサポートします。実践的で具体的なアドバイスを提供し、顧客視点を重視します。',
            default => 'あなたは親切で知識豊富なAIアシスタントです。',
        };

        // ストリーミングレスポンス
        return response()->stream(function () use ($conversation, $messages, $systemPrompt, $userMessage) {
            try {
                // Claude APIにストリーミングリクエスト
                $response = Http::withHeaders([
                    'x-api-key' => config('services.claude.api_key'),
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-sonnet-4-20250514',
                    'max_tokens' => 4096,
                    'system' => $systemPrompt,
                    'messages' => $messages,
                    'stream' => true,
                ]);

                if (!$response->successful()) {
                    echo "data: " . json_encode([
                        'error' => 'Claude API request failed',
                        'details' => $response->body()
                    ]) . "\n\n";
                    flush();
                    return;
                }

                $assistantContent = '';
                $inputTokens = null;
                $outputTokens = null;

                // ストリーミングレスポンスを読み取る
                $body = $response->body();
                $lines = explode("\n", $body);

                foreach ($lines as $line) {
                    if (empty($line) || !str_starts_with($line, 'data: ')) {
                        continue;
                    }

                    $jsonData = substr($line, 6); // "data: " を除去
                    
                    if ($jsonData === '[DONE]') {
                        break;
                    }

                    try {
                        $data = json_decode($jsonData, true);

                        // コンテンツブロックの開始
                        if (isset($data['type']) && $data['type'] === 'content_block_delta') {
                            if (isset($data['delta']['text'])) {
                                $text = $data['delta']['text'];
                                $assistantContent .= $text;
                                
                                // クライアントにテキストを送信
                                echo "data: " . json_encode(['text' => $text]) . "\n\n";
                                flush();
                            }
                        }

                        // メッセージの完了（トークン情報）
                        if (isset($data['type']) && $data['type'] === 'message_delta') {
                            if (isset($data['usage'])) {
                                $outputTokens = $data['usage']['output_tokens'] ?? null;
                            }
                        }

                        // 最初のメッセージ（入力トークン情報）
                        if (isset($data['type']) && $data['type'] === 'message_start') {
                            if (isset($data['message']['usage'])) {
                                $inputTokens = $data['message']['usage']['input_tokens'] ?? null;
                            }
                        }

                    } catch (\Exception $e) {
                        Log::error('Stream parsing error: ' . $e->getMessage());
                    }
                }

                // トークン情報を計算
                $totalTokens = $inputTokens && $outputTokens ? $inputTokens + $outputTokens : null;

                // Claudeの応答を保存
                $assistantMessage = Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $assistantContent,
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'total_tokens' => $totalTokens,
                ]);

                // コスト計算
                $inputCost = ($inputTokens ?? 0) / 1_000_000 * 3;
                $outputCost = ($outputTokens ?? 0) / 1_000_000 * 15;
                $totalCost = $inputCost + $outputCost;

                // 完了メッセージを送信
                echo "data: " . json_encode([
                    'done' => true,
                    'conversation_id' => $conversation->id,
                    'user_message_id' => $userMessage->id,
                    'assistant_message_id' => $assistantMessage->id,
                    'tokens' => [
                        'input' => $inputTokens,
                        'output' => $outputTokens,
                        'total' => $totalTokens,
                    ],
                    'cost' => [
                        'usd' => $totalCost,
                        'jpy' => $totalCost * 150,
                    ],
                ]) . "\n\n";
                flush();

            } catch (\Exception $e) {
                Log::error('Streaming error: ' . $e->getMessage());
                echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * ファイル付きメッセージ送信
     */
    public function uploadWithFile(Request $request, Conversation $conversation)
    {
        // 自分の会話かチェック
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'この会話にアクセスする権限がありません',
            ], 403);
        }

        $request->validate([
            'message' => 'required|string|max:10000',
            'file' => 'required|file|max:10240', // 10MB
        ]);

        $messageText = $request->input('message');
        $file = $request->file('file');

        // ファイル情報を取得
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // ファイルを保存
        $timestamp = now()->format('YmdHis');
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $fileName = "{$timestamp}_{$sanitizedName}.{$extension}";
        $filePath = $file->storeAs('attachments', $fileName, 'public');

        \Log::info('uploadWithFile $filePath: ' . $filePath);

        // ユーザーメッセージを保存
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $messageText,
        ]);

        // 添付ファイル情報を保存
        $attachment = \App\Models\Attachment::create([
            'message_id'        => $userMessage->id,
            'filename'          => $originalName,      // 保存されたファイル名
            'original_filename' => $originalName,      // 元のファイル名
            'filepath'          => $filePath,          // ファイルパス
            'mime_type'         => $mimeType,          // MIMEタイプ
            'size'              => $fileSize,          // ファイルサイズ
        ]);

        // ファイルの内容を取得
        $fileContent = null;
        $isImage = str_starts_with($mimeType, 'image/');

        if ($isImage) {
            // 画像ファイルの場合、base64エンコード
            $imageData = base64_encode(file_get_contents(storage_path('app/public/' . $filePath)));
            $mediaType = $mimeType;
        } else {
            // テキストファイルの場合、内容を読み取る
            try {
                $fileContent = file_get_contents(storage_path('app/public/' . $filePath));
            } catch (\Exception $e) {
                Log::error('File read error: ' . $e->getMessage());
            }
        }

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

        // 最後のメッセージ（ファイル付き）を特別に処理
        $lastMessage = &$messages[count($messages) - 1];
        
        if ($isImage) {
            // 画像の場合
            $lastMessage['content'] = [
                [
                    'type' => 'image',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => $mediaType,
                        'data' => $imageData,
                    ],
                ],
                [
                    'type' => 'text',
                    'text' => $messageText,
                ],
            ];
        } else {
            // テキストファイルの場合
            $lastMessage['content'] = $messageText . "\n\n【添付ファイル: {$originalName}】\n```\n" . $fileContent . "\n```";
        }

        // システムプロンプト
        $systemPrompt = match($conversation->mode) {
            'dev' => 'あなたは優秀なプログラミングアシスタントです。コードの説明、デバッグ、最適化を支援します。具体的なコード例を提示し、ベストプラクティスに従ったアドバイスを提供してください。',
            'study' => 'あなたは親切な学習アシスタントです。分かりやすく、丁寧に説明します。専門用語を平易な言葉で説明し、例え話を使って理解を深めます。',
            'sales' => 'あなたは経験豊富な営業コンサルタントです。顧客との関係構築、提案資料の作成、商談の進め方をサポートします。実践的で具体的なアドバイスを提供し、顧客視点を重視します。',
            default => 'あなたは親切で知識豊富なAIアシスタントです。',
        };
        
        try {
            // Claude APIにリクエスト
            $response = Http::withHeaders([
                'x-api-key' => config('services.claude.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 4096,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Claude API request failed: ' . $response->body());
            }

            $data = $response->json();

            // Claudeの応答を取得
            $assistantContent = '';
            foreach ($data['content'] as $content) {
                if ($content['type'] === 'text') {
                    $assistantContent .= $content['text'];
                }
            }

            // トークン情報を取得
            $usage = $data['usage'] ?? null;
            $inputTokens = $usage['input_tokens'] ?? null;
            $outputTokens = $usage['output_tokens'] ?? null;
            $totalTokens = $inputTokens && $outputTokens ? $inputTokens + $outputTokens : null;

            // Claudeの応答を保存
            $assistantMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $assistantContent,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $totalTokens,
            ]);

            // コスト計算
            $inputCost = ($inputTokens ?? 0) / 1_000_000 * 3;
            $outputCost = ($outputTokens ?? 0) / 1_000_000 * 15;
            $totalCost = $inputCost + $outputCost;

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'user_message' => [
                    'id' => $userMessage->id,
                    'role' => 'user',
                    'content' => $messageText,
                    'created_at' => $userMessage->created_at,
                ],
                'attachment' => [
                    'id' => $attachment->id,
                    'file_name' => $originalName,
                    'file_type' => $mimeType,
                    'file_size' => $fileSize,
                    'file_url' => asset('storage/' . $filePath),
                ],
                'assistant_message' => [
                    'id' => $assistantMessage->id,
                    'role' => 'assistant',
                    'content' => $assistantMessage->content,
                    'created_at' => $assistantMessage->created_at,
                ],
                'tokens' => [
                    'input' => $inputTokens,
                    'output' => $outputTokens,
                    'total' => $totalTokens,
                ],
                'cost' => [
                    'usd' => $totalCost,
                    'jpy' => $totalCost * 150,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Claude API Error with file: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'メッセージの送信に失敗しました',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * メッセージ検索
     */
    public function searchMessages(Request $request)
    {
        $keyword = $request->query('q');
        
        if (!$keyword) {
            return response()->json([
                'success' => false,
                'message' => '検索キーワードを指定してください',
            ], 400);
        }

        // ユーザーの会話IDを取得
        $conversationIds = Conversation::where('user_id', $request->user()->id)
            ->pluck('id');

        $query = Message::whereIn('conversation_id', $conversationIds)
            ->where('content', 'like', "%{$keyword}%");

        // ロール（user/assistant）で絞り込み
        if ($request->has('role')) {
            $query->where('role', $request->query('role'));
        }

        // 会話IDで絞り込み
        if ($request->has('conversation_id')) {
            $query->where('conversation_id', $request->query('conversation_id'));
        }

        // 日付範囲で絞り込み
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->query('from'));
        }
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->query('to'));
        }

        // ソート順
        $sortOrder = $request->query('sort_order', 'desc');
        $query->orderBy('created_at', $sortOrder);

        // ページネーション
        $perPage = $request->query('per_page', 20);
        $messages = $query->with('conversation')->paginate($perPage);

        return response()->json([
            'success' => true,
            'keyword' => $keyword,
            'data' => $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'conversation_title' => $message->conversation->title,
                    'role' => $message->role,
                    'content' => $message->content,
                    'input_tokens' => $message->input_tokens,
                    'output_tokens' => $message->output_tokens,
                    'total_tokens' => $message->total_tokens,
                    'created_at' => $message->created_at,
                ];
            }),
            'pagination' => [
                'total' => $messages->total(),
                'per_page' => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'from' => $messages->firstItem(),
                'to' => $messages->lastItem(),
            ],
        ]);
    }

}
