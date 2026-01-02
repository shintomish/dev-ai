<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'content',
        'is_image',  // 追加
    ];

    protected $casts = [
        'is_image' => 'boolean',  // 追加
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * 人間が読めるファイルサイズ
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
     * 画像かどうかを判定
     */
    public function isImage(): bool
    {
        return $this->is_image || str_starts_with($this->mime_type, 'image/');
    }

    /**
     * 公開URLを取得
     */
    public function getPublicUrlAttribute()
    {
        return asset('storage/' . $this->filename);
    }
}