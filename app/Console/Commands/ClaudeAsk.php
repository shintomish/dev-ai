<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ClaudeAsk extends Command
{
    protected $signature = 'claude:ask
                            {question : 質問内容}
                            {--mode=dev : モード (dev or study)}
                            {--save : 会話を保存する}';

    protected $description = 'Claude APIに質問する';

    public function handle()
    {
        $question = $this->argument('question');
        $mode = $this->option('mode');
        $save = $this->option('save');

        if (!in_array($mode, ['dev', 'study'])) {
            $this->error('モードは dev または study を指定してください');
            return 1;
        }

        $this->info('🤖 Claude に質問中...');
        $this->newLine();

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
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $question,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $answer = $data['content'][0]['text'] ?? 'レスポンスが空です';

                // 回答を表示
                $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                $this->line($answer);
                $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                $this->newLine();

                // 使用量表示
                if (isset($data['usage'])) {
                    $usage = $data['usage'];
                    $this->comment(sprintf(
                        'トークン使用量: 入力 %d / 出力 %d',
                        $usage['input_tokens'],
                        $usage['output_tokens']
                    ));
                }

                // 会話を保存
                if ($save) {
                    $this->saveConversation($question, $answer, $mode, $data);
                }

                return 0;
            }

            $this->error('API呼び出しに失敗しました: ' . $response->body());
            return 1;

        } catch (\Exception $e) {
            $this->error('エラーが発生しました: ' . $e->getMessage());
            return 1;
        }
    }

    private function saveConversation($question, $answer, $mode, $data)
    {
        $conversation = \App\Models\Conversation::create(['mode' => $mode]);

        \App\Models\Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $question,
        ]);

        \App\Models\Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $answer,
            'metadata' => [
                'usage' => $data['usage'] ?? null,
                'model' => $data['model'] ?? null,
            ],
        ]);

        $conversation->generateTitle();

        $this->info("✅ 会話を保存しました (ID: {$conversation->id})");
    }

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