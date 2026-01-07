<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
        'input_tokens',      // 追加
        'output_tokens',     // 追加
        'total_tokens',      // 追加
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * このメッセージに添付されたファイル
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * トークンコストを計算（USD）
     * Claude Sonnet 4: $3/MTok input, $15/MTok output
     */
    public function getCostAttribute()
    {
        if (!$this->input_tokens && !$this->output_tokens) {
            return 0;
        }

        $inputCost = ($this->input_tokens ?? 0) / 1_000_000 * 3;
        $outputCost = ($this->output_tokens ?? 0) / 1_000_000 * 15;

        return $inputCost + $outputCost;
    }

    /**
     * 日本円でのコスト（1USD = 155円で計算）
     */
    public function getCostJpyAttribute()
    {
        return $this->cost * 155;
    }
}