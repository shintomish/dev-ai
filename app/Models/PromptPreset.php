<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptPreset extends Model
{
    protected $fillable = [
        'mode',
        'title',
        'prompt',
        'icon',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 特定モードのアクティブなプリセットを取得
     */
    public static function getByMode(string $mode)
    {
        return self::where('mode', $mode)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }
}
