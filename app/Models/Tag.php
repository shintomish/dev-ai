<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['user_id', 'name', 'color'];

    /**
     * タグの所有者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このタグが付けられた会話
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class);
    }
}