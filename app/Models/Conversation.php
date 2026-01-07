<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',      // ← これがあるか確認！
        'title',
        'mode',
        'is_favorite',
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
    ];

    // ユーザーとのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
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

    /**
     * タイトル自動生成
     */
    public function generateTitle()
    {
        if ($this->title === '新しい会話' && $this->messages()->count() > 0) {
            $firstMessage = $this->messages()->first();
            $this->title = mb_substr($firstMessage->content, 0, 50);
            $this->save();
        }
    }
}