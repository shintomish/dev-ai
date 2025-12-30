<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>開発支援AI - Claude Chat</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <script src="https://cdn.tailwindcss.com"></script>

    <!-- highlight.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/bash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/python.min.js"></script>

    <style>
        /* ===== 全体レイアウト ===== */
        * {
            box-sizing: border-box;
        }

        html, body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
            margin: 0;
            padding: 0;
            height: 100vh;
        }

        body {
            background: #f9fafb;
        }

        /* メインコンテナ */
        .flex.h-screen {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        /* ===== サイドバー ===== */
        aside {
            flex-shrink: 0;
            flex-grow: 0;
            min-width: 16rem;
            width: 16rem;
            max-width: 16rem;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            background: white;
            border-right: 1px solid #e5e7eb;
        }

        aside::-webkit-scrollbar {
            width: 6px;
        }

        aside::-webkit-scrollbar-track {
            background: #f9fafb;
        }

        aside::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 3px;
        }

        aside::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        /* ===== サイドバーレイアウト統一 ===== */

        /* 会話リストコンテナ */
        aside .space-y-2 {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
        }

        /* 各会話アイテム（お気に入り+リンク+削除） */
        aside .flex.items-center.gap-2 {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            width: 100%;
            min-width: 0;
        }

        /* お気に入りボタン */
        aside .flex-shrink-0.text-xl {
            flex-shrink: 0;
            width: 1.5rem;
            text-align: center;
        }

        /* 会話リンク */
        aside a[href*="chat?conversation"] {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        /* 削除ボタン */
        aside button[onclick*="deleteConversation"] {
            flex-shrink: 0;
            width: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
        }

        /* 会話タイトル */
        aside .truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
            width: 100%;
        }

        /* タグコンテナ */
        aside .flex.flex-wrap.gap-1 {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            max-width: 100%;
            overflow: hidden;
            max-height: 2rem;
        }

        /* 個別タグ */
        aside .tag {
            display: inline-block;
            padding: 0.125rem 0.375rem;
            background: #e5e7eb;
            border-radius: 9999px;
            font-size: 0.625rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 5rem;
        }

        /* ===== メインチャットエリア ===== */
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            max-width: 100%;
            height: 100vh;
            overflow: hidden;
        }

        /* チャットメッセージ表示エリア */
        #chatMessages {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden !important;
            padding: 1rem;
            background: #f9fafb;
            min-height: 0;
            max-width: 100% !important;
        }

        #chatMessages::-webkit-scrollbar {
            width: 8px;
        }

        #chatMessages::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #chatMessages::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        #chatMessages::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* ===== メッセージスタイル ===== */
        .message {
            margin-bottom: 1rem;
            max-width: 100% !important;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ユーザーメッセージ */
        .message.user {
            align-items: flex-end;
        }

        .message.user .message-content {
            background: #3b82f6;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            max-width: min(80%, 800px);
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        /* AIメッセージ */
        .message.assistant {
            align-items: flex-start;
        }

        .message.assistant .message-content {
            background: white;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            max-width: min(80%, 800px);
            border: 1px solid #e5e7eb;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        /* エラーメッセージ */
        .message.error .message-content {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            max-width: min(80%, 800px);
        }

        /* ローディング */
        .message.loading .message-content {
            background: #f3f4f6;
            color: #6b7280;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            max-width: min(80%, 800px);
        }

        /* ===== メッセージ内容 ===== */
        .message-content {
            max-width: 100% !important;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            overflow: hidden !important;
        }

        .message-content * {
            max-width: 100% !important;
        }

        .message-content p {
            margin: 0.5rem 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .message-content ul,
        .message-content ol {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
        }

        .message-content li {
            margin: 0.25rem 0;
            word-wrap: break-word;
        }

        /* ===== コードブロック ===== */
        .message-content pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem;
            padding-top: 2rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            overflow-y: hidden;
            margin: 0.5rem 0;
            position: relative;
            max-width: 100% !important;
        }

        .message-content pre code {
            background: transparent;
            padding: 0;
            display: block;
            max-width: 100%;
        }

        /* インラインコード */
        .message-content code {
            background: #1e293b;
            color: #22d3ee;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-family: 'Courier New', monospace;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .message-content pre code {
            background: transparent;
            padding: 0;
        }

        /* コピーボタン */
        .copy-button {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            padding: 0.25rem 0.75rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.75rem;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 10;
        }

        .message-content pre:hover .copy-button {
            opacity: 1;
        }

        .copy-button:hover {
            background: #2563eb;
        }

        .copy-button.copied {
            background: #10b981;
        }

        /* ===== その他の要素 ===== */
        .message-content a {
            color: #3b82f6;
            text-decoration: underline;
            word-break: break-all;
            overflow-wrap: break-word;
        }

        .message-content table {
            display: block;
            overflow-x: auto;
            max-width: 100%;
            border-collapse: collapse;
            margin: 0.5rem 0;
        }

        .message-content table th,
        .message-content table td {
            border: 1px solid #e5e7eb;
            padding: 0.5rem;
            text-align: left;
        }

        .message-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            display: block;
            margin: 0.5rem 0;
        }

        .message-content blockquote {
            border-left: 4px solid #e5e7eb;
            padding-left: 1rem;
            margin: 0.5rem 0;
            color: #6b7280;
            word-wrap: break-word;
        }

        .message-content hr {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 1rem 0;
        }

        /* ===== ストリーミング ===== */
        .streaming-content {
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .streaming-cursor {
            display: inline-block;
            width: 0.5rem;
            height: 1rem;
            background: currentColor;
            margin-left: 0.25rem;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }

        /* ===== フォームエリア ===== */
        #chatForm {
            flex-shrink: 0;
            flex-grow: 0;
            border-top: 1px solid #e5e7eb;
            background: white;
            padding: 1rem;
            max-width: 100%;
        }

        #messageInput {
            flex: 1;
            min-width: 0;
            max-width: 100%;
        }

        #sendButton {
            flex-shrink: 0;
        }

        /* ===== ファイルリスト ===== */
        #fileList {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f3f4f6;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            max-width: 100%;
            overflow: hidden;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.25rem 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-all;
        }

        /* ===== その他 ===== */
        #charCount {
            flex-shrink: 0;
            white-space: nowrap;
        }

        .tag {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            background: #e5e7eb;
            border-radius: 9999px;
            font-size: 0.75rem;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .conversation-item {
            padding: 0.5rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.2s;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .conversation-item:hover {
            background-color: #f3f4f6;
        }

        .conversation-item.active {
            background-color: #dbeafe;
        }

        /* ===== レスポンシブ ===== */
        @media (max-width: 768px) {
            aside {
                position: fixed;
                left: -16rem;
                z-index: 1000;
                transition: left 0.3s;
            }

            aside.open {
                left: 0;
            }

            .message.user .message-content,
            .message.assistant .message-content {
                max-width: 90%;
            }

            .message-content pre {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="flex h-screen">
        <!-- サイドバー -->
        <aside>
            <div class="p-4 border-b border-gray-200">
                <a href="{{ route('chat.new') }}" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                    <span>➕</span>
                    <span>新しい会話</span>
                </a>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <!-- お気に入りセクション -->
                @if($favoriteConversations->isNotEmpty())
                    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">⭐ お気に入り</h3>
                    <div class="space-y-2 mb-4">
                        @foreach($favoriteConversations as $conv)
                            <div class="flex items-center gap-2">
                                <button onclick="toggleFavorite({{ $conv->id }}, event)"
                                        class="flex-shrink-0 text-xl hover:scale-110 transition-transform"
                                        title="お気に入り解除">
                                    ⭐
                                </button>
                                <a href="{{ route('chat.index', ['conversation' => $conv->id]) }}"
                                class="flex-1 block p-3 rounded-lg hover:bg-gray-100 {{ $conversation && $conversation->id === $conv->id ? 'bg-blue-50 border border-blue-200' : 'bg-white border border-gray-200' }}">
                                    <div class="text-sm font-medium text-gray-900 truncate">
                                        {{ $conv->title ?? '無題の会話' }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $conv->updated_at->diffForHumans() }}
                                    </div>
                                    @if($conv->tags->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach($conv->tags as $tag)
                                                <span class="tag">{{ $tag->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </a>
                                <button onclick="deleteConversation({{ $conv->id }})"
                                        class="flex-shrink-0 text-red-500 hover:text-red-700 p-1"
                                        title="削除">
                                    🗑️
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- 最近の会話セクション -->
                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">最近の会話</h3>
                <div class="space-y-2">
                    @forelse($recentConversations as $conv)
                        <div class="flex items-center gap-2">
                            <button onclick="toggleFavorite({{ $conv->id }}, event)"
                                    class="flex-shrink-0 text-xl hover:scale-110 transition-transform"
                                    title="お気に入りに追加">
                                ☆
                            </button>
                            <a href="{{ route('chat.index', ['conversation' => $conv->id]) }}"
                            class="flex-1 block p-3 rounded-lg hover:bg-gray-100 {{ $conversation && $conversation->id === $conv->id ? 'bg-blue-50 border border-blue-200' : 'bg-white border border-gray-200' }}">
                                <div class="text-sm font-medium text-gray-900 truncate">
                                    {{ $conv->title ?? '無題の会話' }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $conv->updated_at->diffForHumans() }}
                                </div>
                                @if($conv->tags->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        @foreach($conv->tags as $tag)
                                            <span class="tag">{{ $tag->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </a>
                            <button onclick="deleteConversation({{ $conv->id }})"
                                    class="flex-shrink-0 text-red-500 hover:text-red-700 p-1"
                                    title="削除">
                                🗑️
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">会話履歴がありません</p>
                    @endforelse
                </div>
            </div>
        </aside>

        <!-- メインチャットエリア -->
        <main>
            <!-- ヘッダー -->
            <div class="border-b border-gray-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-xl font-bold text-gray-900">
                        {{ $conversation ? $conversation->title : '新しい会話' }}
                    </h1>
                    @if($conversation)
                        <div class="flex items-center gap-2">
                            <!-- お気に入りトグル -->
                            <button onclick="toggleFavoriteHeader({{ $conversation->id }})"
                                    class="px-3 py-1 text-xl hover:scale-110 transition-transform"
                                    title="{{ $conversation->is_favorite ? 'お気に入り解除' : 'お気に入りに追加' }}">
                                {{ $conversation->is_favorite ? '⭐' : '☆' }}
                            </button>

                            <!-- タグ管理 -->
                            <div class="relative">
                                <button onclick="toggleTagMenu()" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                                    🏷️ タグ
                                </button>
                                <div id="tagMenu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                                    <div class="p-2 max-h-60 overflow-y-auto">
                                        @foreach($allTags as $tag)
                                            <label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                                <input type="checkbox"
                                                       value="{{ $tag->id }}"
                                                       {{ $conversation->tags->contains($tag->id) ? 'checked' : '' }}
                                                       onchange="handleTagChange({{ $conversation->id }}, {{ $tag->id }}, this.checked)"
                                                       class="rounded">
                                                <span class="text-sm">{{ $tag->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- エクスポート -->
                            <div class="relative">
                                <button onclick="toggleExportMenu()" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                                    📥 エクスポート
                                </button>
                                <div id="exportMenu" class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                                    <a href="{{ route('chat.export', ['conversation' => $conversation->id, 'format' => 'markdown']) }}"
                                       class="block px-4 py-2 text-sm hover:bg-gray-50">
                                        📝 Markdown
                                    </a>
                                    <a href="{{ route('chat.export', ['conversation' => $conversation->id, 'format' => 'json']) }}"
                                       class="block px-4 py-2 text-sm hover:bg-gray-50">
                                        📊 JSON
                                    </a>
                                    <a href="{{ route('chat.export', ['conversation' => $conversation->id, 'format' => 'txt']) }}"
                                       class="block px-4 py-2 text-sm hover:bg-gray-50">
                                        📄 テキスト
                                    </a>
                                </div>
                            </div>

                            <!-- 削除ボタン -->
                            <button onclick="deleteConversationHeader({{ $conversation->id }})"
                                    class="px-3 py-1 text-sm border border-red-300 text-red-600 rounded-lg hover:bg-red-50 hover:border-red-400"
                                    title="この会話を削除">
                                🗑️ 削除
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- チャットメッセージ -->
            <div id="chatMessages">
                @foreach($messages as $message)
                    <div class="message {{ $message->role }}">
                        <div class="message-content">
                            {!! nl2br(e($message->content)) !!}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- 入力フォーム -->
            <form id="chatForm">
                <input type="hidden" name="conversation_id" id="conversationId" value="{{ $conversation->id ?? '' }}">

                <!-- モード選択 -->
                <div class="flex items-center gap-4 mb-3">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="mode" value="dev" {{ !$conversation || $conversation->mode === 'dev' ? 'checked' : '' }}
                               class="text-blue-600" {{ $conversation ? 'disabled' : '' }}>
                        <span class="text-sm font-medium">🔧 開発支援</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="mode" value="study" {{ $conversation && $conversation->mode === 'study' ? 'checked' : '' }}
                               class="text-green-600" {{ $conversation ? 'disabled' : '' }}>
                        <span class="text-sm font-medium">📚 学習支援</span>
                    </label>
                    <label class="flex items-center gap-2 ml-auto">
                        <input type="checkbox" id="streamMode" class="rounded">
                        <span class="text-sm">⚡ ストリーミング</span>
                    </label>
                </div>

                <!-- ファイルアップロード -->
                <div class="mb-3">
                    <input type="file" id="fileInput" name="files[]" multiple class="hidden" accept=".txt,.log,.php,.js,.py,.java,.cpp,.h,.md,.json,.xml,.yaml,.yml">
                    <button type="button" onclick="document.getElementById('fileInput').click()"
                            class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                        📎 ファイルを添付
                    </button>
                    <div id="fileList"></div>
                </div>

                <!-- メッセージ入力 -->
                <div class="flex gap-2">
                    <textarea id="messageInput"
                              name="message"
                              placeholder="メッセージを入力..."
                              rows="3"
                              maxlength="10000"
                              class="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                              required></textarea>
                    <div class="flex flex-col gap-2">
                        <button type="submit" id="sendButton"
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                            送信
                        </button>
                        <span id="charCount" class="text-xs text-gray-500 text-center">0 / 10000</span>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const conversationIdInput = document.getElementById('conversationId');
        const charCount = document.getElementById('charCount');
        const fileInput = document.getElementById('fileInput');

        // ページ読み込み時の処理
        document.addEventListener('DOMContentLoaded', function() {
            // 既存メッセージのフォーマット
            document.querySelectorAll('.message-content').forEach(element => {
                if (!element.classList.contains('formatted')) {
                    const formattedContent = formatResponse(element.textContent);
                    element.innerHTML = formattedContent;
                    element.classList.add('formatted');

                    element.querySelectorAll('pre code').forEach((block) => {
                        hljs.highlightBlock(block);
                    });
                }
            });

            // 最下部にスクロール
            chatMessages.scrollTop = chatMessages.scrollHeight;

            // メニューの外側クリックで閉じる
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#tagMenu') && !e.target.closest('button[onclick="toggleTagMenu()"]')) {
                    document.getElementById('tagMenu')?.classList.add('hidden');
                }
                if (!e.target.closest('#exportMenu') && !e.target.closest('button[onclick="toggleExportMenu()"]')) {
                    document.getElementById('exportMenu')?.classList.add('hidden');
                }
            });
        });

        // 文字数カウント
        messageInput.addEventListener('input', function() {
            charCount.textContent = `${this.value.length} / 10000`;
            sendButton.disabled = this.value.trim().length === 0;
        });

        // ファイル選択
        fileInput.addEventListener('change', function() {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';

            if (this.files.length > 0) {
                Array.from(this.files).forEach(file => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    fileItem.innerHTML = `
                        <span>📄 ${file.name} (${formatFileSize(file.size)})</span>
                        <button type="button" onclick="removeFile('${file.name}')" class="text-red-500 hover:text-red-700">✕</button>
                    `;
                    fileList.appendChild(fileItem);
                });
            }
        });

        function removeFile(filename) {
            const dt = new DataTransfer();
            const files = fileInput.files;

            for (let i = 0; i < files.length; i++) {
                if (files[i].name !== filename) {
                    dt.items.add(files[i]);
                }
            }

            fileInput.files = dt.files;
            fileInput.dispatchEvent(new Event('change'));
        }

        function formatFileSize(bytes) {
            if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return bytes + ' bytes';
        }

        // メッセージ追加
        function appendMessage(role, content, isLoading = false) {
            const messageId = 'msg-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            const messageDiv = document.createElement('div');
            messageDiv.id = messageId;
            messageDiv.className = `message ${role} ${isLoading ? 'loading' : ''}`;

            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';

            if (role === 'assistant' && !isLoading) {
                contentDiv.innerHTML = formatResponse(content);
                messageDiv.appendChild(contentDiv);
                chatMessages.appendChild(messageDiv);

                contentDiv.querySelectorAll('pre code').forEach((block) => {
                    hljs.highlightBlock(block);
                });
            } else {
                contentDiv.textContent = content;
                messageDiv.appendChild(contentDiv);
                chatMessages.appendChild(messageDiv);
            }

            chatMessages.scrollTop = chatMessages.scrollHeight;
            return messageId;
        }

        // レスポンスフォーマット
        function formatResponse(text) {
            let formatted = text
                .replace(/```(\w+)?\n([\s\S]*?)```/g, (match, lang, code) => {
                    const language = lang || 'plaintext';
                    const codeId = 'code-' + Math.random().toString(36).substr(2, 9);
                    return `<pre><button class="copy-button" onclick="copyCode('${codeId}')">📋 コピー</button><code id="${codeId}" class="language-${language}">${escapeHtml(code.trim())}</code></pre>`;
                })
                .replace(/`([^`]+)`/g, '<code>$1</code>')
                .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
                .replace(/\*([^*]+)\*/g, '<em>$1</em>')
                .replace(/\n/g, '<br>');

            return formatted;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ストリーミングメッセージ
        function appendStreamingMessage() {
            const messageId = 'msg-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            const messageDiv = document.createElement('div');
            messageDiv.id = messageId;
            messageDiv.className = 'message assistant';

            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content streaming-content';
            contentDiv.innerHTML = '<span class="streaming-cursor"></span>';

            messageDiv.appendChild(contentDiv);
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;

            return messageId;
        }

        function appendTextToStreamingMessage(messageId, text) {
            const messageDiv = document.getElementById(messageId);
            if (!messageDiv) return;

            const contentDiv = messageDiv.querySelector('.streaming-content');
            const cursor = contentDiv.querySelector('.streaming-cursor');

            if (cursor) {
                cursor.insertAdjacentText('beforebegin', text);
            } else {
                contentDiv.textContent += text;
            }

            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }

        function finalizeStreamingMessage(messageId) {
            const messageDiv = document.getElementById(messageId);
            if (!messageDiv) return;

            const cursor = messageDiv.querySelector('.streaming-cursor');
            if (cursor) cursor.remove();

            const contentDiv = messageDiv.querySelector('.streaming-content');
            const formattedContent = formatResponse(contentDiv.textContent);
            contentDiv.innerHTML = formattedContent;
            contentDiv.classList.remove('streaming-content');

            contentDiv.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightBlock(block);
            });
        }

        // ファイルアップロード処理
        async function handleFileUpload(message, mode, conversationId, fileInput) {
            const loadingId = appendMessage('assistant', '考え中...', true);

            try {
                const formData = new FormData();
                formData.append('message', message);
                formData.append('mode', mode);
                if (conversationId) {
                    formData.append('conversation_id', conversationId);
                }

                Array.from(fileInput.files).forEach(file => {
                    formData.append('files[]', file);
                });

                const response = await fetch('{{ route("chat.send") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Server Error:', errorText);
                    throw new Error(`HTTP ${response.status}: サーバーエラー`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType?.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON Response:', text.substring(0, 500));
                    throw new Error('サーバーから正しい応答が返されませんでした');
                }

                const data = await response.json();
                document.getElementById(loadingId)?.remove();

                if (data.success) {
                    appendMessage('assistant', data.response);

                    if (data.conversation_id && !conversationIdInput.value) {
                        conversationIdInput.value = data.conversation_id;
                        window.history.replaceState({}, '', `/chat?conversation=${data.conversation_id}`);
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    appendMessage('error', `エラー: ${data.error}`);
                }
            } catch (error) {
                document.getElementById(loadingId)?.remove();
                console.error('Upload Error:', error);
                appendMessage('error', `アップロードエラー: ${error.message}`);
            }
        }

        // 通常のメッセージ送信
        async function handleNormalResponse(message, mode, conversationId) {
            const loadingId = appendMessage('assistant', '考え中...', true);

            try {
                const response = await fetch('{{ route("chat.send") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        message: message,
                        mode: mode,
                        conversation_id: conversationId,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                document.getElementById(loadingId)?.remove();

                if (data.success) {
                    appendMessage('assistant', data.response);

                    if (data.conversation_id && !conversationIdInput.value) {
                        conversationIdInput.value = data.conversation_id;
                        window.history.replaceState({}, '', `/chat?conversation=${data.conversation_id}`);
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    appendMessage('error', `エラー: ${data.error}`);
                }
            } catch (error) {
                document.getElementById(loadingId)?.remove();
                appendMessage('error', `エラー: ${error.message}`);
            }
        }

        // ストリーミング送信
        async function handleStreamingResponse(message, mode, conversationId) {
            const messageId = appendStreamingMessage();

            try {
                const response = await fetch('{{ route("chat.send.stream") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                    },
                    body: JSON.stringify({
                        message: message,
                        mode: mode,
                        conversation_id: conversationId,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop();

                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            try {
                                const data = JSON.parse(line.slice(6));

                                if (data.text) {
                                    appendTextToStreamingMessage(messageId, data.text);
                                }

                                if (data.done) {
                                    if (data.conversation_id && !conversationIdInput.value) {
                                        conversationIdInput.value = data.conversation_id;
                                        window.history.replaceState({}, '', `/chat?conversation=${data.conversation_id}`);
                                        setTimeout(() => location.reload(), 1000);
                                    }
                                }

                                if (data.error) {
                                    throw new Error(data.error);
                                }
                            } catch (parseError) {
                                console.error('JSON Parse Error:', parseError);
                            }
                        }
                    }
                }

                finalizeStreamingMessage(messageId);

            } catch (error) {
                console.error('Streaming Error:', error);
                document.getElementById(messageId)?.remove();
                appendMessage('error', `ストリーミングエラー: ${error.message}`);
            }
        }

        // フォーム送信
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const message = messageInput.value.trim();
            if (!message) return;

            const mode = document.querySelector('input[name="mode"]:checked').value;
            const conversationId = conversationIdInput.value || null;
            const streamMode = document.getElementById('streamMode')?.checked || false;
            const hasFiles = fileInput && fileInput.files.length > 0;

            let displayMessage = message;
            if (hasFiles) {
                displayMessage += '\n\n📎 ' + Array.from(fileInput.files).map(f => f.name).join(', ');
            }
            appendMessage('user', displayMessage);

            messageInput.value = '';
            charCount.textContent = '0 / 10000';
            sendButton.disabled = true;
            sendButton.textContent = '送信中...';

            try {
                if (hasFiles) {
                    await handleFileUpload(message, mode, conversationId, fileInput);
                    fileInput.value = '';
                    document.getElementById('fileList').innerHTML = '';
                } else if (streamMode) {
                    await handleStreamingResponse(message, mode, conversationId);
                } else {
                    await handleNormalResponse(message, mode, conversationId);
                }
            } catch (error) {
                appendMessage('error', `エラー: ${error.message}`);
            } finally {
                sendButton.disabled = false;
                sendButton.textContent = '送信';
                messageInput.focus();
            }
        });

        // コードコピー
        function copyCode(id) {
            const codeElement = document.getElementById(id);
            const button = codeElement.previousElementSibling;
            const text = codeElement.textContent;

            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.textContent;
                button.textContent = '✓ コピー完了';
                button.classList.add('copied');

                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                console.error('コピー失敗:', err);
                alert('コピーに失敗しました');
            });
        }

        // お気に入りトグル
        async function toggleFavorite(conversationId, event) {
            event.preventDefault();
            event.stopPropagation();

            try {
                const response = await fetch(`/chat/conversation/${conversationId}/favorite`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                });

                if (response.ok) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // 会話削除
        async function deleteConversation(conversationId) {
            if (!confirm('この会話を削除しますか？')) return;

            try {
                const response = await fetch(`/chat/conversation/${conversationId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                });

                if (response.ok) {
                    window.location.href = '{{ route("chat.index") }}';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('削除に失敗しました');
            }
        }

        // タグメニュートグル
        function toggleTagMenu() {
            document.getElementById('tagMenu').classList.toggle('hidden');
        }

        // エクスポートメニュートグル
        function toggleExportMenu() {
            document.getElementById('exportMenu').classList.toggle('hidden');
        }

        // タグ変更
        async function handleTagChange(conversationId, tagId, isChecked) {
            const url = isChecked
                ? `/chat/conversation/${conversationId}/tag/attach`
                : `/chat/conversation/${conversationId}/tag/detach`;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ tag_id: tagId }),
                });

                if (!response.ok) {
                    throw new Error('タグの更新に失敗しました');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('タグの更新に失敗しました');
            }
        }

        // ヘッダーのお気に入りトグル
        async function toggleFavoriteHeader(conversationId) {
            try {
                const response = await fetch(`/chat/conversation/${conversationId}/favorite`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                });

                if (response.ok) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('お気に入りの更新に失敗しました');
            }
        }

        // ヘッダーの会話削除
        async function deleteConversationHeader(conversationId) {
            if (!confirm('この会話を削除しますか?\n\nこの操作は取り消せません。')) return;

            try {
                const response = await fetch(`/chat/conversation/${conversationId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                });

                if (response.ok) {
                    window.location.href = '{{ route("chat.index") }}';
                } else {
                    throw new Error('削除に失敗しました');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('削除に失敗しました');
            }
        }
    </script>
</body>
</html>
