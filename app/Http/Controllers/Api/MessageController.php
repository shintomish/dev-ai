<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $systemPrompt = $conversation->mode === 'dev'
            ? 'あなたは優秀なプログラミングアシスタントです。コードの説明、デバッグ、最適化を支援します。'
            : 'あなたは親切な学習アシスタントです。分かりやすく、丁寧に説明します。';

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
        $systemPrompt = $conversation->mode === 'dev'
            ? 'あなたは優秀なプログラミングアシスタントです。コードの説明、デバッグ、最適化を支援します。'
            : 'あなたは親切な学習アシスタントです。分かりやすく、丁寧に説明します。';

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
}