<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'message_id',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'content',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * 所属するメッセージ
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * ファイルサイズを人間が読める形式で返す
     */
    public function getHumanReadableSizeAttribute()
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    /**
     * ファイルの公開URLを取得
     */
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->filename);
    }
}