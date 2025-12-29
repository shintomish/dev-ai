<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>開発支援AI - Claude Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- highlight.js 追加 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/bash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/python.min.js"></script>

    <style>
        .message-content pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 0.5rem 0;
        }
        .message-content code {
            background: #1e293b;
            color: #22d3ee;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-family: 'Courier New', monospace;
        }
        .message-content pre code {
            background: transparent;
            padding: 0;
        }
        .message-content pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 0.5rem 0;
            position: relative; /* 追加 */
        }

        /* コピーボタンのスタイル */
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
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- サイドバー（会話履歴） -->
        <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
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
                                    <div class="text-xs text-gray-400 mt-1">
                                        {{ $conv->mode === 'dev' ? '🔧 開発支援' : '📚 学習支援' }}
                                    </div>
                                </a>
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
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ $conv->mode === 'dev' ? '🔧 開発支援' : '📚 学習支援' }}
                                </div>
                            </a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">会話履歴がありません</p>
                    @endforelse
                </div>
            </div>
        </aside>

        <!-- メインコンテンツ -->
        <div class="flex-1 flex flex-col">
            <!-- ヘッダー -->
            <header class="bg-white shadow-sm border-b border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">🤖 開発支援AI</h1>
                        <p class="text-sm text-gray-600">
                            @if($conversation)
                                {{ $conversation->title ?? '会話中' }}
                                <!-- タグ表示 -->
                                <div class="flex gap-1 mt-2">
                                    @foreach($conversation->tags as $tag)
                                        <span class="px-2 py-1 text-xs rounded-full bg-{{ $tag->color }}-100 text-{{ $tag->color }}-800">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                    <button onclick="openTagModal()" class="px-2 py-1 text-xs text-blue-600 hover:bg-blue-50 rounded">
                                        + タグ
                                    </button>
                                </div>
                            @else
                                Laravel / Linux / Git / VBA 相談
                            @endif
                            </p>
                    </div>
                    <div class="flex gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="mode" value="dev" {{ !$conversation || $conversation->mode === 'dev' ? 'checked' : '' }} class="w-4 h-4" {{ $conversation ? 'disabled' : '' }}>
                            <span class="text-sm font-medium">開発支援</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="mode" value="study" {{ $conversation && $conversation->mode === 'study' ? 'checked' : '' }} class="w-4 h-4" {{ $conversation ? 'disabled' : '' }}>
                            <span class="text-sm font-medium">学習支援</span>
                        </label>
                        @if($conversation)
                            <div class="ml-4 relative">
                                <button onclick="toggleExportMenu()" class="px-3 py-1 text-sm text-blue-600 hover:bg-blue-50 rounded">
                                    ⬇️ エクスポート
                                </button>
                                <div id="exportMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                    <a href="{{ route('chat.export', ['conversation' => $conversation->id, 'format' => 'markdown']) }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg">
                                        📝 Markdown (.md)
                                    </a>
                                    <a href="{{ route('chat.export', ['conversation' => $conversation->id, 'format' => 'json']) }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        📊 JSON (.json)
                                    </a>
                                    <a href="{{ route('chat.export', ['conversation' => $conversation->id, 'format' => 'txt']) }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-lg">
                                        📄 テキスト (.txt)
                                    </a>
                                </div>
                            </div>
                            <button onclick="deleteConversation({{ $conversation->id }})" class="ml-2 px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded">
                                🗑️ 削除
                            </button>
                        @endif
                    </div>
                </div>
            </header>

            <!-- メッセージエリア -->
            <div id="messages" class="flex-1 overflow-y-auto p-4 space-y-4">
                @if(!$conversation || $messages->isEmpty())
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                        <p class="text-sm text-blue-800">
                            💡 <strong>使い方:</strong> 質問を入力して送信してください。エラーログやコードも貼り付けOKです！
                        </p>
                    </div>
                @else
                    @foreach($messages as $msg)
                        @if($msg->role === 'user')
                            <div class="flex justify-end">
                                <div class="bg-blue-600 text-white rounded-lg p-4 max-w-3xl">
                                    <div class="whitespace-pre-wrap">{{ $msg->content }}</div>
                                </div>
                            </div>
                        @else
                            <div class="flex gap-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                    AI
                                </div>
                                <div class="bg-white rounded-lg p-4 shadow-sm flex-1 message-content">
                                    {!! formatMarkdownWithCopyButton($msg->content) !!}
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>

            <!-- 入力エリア -->
            <div class="bg-white border-t border-gray-200 p-4">
                <form id="chatForm" class="space-y-3">
                    <input type="hidden" id="conversationId" value="{{ $conversation->id ?? '' }}">
                    <textarea
                        id="messageInput"
                        rows="4"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        placeholder="質問を入力してください（エラーログやコードも貼り付けOK）"
                        required
                    ></textarea>
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-500">
                            <span id="charCount">0</span> / 10000 文字
                        </div>
                        <button
                            type="submit"
                            id="sendButton"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                        >
                            送信
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const messagesDiv = document.getElementById('messages');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const charCount = document.getElementById('charCount');
        const conversationIdInput = document.getElementById('conversationId');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // 文字数カウント
        messageInput.addEventListener('input', () => {
            const length = messageInput.value.length;
            charCount.textContent = length;
            charCount.style.color = length > 10000 ? 'red' : '';
        });

        // メッセージ送信
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const message = messageInput.value.trim();
            if (!message) return;

            const mode = document.querySelector('input[name="mode"]:checked').value;
            const conversationId = conversationIdInput.value || null;

            // ユーザーメッセージを表示
            appendMessage('user', message);

            // 入力欄クリア & ボタン無効化
            messageInput.value = '';
            charCount.textContent = '0';
            sendButton.disabled = true;
            sendButton.textContent = '送信中...';

            // ローディング表示
            const loadingId = appendMessage('assistant', '考え中...', true);

            try {
                const response = await fetch('{{ route("chat.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ message, mode, conversation_id: conversationId }),
                });

                const data = await response.json();

                // ローディング削除
                document.getElementById(loadingId)?.remove();

                if (data.success) {
                    appendMessage('assistant', data.response);

                    // 会話IDを保存（新規会話の場合）
                    if (data.conversation_id && !conversationIdInput.value) {
                        conversationIdInput.value = data.conversation_id;
                        // URLを更新（履歴に残さない）
                        window.history.replaceState({}, '', `/chat?conversation=${data.conversation_id}`);
                        // サイドバーをリロード
                        setTimeout(() => location.reload(), 1000);
                    }

                    if (data.usage) {
                        console.log('API使用量:', data.usage);
                    }
                } else {
                    appendMessage('error', `エラー: ${data.error}`);
                }
            } catch (error) {
                document.getElementById(loadingId)?.remove();
                appendMessage('error', `通信エラー: ${error.message}`);
            } finally {
                sendButton.disabled = false;
                sendButton.textContent = '送信';
                messageInput.focus();
            }
        });

        // メッセージをDOMに追加
        function appendMessage(type, content, isLoading = false) {
            const id = 'msg-' + Date.now();
            const div = document.createElement('div');
            div.id = id;

            if (type === 'user') {
                div.innerHTML = `
                    <div class="flex justify-end">
                        <div class="bg-blue-600 text-white rounded-lg p-4 max-w-3xl">
                            <div class="whitespace-pre-wrap">${escapeHtml(content)}</div>
                        </div>
                    </div>
                `;
            } else if (type === 'assistant') {
                const formattedContent = isLoading ? content : formatResponse(content);
                div.innerHTML = `
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                            AI
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm flex-1 message-content">
                            ${formattedContent}
                        </div>
                    </div>
                `;
            } else if (type === 'error') {
                div.innerHTML = `
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                        <p class="text-red-800">${escapeHtml(content)}</p>
                    </div>
                `;
            }

            messagesDiv.appendChild(div);

            // シンタックスハイライトを適用
            if (type === 'assistant' && !isLoading) {
                div.querySelectorAll('pre code').forEach((block) => {
                    hljs.highlightElement(block);
                });
            }
            div.scrollIntoView({ behavior: 'smooth', block: 'end' });
            return id;
        }

        // レスポンスをフォーマット（コピーボタン付き）
        // レスポンスをフォーマット（シンタックスハイライト付き）
        function formatResponse(text) {
            // コードブロック（言語指定対応）
            let codeBlockId = 0;
            text = text.replace(/```(\w+)?\n([\s\S]*?)```/g, (match, lang, code) => {
                const id = `code-${Date.now()}-${codeBlockId++}`;
                const escapedCode = escapeHtml(code.trim());
                const langClass = lang ? `language-${lang}` : '';
                return `
                    <pre>
                        <button class="copy-button" onclick="copyCode('${id}')">コピー</button>
                        <code id="${id}" class="${langClass}">${escapedCode}</code>
                    </pre>
                `;
            });

            // インラインコード
            text = text.replace(/`([^`]+)`/g, '<code>$1</code>');

            // 改行
            text = text.replace(/\n/g, '<br>');

            return text;
        }

        // コードをコピー
        function copyCode(id) {
            const codeElement = document.getElementById(id);
            const button = codeElement.previousElementSibling;

            // コードを取得（HTMLエンティティをデコード）
            const text = codeElement.textContent;

            // クリップボードにコピー
            navigator.clipboard.writeText(text).then(() => {
                // ボタンの表示を変更
                const originalText = button.textContent;
                button.textContent = '✓ コピー完了';
                button.classList.add('copied');

                // 2秒後に元に戻す
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                console.error('コピーに失敗しました:', err);
                alert('コピーに失敗しました');
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 会話削除（改善版）
        async function deleteConversation(id) {
            // 会話のタイトルを取得
            const convElement = document.querySelector(`a[href*="conversation=${id}"]`);
            const title = convElement ? convElement.querySelector('.text-sm').textContent.trim() : '無題の会話';

            // 確認ダイアログ
            const confirmed = confirm(
                `会話を削除しますか？\n\n` +
                `タイトル: ${title}\n\n` +
                `この操作は取り消せません。`
            );

            if (!confirmed) return;

            try {
                const response = await fetch(`/chat/conversation/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const data = await response.json();
                if (data.success) {
                    // 削除成功メッセージ
                    const message = document.createElement('div');
                    message.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                    message.textContent = '✅ 会話を削除しました';
                    document.body.appendChild(message);

                    // 2秒後にメッセージを削除してリダイレクト
                    setTimeout(() => {
                        message.remove();
                        window.location.href = '{{ route("chat.index") }}';
                    }, 2000);
                }
            } catch (error) {
                alert('削除に失敗しました: ' + error.message);
            }
        }

        // エクスポートメニュー表示切替
        function toggleExportMenu() {
            const menu = document.getElementById('exportMenu');
            menu.classList.toggle('hidden');
        }

        // メニュー外クリックで閉じる
        document.addEventListener('click', (e) => {
            const menu = document.getElementById('exportMenu');
            if (menu && !e.target.closest('[onclick="toggleExportMenu()"]') && !e.target.closest('#exportMenu')) {
                menu.classList.add('hidden');
            }
        });

        // Enterキーで送信
        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.requestSubmit();
            }
        });

        // 初回ロード時に最下部にスクロール
        // 初回ロード時にシンタックスハイライトを適用
        window.addEventListener('load', () => {
            messagesDiv.scrollTop = messagesDiv.scrollHeight;

            // 既存のコードブロックにハイライトを適用
            document.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightElement(block);
            });
        });

        // お気に入りトグル（デバッグ版）
        async function toggleFavorite(id, event) {
            event.preventDefault();
            event.stopPropagation();

            console.log('クリックされた会話ID:', id);
            const button = event.currentTarget;

            try {
                const response = await fetch(`/chat/conversation/${id}/favorite`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                });

                const data = await response.json();
                console.log('APIレスポンス:', data);

                if (data.success) {
                    // ボタンの表示を即座に更新
                    if (data.is_favorite) {
                        button.textContent = '⭐';
                        button.title = 'お気に入り解除';
                        console.log('お気に入りに追加しました:', id);
                    } else {
                        button.textContent = '☆';
                        button.title = 'お気に入りに追加';
                        console.log('お気に入りから削除しました:', id);
                    }

                    // 1秒後にリロードして並び順を更新
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert('お気に入りの切替に失敗しました');
                }
            } catch (error) {
                console.error('お気に入り切替エラー:', error);
                alert('エラーが発生しました: ' + error.message);
            }
        }

        // タグモーダル
        function openTagModal() {
            document.getElementById('tagModal').classList.remove('hidden');
        }

        function closeTagModal() {
            document.getElementById('tagModal').classList.add('hidden');
        }

        function saveAndCloseTagModal() {
            closeTagModal();
            location.reload();
        }

        async function toggleTag(tagId, tagName) {
    alert('関数が呼ばれました！ タグ: ' + tagName);

    console.log('=== toggleTag 開始 ===');
    console.log('引数 - tagId:', tagId, 'tagName:', tagName);

            const conversationId = {{ $conversation->id ?? 'null' }};
            if (!conversationId) return;

            const button = document.getElementById(`tag-btn-${tagId}`);
            const hasTag = button.dataset.attached === 'true';

            console.log(`タグ ${tagName} (ID: ${tagId}):`, hasTag ? '削除' : '追加');

            try {
                const url = hasTag
                    ? `/chat/conversation/${conversationId}/tag/detach`
                    : `/chat/conversation/${conversationId}/tag/attach`;

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ tag_id: tagId }),
                });

                const data = await response.json();
                if (data.success) {
                    // data-attached を更新
                    button.dataset.attached = hasTag ? 'false' : 'true';

                    // 見た目を更新
                    if (hasTag) {
                        // タグ削除 → 白背景
                        button.className = 'px-3 py-1 text-sm rounded-full border-2 bg-white text-gray-700 border-gray-300 hover:shadow';
                    } else {
                        // タグ追加 → 色付き（仮で青色）
                        button.className = 'px-3 py-1 text-sm rounded-full border-2 bg-blue-100 text-blue-800 border-blue-300 hover:shadow';
                    }

                    console.log('✅ タグ更新成功');
                } else {
                    console.error('❌ タグ更新失敗:', data);
                }
            } catch (error) {
                console.error('❌ タグ切替エラー:', error);
            }
        }
    </script>

    <!-- タグ編集モーダル -->
    @if($conversation)
    <div id="tagModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-bold mb-4">タグを編集</h3>

            <div class="space-y-2 mb-4">
                <p class="text-sm text-gray-600">タグを選択してください</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($allTags as $tag)
                        @php
                            $isAttached = $conversation->tags->contains($tag->id);
                        @endphp
                        <button type="button"
                            onclick="alert('ボタンクリック: {{ $tag->name }}'); toggleTag({{ $tag->id }}, '{{ $tag->name }}');"
                            id="tag-btn-{{ $tag->id }}"
                            data-attached="{{ $isAttached ? 'true' : 'false' }}"
                            class="px-3 py-1 text-sm rounded-full border-2 {{ $isAttached ? 'bg-blue-100 text-blue-800 border-blue-300' : 'bg-white text-gray-700 border-gray-300' }} hover:shadow">
                            {{ $tag->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeTagModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">
                    キャンセル
                </button>
                <button type="button" onclick="saveAndCloseTagModal()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    完了
                </button>
            </div>
        </div>
    </div>
    @endif
</body>
</html>

@php
function formatMarkdownWithCopyButton($text) {
    static $codeBlockId = 0;

    // コードブロック（言語指定対応）
    $text = preg_replace_callback('/```(\w+)?\n([\s\S]*?)```/', function($matches) use (&$codeBlockId) {
        $lang = $matches[1] ?? '';
        $code = htmlspecialchars(trim($matches[2]));
        $id = 'code-' . time() . '-' . $codeBlockId++;
        $langClass = $lang ? "language-{$lang}" : '';
        return sprintf(
            '<pre><button class="copy-button" onclick="copyCode(\'%s\')">コピー</button><code id="%s" class="%s">%s</code></pre>',
            $id, $id, $langClass, $code
        );
    }, $text);

    // インラインコード
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

    // 改行
    $text = nl2br($text);

    return $text;
}
@endphp