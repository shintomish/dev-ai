<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ['title', 'mode'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function generateTitle(): void
    {
        if ($this->title) return;

        $firstMessage = $this->messages()->where('role', 'user')->first();
        if ($firstMessage) {
            $title = mb_substr($firstMessage->content, 0, 50);
            if (mb_strlen($firstMessage->content) > 50) {
                $title .= '...';
            }
            $this->update(['title' => $title]);
        }
    }
}