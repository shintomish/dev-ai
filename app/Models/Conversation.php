<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Conversation extends Model
{
    protected $fillable = ['title', 'mode', 'is_favorite'];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function generateTitle(): void
    {
        if ($this->title) return;

        $firstMessage = $this->messages()->where('role', 'user')->first();
        if ($firstMessage) {
            // 改行、タブ、複数の空白を1つの空白に置換
            $content = preg_replace('/\s+/', ' ', $firstMessage->content);
            $content = trim($content);

            // 最初の50文字を取得
            $title = mb_substr($content, 0, 50);
            if (mb_strlen($content) > 50) {
                $title .= '...';
            }

            $this->update(['title' => $title]);
        }
    }

    /**
     * 会話の合計トークン数
     */
    public function getTotalTokensAttribute()
    {
        return $this->messages()->sum('total_tokens') ?? 0;
    }

    /**
     * 会話の合計入力トークン数
     */
    public function getInputTokensAttribute()
    {
        return $this->messages()->sum('input_tokens') ?? 0;
    }

    /**
     * 会話の合計出力トークン数
     */
    public function getOutputTokensAttribute()
    {
        return $this->messages()->sum('output_tokens') ?? 0;
    }

    /**
     * 会話の合計コスト（USD）
     */
    public function getCostAttribute()
    {
        $inputCost = $this->input_tokens / 1_000_000 * 3;
        $outputCost = $this->output_tokens / 1_000_000 * 15;

        return $inputCost + $outputCost;
    }

    /**
     * 会話の合計コスト（JPY）
     */
    public function getCostJpyAttribute()
    {
        return $this->cost * 155;
    }

}