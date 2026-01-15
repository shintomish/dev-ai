<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * 詳細な統計データを取得
     */
    public function detailed(Request $request)
    {
        $userId = $request->user()->id;
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        // 月間統計
        $monthly = Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.user_id', $userId)
            ->where('messages.role', 'assistant')
            ->whereBetween('messages.created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as message_count,
                SUM(input_tokens) as input_tokens,
                SUM(output_tokens) as output_tokens,
                SUM(total_tokens) as total_tokens,
                SUM(cost_usd) as cost_usd
            ')
            ->first();

        $monthly->cost_jpy = $monthly->cost_usd * 150; // USD to JPY

        // 日別統計（グラフ用）
        $daily = Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.user_id', $userId)
            ->where('messages.role', 'assistant')
            ->whereBetween('messages.created_at', [$startDate, $endDate])
            ->selectRaw('
                DATE(messages.created_at) as date,
                SUM(input_tokens) as input_tokens,
                SUM(output_tokens) as output_tokens,
                SUM(total_tokens) as total_tokens,
                SUM(cost_usd) as cost_usd
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item->cost_jpy = $item->cost_usd * 150;
                return $item;
            });

        // トップ10会話
        $conversations = Conversation::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('total_tokens', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'monthly' => $monthly,
            'daily' => $daily,
            'conversations' => $conversations,
        ]);
    }

    /**
     * モード別の詳細統計を取得
     */
    public function byMode(Request $request)
    {
        $userId = $request->user()->id;
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        // モード別の月間統計
        $modeStats = Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.user_id', $userId)
            ->where('messages.role', 'assistant')
            ->whereBetween('messages.created_at', [$startDate, $endDate])
            ->selectRaw('
                conversations.mode,
                COUNT(*) as message_count,
                SUM(messages.input_tokens) as input_tokens,
                SUM(messages.output_tokens) as output_tokens,
                SUM(messages.total_tokens) as total_tokens,
                SUM(messages.cost_usd) as cost_usd
            ')
            ->groupBy('conversations.mode')
            ->get()
            ->map(function ($stat) {
                $stat->cost_jpy = $stat->cost_usd * 150; // USD to JPY
                return $stat;
            });

        // モード別の日別統計（グラフ用）
        $dailyByMode = Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.user_id', $userId)
            ->where('messages.role', 'assistant')
            ->whereBetween('messages.created_at', [$startDate, $endDate])
            ->selectRaw('
                DATE(messages.created_at) as date,
                conversations.mode,
                SUM(messages.input_tokens) as input_tokens,
                SUM(messages.output_tokens) as output_tokens,
                SUM(messages.total_tokens) as total_tokens
            ')
            ->groupBy('date', 'conversations.mode')
            ->orderBy('date')
            ->get()
            ->groupBy('mode');

        // モード別のトップ会話
        $topConversationsByMode = Conversation::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('messages')
            ->get()
            ->groupBy('mode')
            ->map(function ($conversations) {
                return $conversations->sortByDesc('total_tokens')->take(5)->values();
            });

        return response()->json([
            'mode_stats' => $modeStats,
            'daily_by_mode' => $dailyByMode,
            'top_conversations_by_mode' => $topConversationsByMode,
        ]);
    }
}
