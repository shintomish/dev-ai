<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * 会話一覧取得
     */
    public function index(Request $request)
    {
        $conversations = Conversation::where('user_id', $request->user()->id)
            ->with('tags')
            ->latest()
            ->get()
            ->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'mode' => $conversation->mode,
                    'is_favorite' => $conversation->is_favorite,
                    'tags' => $conversation->tags->pluck('name'),
                    'message_count' => $conversation->messages()->count(),
                    'total_tokens' => $conversation->total_tokens,
                    'cost_usd' => $conversation->cost,
                    'cost_jpy' => $conversation->cost_jpy,
                    'created_at' => $conversation->created_at,
                    'updated_at' => $conversation->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * 会話作成
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'mode' => 'required|in:dev,study',
        ]);

        $conversation = Conversation::create([
            'user_id' => $request->user()->id,
            'title' => $request->title ?? '新しい会話',
            'mode' => $request->mode,
        ]);

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'mode' => $conversation->mode,
                'is_favorite' => $conversation->is_favorite,
                'created_at' => $conversation->created_at,
            ],
        ], 201);
    }

    /**
     * 会話詳細取得
     */
    public function show(Request $request, Conversation $conversation)
    {
        // 自分の会話かチェック
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'この会話にアクセスする権限がありません',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'mode' => $conversation->mode,
                'is_favorite' => $conversation->is_favorite,
                'tags' => $conversation->tags->pluck('name'),
                'message_count' => $conversation->messages()->count(),
                'total_tokens' => $conversation->total_tokens,
                'cost_usd' => $conversation->cost,
                'cost_jpy' => $conversation->cost_jpy,
                'created_at' => $conversation->created_at,
                'updated_at' => $conversation->updated_at,
            ],
        ]);
    }

    /**
     * 会話削除
     */
    public function destroy(Request $request, Conversation $conversation)
    {
        // 自分の会話かチェック
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'この会話にアクセスする権限がありません',
            ], 403);
        }

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => '会話を削除しました',
        ]);
    }

    /**
     * お気に入り切り替え
     */
    public function toggleFavorite(Request $request, Conversation $conversation)
    {
        // 自分の会話かチェック
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'この会話にアクセスする権限がありません',
            ], 403);
        }

        $conversation->is_favorite = !$conversation->is_favorite;
        $conversation->save();

        return response()->json([
            'success' => true,
            'is_favorite' => $conversation->is_favorite,
        ]);
    }

    /**
     * タグ更新
     */
    public function updateTags(Request $request, Conversation $conversation)
    {
        // 自分の会話かチェック
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'この会話にアクセスする権限がありません',
            ], 403);
        }

        $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string|max:50',
        ]);

        // 既存のタグを取得または作成
        $tagIds = [];
        foreach ($request->tags as $tagName) {
            $tag = \App\Models\Tag::firstOrCreate(['name' => $tagName]);
            $tagIds[] = $tag->id;
        }

        // タグを同期
        $conversation->tags()->sync($tagIds);

        return response()->json([
            'success' => true,
            'tags' => $conversation->tags->pluck('name'),
        ]);
    }

    /**
     * 月間統計
     */
    public function monthlyStats(Request $request)
    {
        $startOfMonth = now()->startOfMonth();

        $conversationIds = Conversation::where('user_id', $request->user()->id)
            ->pluck('id');

        if ($conversationIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'stats' => [
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'total_tokens' => 0,
                    'message_count' => 0,
                    'cost_usd' => 0,
                    'cost_jpy' => 0,
                ],
            ]);
        }

        $stats = \App\Models\Message::whereIn('conversation_id', $conversationIds)
            ->where('created_at', '>=', $startOfMonth)
            ->whereNotNull('total_tokens')
            ->selectRaw('
                SUM(input_tokens) as total_input,
                SUM(output_tokens) as total_output,
                SUM(total_tokens) as total_tokens,
                COUNT(*) as message_count
            ')
            ->first();

        $inputCost = ($stats->total_input ?? 0) / 1_000_000 * 3;
        $outputCost = ($stats->total_output ?? 0) / 1_000_000 * 15;
        $totalCost = $inputCost + $outputCost;

        return response()->json([
            'success' => true,
            'stats' => [
                'input_tokens' => $stats->total_input ?? 0,
                'output_tokens' => $stats->total_output ?? 0,
                'total_tokens' => $stats->total_tokens ?? 0,
                'message_count' => $stats->message_count ?? 0,
                'cost_usd' => $totalCost,
                'cost_jpy' => $totalCost * 150,
            ],
        ]);
    }

    /**
     * 詳細統計
     */
    public function detailedStats(Request $request)
    {
        // ChatControllerのgetDetailedStatsと同じロジック
        // 簡略化のため、monthlyStatsのみ返す
        return $this->monthlyStats($request);
    }

    /**
     * 会話検索
     */
    public function searchConversations(Request $request)
    {
        $query = Conversation::where('user_id', $request->user()->id);

        // キーワード検索（タイトル）
        if ($request->has('q')) {
            $keyword = $request->query('q');
            $query->where('title', 'like', "%{$keyword}%");
        }

        // モードで絞り込み
        if ($request->has('mode')) {
            $query->where('mode', $request->query('mode'));
        }

        // お気に入りで絞り込み
        if ($request->has('favorite')) {
            $isFavorite = filter_var($request->query('favorite'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_favorite', $isFavorite);
        }

        // タグで絞り込み
        if ($request->has('tag')) {
            $tagName = $request->query('tag');
            $query->whereHas('tags', function ($q) use ($tagName) {
                $q->where('name', $tagName);
            });
        }

        // 日付範囲で絞り込み
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->query('from'));
        }
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->query('to'));
        }

        // ソート順
        $sortBy = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // ページネーション
        $perPage = $request->query('per_page', 20);
        $conversations = $query->with('tags')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $conversations->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'mode' => $conversation->mode,
                    'is_favorite' => $conversation->is_favorite,
                    'tags' => $conversation->tags->pluck('name'),
                    'message_count' => $conversation->messages()->count(),
                    'total_tokens' => $conversation->total_tokens,
                    'cost_usd' => $conversation->cost,
                    'cost_jpy' => $conversation->cost_jpy,
                    'created_at' => $conversation->created_at,
                    'updated_at' => $conversation->updated_at,
                ];
            }),
            'pagination' => [
                'total' => $conversations->total(),
                'per_page' => $conversations->perPage(),
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'from' => $conversations->firstItem(),
                'to' => $conversations->lastItem(),
            ],
        ]);
    }

    /**
     * 全文検索（会話とメッセージ）
     */
    public function searchAll(Request $request)
    {
        $keyword = $request->query('q');
        
        if (!$keyword) {
            return response()->json([
                'success' => false,
                'message' => '検索キーワードを指定してください',
            ], 400);
        }

        $conversationIds = Conversation::where('user_id', $request->user()->id)
            ->pluck('id');

        // 会話をタイトルで検索
        $conversations = Conversation::where('user_id', $request->user()->id)
            ->where('title', 'like', "%{$keyword}%")
            ->with('tags')
            ->get()
            ->map(function ($conversation) {
                return [
                    'type' => 'conversation',
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'mode' => $conversation->mode,
                    'is_favorite' => $conversation->is_favorite,
                    'tags' => $conversation->tags->pluck('name'),
                    'created_at' => $conversation->created_at,
                ];
            });

        // メッセージを内容で検索
        $messages = \App\Models\Message::whereIn('conversation_id', $conversationIds)
            ->where('content', 'like', "%{$keyword}%")
            ->with('conversation')
            ->get()
            ->map(function ($message) {
                return [
                    'type' => 'message',
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'conversation_title' => $message->conversation->title,
                    'role' => $message->role,
                    'content' => mb_substr($message->content, 0, 200) . '...',
                    'created_at' => $message->created_at,
                ];
            });

        // 結果を統合
        $results = $conversations->concat($messages)->sortByDesc('created_at')->values();

        return response()->json([
            'success' => true,
            'keyword' => $keyword,
            'total_results' => $results->count(),
            'conversations_found' => $conversations->count(),
            'messages_found' => $messages->count(),
            'results' => $results,
        ]);
    }

}