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

        // 既存の会話を読み込み
        if ($conversationId) {
            $conversation = Conversation::with('messages')->find($conversationId);
            if ($conversation) {
                $messages = $conversation->messages;
            }
        }

        // 過去の会話一覧（最新10件）
        $recentConversations = Conversation::orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return view('chat', [
            'conversation' => $conversation,
            'messages' => $messages,
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
     * 新しい会話を開始
     */
    public function newConversation()
    {
        return redirect()->route('chat.index');
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