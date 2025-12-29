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
}