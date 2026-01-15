<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromptPreset;
use Illuminate\Http\Request;

class PromptPresetController extends Controller
{
    /**
     * 指定モードのプリセット一覧を取得
     */
    public function index(string $mode)
    {
        $presets = PromptPreset::getByMode($mode);

        return response()->json($presets);
    }

    /**
     * すべてのモードのプリセット一覧を取得
     */
    public function all()
    {
        $presets = PromptPreset::where('is_active', true)
            ->orderBy('mode')
            ->orderBy('order')
            ->get()
            ->groupBy('mode');

        return response()->json($presets);
    }
}
