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

    <!-- Marked.js for Markdown parsing -->
    <script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>

    <!-- DOMPurify for XSS protection -->
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>

    <!-- Mermaid for diagrams (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10.6.1/dist/mermaid.min.js"></script>

    <!-- Mermaid for diagrams -->
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10.6.1/dist/mermaid.min.js"></script>

    <!-- Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        /* ===== ダークモード用CSS変数 ===== */
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-tertiary: #f3f4f6;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --text-tertiary: #9ca3af;
            --border-color: #e5e7eb;
            --border-color-dark: #d1d5db;
            --user-msg-bg: #3b82f6;
            --user-msg-text: #ffffff;
            --ai-msg-bg: #ffffff;
            --ai-msg-text: #111827;
            --ai-msg-border: #e5e7eb;
            --code-bg: #1e293b;
            --code-text: #e2e8f0;
            --inline-code-bg: #1e293b;
            --inline-code-text: #22d3ee;
            --hover-bg: #f3f4f6;
            --error-bg: #fee2e2;
            --error-text: #991b1b;
            --error-border: #fca5a5;
            --loading-bg: #f3f4f6;
            --loading-text: #6b7280;
            --input-bg: #ffffff;
            --scrollbar-track: #f9fafb;
            --scrollbar-thumb: #d1d5db;
            --scrollbar-thumb-hover: #9ca3af;
        }

        [data-theme="dark"] {
            --bg-primary: #1f2937;
            --bg-secondary: #111827;
            --bg-tertiary: #374151;
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --text-tertiary: #9ca3af;
            --border-color: #374151;
            --border-color-dark: #4b5563;
            --user-msg-bg: #2563eb;
            --user-msg-text: #ffffff;
            --ai-msg-bg: #374151;
            --ai-msg-text: #f9fafb;
            --ai-msg-border: #4b5563;
            --code-bg: #0f172a;
            --code-text: #e2e8f0;
            --inline-code-bg: #0f172a;
            --inline-code-text: #22d3ee;
            --hover-bg: #374151;
            --error-bg: #7f1d1d;
            --error-text: #fca5a5;
            --error-border: #991b1b;
            --loading-bg: #374151;
            --loading-text: #9ca3af;
            --input-bg: #1f2937;
            --scrollbar-track: #111827;
            --scrollbar-thumb: #4b5563;
            --scrollbar-thumb-hover: #6b7280;
        }

        /* ===== マークダウンスタイル ===== */
        .markdown-content {
            line-height: 1.6;
        }

        .markdown-content h1,
        .markdown-content h2,
        .markdown-content h3,
        .markdown-content h4,
        .markdown-content h5,
        .markdown-content h6 {
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .markdown-content h1 {
            font-size: 1.875rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
        }

        .markdown-content h2 {
            font-size: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.5rem;
        }

        .markdown-content h3 {
            font-size: 1.25rem;
        }

        .markdown-content h4 {
            font-size: 1.125rem;
        }

        .markdown-content p {
            margin: 0.75rem 0;
        }

        .markdown-content a {
            color: #3b82f6;
            text-decoration: underline;
            word-break: break-all;
        }

        [data-theme="dark"] .markdown-content a {
            color: #60a5fa;
        }

        .markdown-content a:hover {
            color: #2563eb;
        }

        .markdown-content ul,
        .markdown-content ol {
            margin: 0.75rem 0;
            padding-left: 2rem;
        }

        .markdown-content li {
            margin: 0.25rem 0;
        }

        .markdown-content ul ul,
        .markdown-content ol ul,
        .markdown-content ul ol,
        .markdown-content ol ol {
            margin: 0.25rem 0;
        }

        /* テーブル */
        .markdown-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            overflow-x: auto;
            display: block;
        }

        .markdown-content table thead {
            background: var(--bg-tertiary);
        }

        .markdown-content table th,
        .markdown-content table td {
            border: 1px solid var(--border-color);
            padding: 0.5rem 0.75rem;
            text-align: left;
        }

        .markdown-content table th {
            font-weight: 600;
            color: var(--text-primary);
        }

        .markdown-content table tbody tr:hover {
            background: var(--hover-bg);
        }

        /* 引用 */
        .markdown-content blockquote {
            border-left: 4px solid #3b82f6;
            padding-left: 1rem;
            margin: 1rem 0;
            color: var(--text-secondary);
            font-style: italic;
        }

        [data-theme="dark"] .markdown-content blockquote {
            border-left-color: #60a5fa;
        }

        /* 水平線 */
        .markdown-content hr {
            border: none;
            border-top: 2px solid var(--border-color);
            margin: 1.5rem 0;
        }

        /* コードブロック（Markedで処理） */
        .markdown-content pre {
            background: var(--code-bg);
            color: var(--code-text);
            padding: 1rem;
            padding-top: 2.5rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 1rem 0;
            position: relative;
        }

        .markdown-content pre code {
            background: transparent;
            color: inherit;
            padding: 0;
        }

        /* インラインコード */
        .markdown-content code {
            background: var(--inline-code-bg);
            color: var(--inline-code-text);
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-family: 'Courier New', Consolas, Monaco, monospace;
            font-size: 0.9em;
        }

        .markdown-content pre code {
            background: transparent;
            padding: 0;
        }

        /* タスクリスト */
        .markdown-content input[type="checkbox"] {
            margin-right: 0.5rem;
        }

        .markdown-content .task-list-item {
            list-style: none;
        }

        .markdown-content .task-list-item input[type="checkbox"] {
            margin-left: -1.5rem;
        }

        /* Mermaid図表 */
        .mermaid {
            background: var(--bg-primary);
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }

        /* コピーボタン（既存） */
        .markdown-content .copy-button {
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

        .markdown-content pre:hover .copy-button {
            opacity: 1;
        }

        .markdown-content .copy-button:hover {
            background: #2563eb;
        }

        .markdown-content .copy-button.copied {
            background: #10b981;
        }

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
            background: var(--bg-secondary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
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
            background: var(--bg-primary);
            border-right: 1px solid var(--border-color);
        }

        aside::-webkit-scrollbar {
            width: 6px;
        }

        aside::-webkit-scrollbar-track {
            background: var(--scrollbar-track);
        }

        aside::-webkit-scrollbar-thumb {
            background: var(--scrollbar-thumb);
            border-radius: 3px;
        }

        aside::-webkit-scrollbar-thumb:hover {
            background: var(--scrollbar-thumb-hover);
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
            background: var(--bg-secondary);
            min-height: 0;
            max-width: 100% !important;
        }

        #chatMessages::-webkit-scrollbar {
            width: 8px;
        }

        #chatMessages::-webkit-scrollbar-track {
            background: var(--scrollbar-track);
        }

        #chatMessages::-webkit-scrollbar-thumb {
            background: var(--scrollbar-thumb);
            border-radius: 4px;
        }

        #chatMessages::-webkit-scrollbar-thumb:hover {
            background: var(--scrollbar-thumb-hover);
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
            background: var(--user-msg-bg);
            color: var(--user-msg-text);
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
            background: var(--ai-msg-bg);
            color: var(--ai-msg-text);
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            max-width: min(80%, 800px);
            border: 1px solid var(--ai-msg-border);
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        /* エラーメッセージ */
        .message.error .message-content {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-border);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            max-width: min(80%, 800px);
        }

        /* ローディング */
        .message.loading .message-content {
            background: var(--loading-bg);
            color: var(--loading-text);
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
            background: var(--code-bg);
            color: var(--code-text);
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
            background: var(--inline-code-bg);
            color: var(--inline-code-text);
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
            border-top: 1px solid var(--border-color);
            background: var(--bg-primary);
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
            background: var(--bg-tertiary);
            color: var(--text-primary);
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
            color: var(--text-primary);
        }
        .file-item button {
            color: #ef4444;
        }
        [data-theme="dark"] .file-item button {
            color: #f87171;
        }

        /* プレースホルダー */
        ::placeholder {
            color: var(--text-tertiary);
            opacity: 0.7;
        }

        [data-theme="dark"] ::placeholder {
            color: var(--text-tertiary);
            opacity: 0.5;
        }

        /* ===== その他 ===== */
        #charCount {
            flex-shrink: 0;
            white-space: nowrap;
        }

        .tag {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            background: var(--bg-tertiary);
            color: var(--text-primary);
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
            background-color: var(--hover-bg);
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
        /* タブボタンのスタイル */
        .tab-button {
            color: var(--text-secondary);
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            cursor: pointer;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
        }

        .tab-button.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
            font-weight: 600;
        }

        .tab-button:hover:not(.active) {
            color: var(--text-primary);
            background-color: var(--hover-bg);
        }

        /* タブコンテンツ */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* 検索結果のハイライト（ダークモード対応） */
        mark {
            background: #fef08a;
            color: #000000;
        }

        [data-theme="dark"] mark {
            background: #854d0e;
            color: #fef3c7;
        }

        /* フォーカス時のリング（ダークモード対応） */
        .focus\:ring-2:focus {
            --tw-ring-color: #3b82f6;
        }

        [data-theme="dark"] .focus\:ring-2:focus {
            --tw-ring-color: #2563eb;
        }
    </style>
</head>
<body>

    <!-- 緊急用: モーダル強制クローズボタン -->
    <div style="position: fixed; top: 10px; right: 10px; z-index: 9999;">
        <button onclick="forceCloseAllModals()"
                style="background: red; color: white; padding: 10px; border-radius: 5px; font-weight: bold; cursor: pointer; border: none;">
            <!-- 🚨 画面復旧 -->
        </button>
    </div>

    <div class="flex h-screen">
        <!-- サイドバー -->
        <aside class="w-80 bg-white border-r border-gray-200 flex flex-col" style="background: var(--bg-primary); border-color: var(--border-color);">
            <!-- 検索ボックス -->
            <div class="p-4 border-b border-gray-200" style="border-color: var(--border-color);">
                <div class="relative">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="会話を検索..."
                        class="w-full px-4 py-2 pl-10 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        style="background: var(--input-bg); color: var(--text-primary); border-color: var(--border-color);"
                        autocomplete="off"
                    >
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" style="color: var(--text-secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <button
                        id="clearSearch"
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 hidden"
                        style="color: var(--text-secondary);"
                        title="検索をクリア"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="searchResults" class="mt-2 text-sm text-gray-500 hidden" style="color: var(--text-secondary);">
                    <span id="resultCount">0</span> 件の会話が見つかりました
                </div>
            </div>

            <!-- トークン使用量統計 -->
                @if(isset($monthlyStats))
                <div class="p-4 border-b border-gray-200 cursor-pointer hover:bg-opacity-80 transition"
                    style="border-color: var(--border-color); background: var(--bg-tertiary);"
                    onclick="openStatsModal()">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-xs font-semibold uppercase" style="color: var(--text-secondary);">
                            📊 今月の使用量
                        </div>
                        <svg class="w-4 h-4" style="color: var(--text-secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <div class="space-y-1 text-sm" style="color: var(--text-primary);">
                        <div class="flex justify-between">
                            <span>トークン:</span>
                            <span class="font-mono">{{ number_format($monthlyStats['total_tokens'], 0, '.', ',') }}</span>
                        </div>
                        <div class="flex justify-between text-xs" style="color: var(--text-secondary);">
                            <span>入力:</span>
                            <span class="font-mono">{{ number_format($monthlyStats['input_tokens'], 0, '.', ',') }}</span>
                        </div>
                        <div class="flex justify-between text-xs" style="color: var(--text-secondary);">
                            <span>出力:</span>
                            <span class="font-mono">{{ number_format($monthlyStats['output_tokens'], 0, '.', ',') }}</span>
                        </div>
                        <div class="flex justify-between pt-1 border-t" style="border-color: var(--border-color);">
                            <span>コスト:</span>
                            <span class="font-mono font-semibold">¥{{ number_format($monthlyStats['cost_jpy'], 2, '.', ',') }}</span>
                        </div>
                        <div class="flex justify-between text-xs" style="color: var(--text-secondary);">
                            <span>メッセージ数:</span>
                            <span class="font-mono">{{ number_format($monthlyStats['message_count'], 0, '.', ',') }}</span>
                        </div>
                    </div>
                </div>
                @endif
            <!-- 新しい会話ボタン -->
                <div class="p-4 border-b border-gray-200" style="border-color: var(--border-color);">
                    <a href="{{ route('chat.new') }}" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                        <span>➕</span>
                        <span>新しい会話</span>
                    </a>
                </div>

            <!-- タブボタン -->
            <div class="flex border-b border-gray-200" style="border-color: var(--border-color);">
                <button class="tab-button active flex-1 px-4 py-2 text-sm font-medium" data-tab="recent">
                    最近
                </button>
                <button class="tab-button flex-1 px-4 py-2 text-sm font-medium" data-tab="favorites">
                    お気に入り
                </button>
            </div>

            <!-- タブコンテンツ -->
            <div class="flex-1 overflow-y-auto">
                <!-- 最近の会話タブ -->
                <div id="recent" class="tab-content active p-4">
                    <div id="conversationList1" class="space-y-2">
                        @forelse($recentConversations as $conv)
                            <div class="flex items-center gap-2">
                                <button onclick="toggleFavorite({{ $conv->id }}, event)"
                                        class="flex-shrink-0 text-xl hover:scale-110 transition-transform"
                                        title="お気に入りに追加">
                                    ☆
                                </button>
                                <a href="{{ route('chat.index', ['conversation' => $conv->id]) }}"
                                class="flex-1 block p-3 rounded-lg hover:bg-gray-100 {{ $conversation && $conversation->id === $conv->id ? 'bg-blue-50 border border-blue-200' : 'bg-white border border-gray-200' }}"
                                style="background: {{ $conversation && $conversation->id === $conv->id ? '#dbeafe' : 'var(--bg-primary)' }}; color: var(--text-primary); border-color: var(--border-color);">
                                    <div class="text-sm font-medium text-gray-900 truncate" style="color: var(--text-primary);">
                                        {{ $conv->title ?? '無題の会話' }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1 flex items-center justify-between" style="color: var(--text-secondary);">
                                        <span>{{ $conv->updated_at->diffForHumans() }}</span>
                                        @if($conv->total_tokens > 0)
                                            <span class="font-mono text-xs">{{ number_format($conv->total_tokens) }} tok</span>
                                        @endif
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
                            <p class="text-sm text-gray-500 text-center py-4" style="color: var(--text-secondary);">会話履歴がありません</p>
                        @endforelse
                    </div>
                </div>

                <!-- お気に入りタブ -->
                <div id="favorites" class="tab-content p-4">
                    <div id="favoriteConversationList" class="space-y-2">
                        @forelse($favoriteConversations as $conv)
                            <div class="flex items-center gap-2">
                                <button onclick="toggleFavorite({{ $conv->id }}, event)"
                                        class="flex-shrink-0 text-xl hover:scale-110 transition-transform"
                                        title="お気に入り解除">
                                    ⭐
                                </button>
                                <a href="{{ route('chat.index', ['conversation' => $conv->id]) }}"
                                class="flex-1 block p-3 rounded-lg hover:bg-gray-100 {{ $conversation && $conversation->id === $conv->id ? 'bg-blue-50 border border-blue-200' : 'bg-white border border-gray-200' }}"
                                style="background: {{ $conversation && $conversation->id === $conv->id ? '#dbeafe' : 'var(--bg-primary)' }}; color: var(--text-primary); border-color: var(--border-color);">
                                    <div class="text-sm font-medium text-gray-900 truncate" style="color: var(--text-primary);">
                                        {{ $conv->title ?? '無題の会話' }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1 flex items-center justify-between" style="color: var(--text-secondary);">
                                        <span>{{ $conv->updated_at->diffForHumans() }}</span>
                                        @if($conv->total_tokens > 0)
                                            <span class="font-mono text-xs">{{ number_format($conv->total_tokens) }} tok</span>
                                        @endif
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
                            <p class="text-sm text-gray-500 text-center py-4" style="color: var(--text-secondary);">お気に入りはありません</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </aside>

        <!-- メインチャットエリア -->
        <main>
            <!-- ヘッダー -->
            <div class="border-b border-gray-200 bg-white p-4" style="background: var(--bg-primary); border-color: var(--border-color);">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <h1 class="text-xl font-bold text-gray-900" style="color: var(--text-primary);">
                            {{ $conversation ? $conversation->title : '新しい会話' }}
                        </h1>
                        @if($conversation && $conversation->total_tokens > 0)
                            <div class="text-xs mt-1" style="color: var(--text-secondary);">
                                📊 {{ number_format($conversation->total_tokens) }} tokens
                                (入力: {{ number_format($conversation->input_tokens) }} / 出力: {{ number_format($conversation->output_tokens) }})
                                · ¥{{ number_format($conversation->cost_jpy, 2) }}
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- ダークモード切り替えボタン -->
                        <button onclick="toggleDarkMode()"
                                id="darkModeToggle"
                                class="px-3 py-1 text-xl hover:scale-110 transition-transform"
                                title="ダークモード切り替え">
                            🌙
                        </button>

                        <!-- ユーザー情報とログアウト -->
                        @auth
                        <div class="flex items-center gap-2 ml-4 pl-4 border-l" style="border-color: var(--border-color);">
                            <span class="text-sm" style="color: var(--text-primary);">
                                👤 {{ auth()->user()->name }}
                                <span class="text-xs" style="color: var(--text-secondary);">(ID: {{ auth()->id() }})</span>
                            </span>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50"
                                        style="background: var(--bg-primary); color: var(--text-primary); border-color: var(--border-color);">
                                    ログアウト
                                </button>
                            </form>
                        </div>
                        @endauth

                        @if($conversation)
                            <!-- お気に入りトグル -->
                            <button onclick="toggleFavoriteHeader({{ $conversation->id }})"
                                    class="px-3 py-1 text-xl hover:scale-110 transition-transform"
                                    title="{{ $conversation->is_favorite ? 'お気に入り解除' : 'お気に入りに追加' }}">
                                {{ $conversation->is_favorite ? '⭐' : '☆' }}
                            </button>

                            <!-- タグ管理 -->
                            <div class="relative">
                                <button onclick="toggleTagMenu()" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50" style="background: var(--bg-primary); color: var(--text-primary); border-color: var(--border-color);">
                                    🏷️ タグ
                                </button>
                                <div id="tagMenu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-50" style="background: var(--bg-primary); border-color: var(--border-color);">
                                    <div class="p-3">
                                        <input type="text" id="newTagInput" placeholder="新しいタグ..." class="w-full px-3 py-1 text-sm border border-gray-300 rounded mb-2" style="background: var(--input-bg); color: var(--text-primary); border-color: var(--border-color);">
                                        <button onclick="addNewTag()" class="w-full px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">追加</button>
                                    </div>
                                    <div class="p-2 max-h-60 overflow-y-auto border-t" style="border-color: var(--border-color);">
                                        @foreach($allTags as $tag)
                                            <label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer" style="color: var(--text-primary);">
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
                                <button onclick="toggleExportMenu()" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50" style="background: var(--bg-primary); color: var(--text-primary); border-color: var(--border-color);">
                                    📥 エクスポート
                                </button>
                                <div id="exportMenu" class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-50" style="background: var(--bg-primary); border-color: var(--border-color);">
                                    <a href="{{ route('conversations.export', ['conversation' => $conversation->id, 'format' => 'markdown']) }}"
                                    class="block px-4 py-2 text-sm hover:bg-gray-50 rounded-t-lg" style="color: var(--text-primary);">
                                        📝 Markdown
                                    </a>
                                    <a href="{{ route('conversations.export', ['conversation' => $conversation->id, 'format' => 'json']) }}"
                                    class="block px-4 py-2 text-sm hover:bg-gray-50" style="color: var(--text-primary);">
                                        📊 JSON
                                    </a>
                                    <a href="{{ route('conversations.export', ['conversation' => $conversation->id, 'format' => 'txt']) }}"
                                    class="block px-4 py-2 text-sm hover:bg-gray-50 rounded-b-lg" style="color: var(--text-primary);">
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
                        @endif
                    </div>
                </div>
            </div>
            <!-- チャットメッセージ -->
            <div id="chatMessages">
                @foreach($messages as $message)
                    <div class="message {{ $message->role }}">
                        <div class="message-content">
                            {!! nl2br(e($message->content)) !!}

                            @if($message->attachments->count() > 0)
                                <div class="mt-3 pt-3 border-t" style="border-color: var(--border-color);">
                                    <div class="text-sm font-semibold mb-2" style="color: var(--text-secondary);">📎 添付ファイル</div>
                                    <div class="space-y-2">
                                        @foreach($message->attachments as $attachment)
                                            @if($attachment->isImage())
                                                <!-- 画像サムネイル -->
                                                <div class="flex items-start gap-2 p-2 rounded" style="background: var(--bg-tertiary);">
                                                    <img src="{{ $attachment->public_url }}"
                                                        alt="{{ $attachment->original_filename }}"
                                                        class="w-32 h-32 object-cover rounded cursor-pointer hover:opacity-80 transition"
                                                        style="border: 1px solid var(--border-color);"
                                                        onclick="window.open('{{ $attachment->public_url }}', '_blank')">
                                                    <div class="flex-1 min-w-0">
                                                        <div class="text-sm font-medium" style="color: var(--text-primary);">{{ $attachment->original_filename }}</div>
                                                        <div class="text-xs" style="color: var(--text-secondary);">{{ $attachment->human_readable_size }}</div>
                                                    </div>
                                                </div>
                                            @else
                                                <!-- テキストファイル -->
                                                <div class="flex items-center gap-2 p-2 rounded" style="background: var(--bg-tertiary);">
                                                    <span>📄</span>
                                                    <span class="text-sm truncate flex-1" style="color: var(--text-primary);">{{ $attachment->original_filename }}</span>
                                                    <span class="text-xs" style="color: var(--text-secondary);">({{ $attachment->human_readable_size }})</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- 入力フォーム -->
            <form id="chatForm" style="background: var(--bg-primary); border-color: var(--border-color);">
                <input type="hidden" name="conversation_id" id="conversationId" value="{{ $conversation->id ?? '' }}">

                <!-- モード選択 -->
                <div class="flex items-center gap-4 mb-3">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="mode" value="dev" {{ !$conversation || $conversation->mode === 'dev' ? 'checked' : '' }}
                            class="text-blue-600" {{ $conversation ? 'disabled' : '' }}>
                        <span class="text-sm font-medium" style="color: var(--text-primary);">🔧 開発支援</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="mode" value="study" {{ $conversation && $conversation->mode === 'study' ? 'checked' : '' }}
                            class="text-green-600" {{ $conversation ? 'disabled' : '' }}>
                        <span class="text-sm font-medium" style="color: var(--text-primary);">📚 学習支援</span>
                    </label>
                    <label class="flex items-center gap-2 ml-auto">
                        <input type="checkbox" id="streamMode" class="rounded">
                        <span class="text-sm" style="color: var(--text-primary);">⚡ ストリーミング</span>
                    </label>
                </div>

                <!-- ファイルアップロード -->
                <div class="mb-3">
                    <input type="file" id="fileInput" name="files[]" multiple class="hidden"
                        accept=".txt,.log,.php,.js,.py,.java,.cpp,.h,.md,.json,.xml,.yaml,.yml,.png,.jpg,.jpeg,.gif,.webp">
                    <button type="button" onclick="document.getElementById('fileInput').click()"
                            class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50"
                            style="background: var(--bg-primary); color: var(--text-primary); border-color: var(--border-color);">
                        📎 ファイルを添付（テキスト・画像）
                    </button>
                    <!-- ファイルリスト表示エリア -->
                    <div id="fileList" style="background: var(--bg-tertiary); color: var(--text-primary);"></div>
                </div>

                <!-- メッセージ入力 -->
                <div class="flex gap-2">
                    <textarea id="messageInput"
                            name="message"
                            placeholder="メッセージを入力..."
                            rows="3"
                            maxlength="10000"
                            class="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                            style="background: var(--input-bg); color: var(--text-primary); border-color: var(--border-color);"
                            required></textarea>
                    <div class="flex flex-col gap-2">
                        <button type="submit" id="sendButton"
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                            送信
                        </button>
                        <span id="charCount" class="text-xs text-gray-500 text-center" style="color: var(--text-secondary);">0 / 10000</span>
                    </div>
                </div>
            </form>

        </main>
    </div>

    <script>
        // ========== 緊急用: すべてのモーダルを強制的に閉じる ==========
        function forceCloseAllModals() {
            console.log('モーダルを強制クローズ中...');

            // すべてのモーダルを閉じる
            const modals = ['statsModal', 'tagMenu', 'exportMenu'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('hidden');
                    console.log('✓ ' + modalId + ' を閉じました');
                }
            });

            // 固定オーバーレイを削除
            document.querySelectorAll('.fixed.inset-0').forEach(el => {
                if (el.classList.contains('bg-black') || el.classList.contains('bg-opacity-50')) {
                    el.classList.add('hidden');
                    console.log('✓ オーバーレイを削除しました');
                }
            });

            // グラフを破棄
            if (typeof tokenChart !== 'undefined' && tokenChart) {
                tokenChart.destroy();
                tokenChart = null;
                console.log('✓ グラフを破棄しました');
            }

            alert('画面を復旧しました！');
        }

        // ========== ダークモード機能 ==========
        (function() {
            // ページ読み込み時に保存された設定を適用
            const savedTheme = localStorage.getItem('theme') || 'light';
            applyTheme(savedTheme);
        })();

        function toggleDarkMode() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            applyTheme(newTheme);
            localStorage.setItem('theme', newTheme);
        }

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);

            // ボタンのアイコンを変更
            const toggleButton = document.getElementById('darkModeToggle');
            if (toggleButton) {
                toggleButton.textContent = theme === 'dark' ? '☀️' : '🌙';
                toggleButton.title = theme === 'dark' ? 'ライトモード' : 'ダークモード';
            }

            console.log('テーマ変更:', theme);
        }

        // ========== タブ切り替え機能 ==========
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const tabName = button.dataset.tab;

                    // すべてのタブボタンとコンテンツから active を削除
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // クリックされたタブを active に
                    button.classList.add('active');
                    document.getElementById(tabName).classList.add('active');
                });
            });
        });

        // ========== ダークモード機能 ==========
        document.addEventListener('DOMContentLoaded', function() {
            // ローカルストレージから設定を読み込み
            const savedTheme = localStorage.getItem('theme') || 'light';
            applyTheme(savedTheme);
        });

        function toggleDarkMode() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            applyTheme(newTheme);
            localStorage.setItem('theme', newTheme);
        }

        // ダークモード切り替え時のMermaidテーマ変更
        // ダークモード切り替え時にグラフを再描画
        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);

            // ボタンのアイコンを変更
            const toggleButton = document.getElementById('darkModeToggle');
            if (toggleButton) {
                toggleButton.textContent = theme === 'dark' ? '☀️' : '🌙';
                toggleButton.title = theme === 'dark' ? 'ライトモード' : 'ダークモード';
            }

            // Mermaidのテーマを変更
            mermaid.initialize({
                startOnLoad: false,
                theme: theme === 'dark' ? 'dark' : 'default'
            });

            // 既存のMermaid図表を再レンダリング
            document.querySelectorAll('.mermaid').forEach((element) => {
                const originalContent = element.textContent;
                element.removeAttribute('data-processed');
                element.innerHTML = originalContent;
                mermaid.init(undefined, element);
            });

            // グラフが表示中なら再描画
            if (tokenChart) {
                const modal = document.getElementById('statsModal');
                if (!modal.classList.contains('hidden')) {
                    // 現在のデータを保持して再描画
                    const currentData = tokenChart.data;
                    tokenChart.destroy();
                    // 再描画は次回openStatsModalで行う
                }
            }

            console.log('テーマ変更:', theme);
        }

        // ========== 定数 ==========
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const conversationIdInput = document.getElementById('conversationId');
        const charCount = document.getElementById('charCount');
        const fileInput = document.getElementById('fileInput');

        // ========== 検索機能 ==========
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');
        const searchResults = document.getElementById('searchResults');
        const resultCount = document.getElementById('resultCount');
        let searchTimeout;

        // 検索実行
        async function performSearch(query) {
            console.log('検索実行:', query);

            try {
                const url = `/conversations/search?q=${encodeURIComponent(query)}`;
                const response = await fetch(url);
                const data = await response.json();

                console.log('検索結果:', data);

                // 検索結果を表示
                displaySearchResults(data.conversations, query);

                // 結果数を表示
                if (query) {
                    resultCount.textContent = data.conversations.length;
                    searchResults.classList.remove('hidden');
                } else {
                    searchResults.classList.add('hidden');
                }
            } catch (error) {
                console.error('検索エラー:', error);
            }
        }

        // 検索結果を表示
        function displaySearchResults(conversations, query) {
            const conversationList = document.getElementById('recentConversationList');
            const favoritesList = document.getElementById('favoritesList');

            if (!conversationList) {
                console.error('recentConversationList  が見つかりません');
                return;
            }

            // 検索中でない場合は何もしない（ページリロードで元に戻す）
            if (!query) {
                location.reload();
                return;
            }

            // 検索結果を お気に入り と 最近 に分ける
            const favoriteResults = conversations.filter(conv => conv.is_favorite);
            const recentResults = conversations.filter(conv => !conv.is_favorite);

            // 結果が0件の場合
            if (conversations.length === 0) {
                conversationList.innerHTML = `
                    <div class="p-8 text-center text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="font-medium">会話が見つかりませんでした</p>
                        <p class="text-sm mt-1">別のキーワードで検索してみてください</p>
                    </div>
                `;
                if (favoritesList) {
                    favoritesList.innerHTML = `
                        <div class="p-4 text-center text-gray-500">
                            <p class="text-sm">検索結果なし</p>
                        </div>
                    `;
                }
                return;
            }

            // 最近の会話リストを更新
            conversationList.innerHTML = renderConversationItems(recentResults, query);

            // お気に入りリストを更新
            if (favoritesList) {
                if (favoriteResults.length > 0) {
                    favoritesList.innerHTML = renderConversationItems(favoriteResults, query);
                } else {
                    favoritesList.innerHTML = `
                        <div class="p-4 text-center text-gray-500">
                            <p class="text-sm">検索結果なし</p>
                        </div>
                    `;
                }
            }
        }

        // 会話アイテムのHTMLを生成
        function renderConversationItems(conversations, query) {
            if (conversations.length === 0) {
                return `
                    <div class="p-4 text-center text-gray-500">
                        <p class="text-sm">検索結果なし</p>
                    </div>
                `;
            }

            let html = '';
            conversations.forEach(conv => {
                let title = conv.title;
                if (query && conv.highlight) {
                    const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
                    title = title.replace(regex, '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
                }

                const tagsHtml = conv.tags.length > 0
                    ? `<div class="flex flex-wrap gap-1 mt-2">
                        ${conv.tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
                    </div>`
                    : '';

                html += `
                    <div class="flex items-center gap-2">
                        <button onclick="toggleFavorite(${conv.id}, event)"
                                class="flex-shrink-0 text-xl hover:scale-110 transition-transform"
                                title="${conv.is_favorite ? 'お気に入り解除' : 'お気に入りに追加'}">
                            ${conv.is_favorite ? '⭐' : '☆'}
                        </button>
                        <a href="/chat?conversation=${conv.id}"
                        class="flex-1 block p-3 rounded-lg hover:bg-gray-100 bg-white border border-gray-200">
                            <div class="text-sm font-medium text-gray-900 truncate">${title}</div>
                            <div class="text-xs text-gray-500 mt-1">${conv.updated_at}</div>
                            ${tagsHtml}
                        </a>
                        <button onclick="deleteConversation(${conv.id})"
                                class="flex-shrink-0 text-red-500 hover:text-red-700 p-1"
                                title="削除">
                            🗑️
                        </button>
                    </div>
                `;
            });

            return html;
        }

        // 正規表現のエスケープ
        function escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        // 入力時の検索
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();

            if (query) {
                clearSearch.classList.remove('hidden');
            } else {
                clearSearch.classList.add('hidden');
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });

        // Enterキーで検索
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                performSearch(e.target.value.trim());
            }
        });

        // クリアボタン
        clearSearch.addEventListener('click', () => {
            searchInput.value = '';
            clearSearch.classList.add('hidden');
            searchResults.classList.add('hidden');
            performSearch('');
        });

        // ========== 以下、既存のJavaScript（省略せず全て含める） ==========

        // ページ読み込み時の処理
        document.addEventListener('DOMContentLoaded', function() {
            // Mermaidの初期化
            mermaid.initialize({
                startOnLoad: false,
                theme: document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'default'
            });

            // 既存メッセージのフォーマット
            document.querySelectorAll('.message-content').forEach(element => {
                if (!element.classList.contains('formatted')) {
                    element.classList.add('markdown-content');  // マークダウンクラス追加
                    const formattedContent = formatResponse(element.textContent);
                    element.innerHTML = formattedContent;
                    element.classList.add('formatted');

                    // コードブロックのハイライト
                    element.querySelectorAll('pre code').forEach((block) => {
                        if (!block.classList.contains('hljs')) {
                            hljs.highlightBlock(block);
                        }
                    });

                    // Mermaid図表のレンダリング
                    element.querySelectorAll('.mermaid').forEach((mermaidElement) => {
                        mermaid.init(undefined, mermaidElement);
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

        // ファイル選択（画像プレビュー対応）
        fileInput.addEventListener('change', function() {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';

            if (this.files.length > 0) {
                Array.from(this.files).forEach(file => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';

                    const isImage = file.type.startsWith('image/');

                    if (isImage) {
                        // 画像の場合はプレビューを表示
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            fileItem.innerHTML = `
                                <div class="flex items-start gap-2 w-full p-2 rounded" style="background: var(--bg-primary); border: 1px solid var(--border-color);">
                                    <img src="${e.target.result}" alt="${file.name}" class="w-20 h-20 object-cover rounded" style="border: 1px solid var(--border-color);">
                                    <div class="flex-1 min-w-0">
                                        <div style="color: var(--text-primary);" class="text-sm font-medium">🖼️ ${file.name}</div>
                                        <div style="color: var(--text-secondary);" class="text-xs">${formatFileSize(file.size)}</div>
                                    </div>
                                    <button type="button" onclick="removeFile('${file.name}')" class="text-red-500 hover:text-red-700 flex-shrink-0">✕</button>
                                </div>
                            `;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        // テキストファイルの場合
                        fileItem.innerHTML = `
                            <div class="flex items-center justify-between w-full p-2 rounded" style="background: var(--bg-primary); border: 1px solid var(--border-color);">
                                <span style="color: var(--text-primary);">📄 ${file.name} (${formatFileSize(file.size)})</span>
                                <button type="button" onclick="removeFile('${file.name}')" class="text-red-500 hover:text-red-700">✕</button>
                            </div>
                        `;
                    }

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
            contentDiv.className = 'message-content markdown-content';  // markdown-content クラスを追加

            if (role === 'assistant' && !isLoading) {
                contentDiv.innerHTML = formatResponse(content);
                messageDiv.appendChild(contentDiv);
                chatMessages.appendChild(messageDiv);

                // コードブロックのハイライト
                contentDiv.querySelectorAll('pre code').forEach((block) => {
                    if (!block.classList.contains('hljs')) {
                        hljs.highlightBlock(block);
                    }
                });

                // Mermaid図表のレンダリング
                contentDiv.querySelectorAll('.mermaid').forEach((element) => {
                    mermaid.init(undefined, element);
                });
            } else {
                contentDiv.textContent = content;
                messageDiv.appendChild(contentDiv);
                chatMessages.appendChild(messageDiv);
            }

            chatMessages.scrollTop = chatMessages.scrollHeight;
            return messageId;
        }

        // レスポンスフォーマット（Marked.js使用）
        function formatResponse(text) {
            // Marked.js の設定
            marked.setOptions({
                breaks: true,
                gfm: true,
                headerIds: false,
                mangle: false,
                sanitize: false,
            });

            // カスタムレンダラー
            const renderer = new marked.Renderer();

            // コードブロックのレンダリング
            renderer.code = function(code, language) {
                const validLanguage = language && hljs.getLanguage(language) ? language : 'plaintext';
                const highlighted = hljs.highlight(code, { language: validLanguage }).value;
                const codeId = 'code-' + Math.random().toString(36).substr(2, 9);

                return `<pre><button class="copy-button" onclick="copyCode('${codeId}')">📋 コピー</button><code id="${codeId}" class="hljs language-${validLanguage}">${highlighted}</code></pre>`;
            };

            // インラインコード
            renderer.codespan = function(code) {
                return `<code>${escapeHtml(code)}</code>`;
            };

            // リンク
            renderer.link = function(href, title, text) {
                const titleAttr = title ? ` title="${escapeHtml(title)}"` : '';
                return `<a href="${escapeHtml(href)}" target="_blank" rel="noopener noreferrer"${titleAttr}>${text}</a>`;
            };

            // テーブル
            renderer.table = function(header, body) {
                return `<table class="w-full"><thead>${header}</thead><tbody>${body}</tbody></table>`;
            };

            // 画像
            renderer.image = function(href, title, text) {
                const titleAttr = title ? ` title="${escapeHtml(title)}"` : '';
                const altAttr = text ? ` alt="${escapeHtml(text)}"` : '';
                return `<img src="${escapeHtml(href)}"${altAttr}${titleAttr} style="max-width: 100%; height: auto; border-radius: 0.5rem; margin: 1rem 0;">`;
            };

            // タスクリスト
            renderer.listitem = function(text, task, checked) {
                if (task) {
                    const checkbox = checked ? '<input type="checkbox" checked disabled>' : '<input type="checkbox" disabled>';
                    return `<li class="task-list-item">${checkbox} ${text}</li>`;
                }
                return `<li>${text}</li>`;
            };

            marked.use({ renderer });

            // Mermaid記法の検出と処理
            let html = text;
            const mermaidRegex = /```mermaid\n([\s\S]*?)```/g;
            let mermaidCounter = 0;

            html = html.replace(mermaidRegex, (match, code) => {
                mermaidCounter++;
                const id = `mermaid-${Date.now()}-${mermaidCounter}`;
                // Mermaidは後でレンダリング
                return `<div class="mermaid" id="${id}">${escapeHtml(code.trim())}</div>`;
            });

            // Markedでパース
            html = marked.parse(html);

            // DOMPurifyでサニタイズ（XSS対策）
            html = DOMPurify.sanitize(html, {
                ALLOWED_TAGS: ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'br', 'strong', 'em', 'u', 's',
                            'a', 'ul', 'ol', 'li', 'blockquote', 'code', 'pre', 'table', 'thead',
                            'tbody', 'tr', 'th', 'td', 'hr', 'img', 'div', 'span', 'input', 'button'],
                ALLOWED_ATTR: ['href', 'title', 'target', 'rel', 'src', 'alt', 'class', 'id', 'type',
                            'checked', 'disabled', 'onclick', 'style'],
            });

            return html;
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
                const response = await fetch(`/conversations/${conversationId}/favorite`, {
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
                const response = await fetch(`/conversations/${conversationId}`, {
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

        // 新しいタグを追加
        async function addNewTag() {
            const input = document.getElementById('newTagInput');
            const tagName = input.value.trim();

            if (!tagName) return;

            @if($conversation)
            try {
                const response = await fetch(`/conversations/{{ $conversation->id }}/tags`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tags: [...@json($conversation->tags->pluck('name')), tagName]
                    }),
                });

                if (response.ok) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('タグの追加に失敗しました');
            }
            @endif
        }

        // タグ変更（チェックボックスのトグル）
        async function handleTagChange(conversationId, tagId, isChecked) {
            console.log('タグ変更:', conversationId, tagId, isChecked);

            try {
                // 現在の会話のすべてのタグを取得
                const checkboxes = document.querySelectorAll('#tagMenu input[type="checkbox"]');
                const selectedTags = [];

                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        // チェックされているタグのIDを取得
                        const tagId = checkbox.value;
                        // タグ名を取得（labelのテキスト）
                        const tagName = checkbox.parentElement.querySelector('span').textContent.trim();
                        selectedTags.push(tagName);
                    }
                });

                console.log('選択されたタグ:', selectedTags);

                // タグを更新
                const response = await fetch(`/conversations/${conversationId}/tags`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tags: selectedTags
                    }),
                });

                if (!response.ok) {
                    throw new Error('タグの更新に失敗しました');
                }

                const data = await response.json();
                console.log('タグ更新成功:', data);

                // 成功したら少し待ってからリロード
                setTimeout(() => {
                    location.reload();
                }, 300);

            } catch (error) {
                console.error('タグ変更エラー:', error);
                alert('タグの更新に失敗しました: ' + error.message);
            }
        }

        // ヘッダーのお気に入りトグル
        async function toggleFavoriteHeader(conversationId) {
            try {
                const response = await fetch(`/conversations/${conversationId}/favorite`, {
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
                const response = await fetch(`/conversations/${conversationId}`, {
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

        // ========== トークン使用量統計モーダル ==========
        var tokenChart = null;

        // カンマ区切り用のヘルパー関数
        function formatNumber(num) {
            if (num === null || num === undefined) return '0';
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        async function openStatsModal() {
            const modal = document.getElementById('statsModal');
            const loading = document.getElementById('statsLoading');
            const content = document.getElementById('statsContent');

            modal.classList.remove('hidden');
            loading.classList.remove('hidden');
            content.classList.add('hidden');

            try {
                const response = await fetch('/stats/tokens/detailed');
                const data = await response.json();

                console.log('統計データ:', data);

                // サマリー更新（手動カンマ区切り）
                document.getElementById('totalTokens').textContent = formatNumber(data.monthly.total_tokens);
                document.getElementById('totalCost').textContent = '¥' + data.monthly.cost_jpy.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                document.getElementById('totalMessages').textContent = formatNumber(data.monthly.message_count);

                // グラフ描画
                renderTokenChart(data.daily);

                // 会話リスト表示
                renderConversationList(data.conversations);

                loading.classList.add('hidden');
                content.classList.remove('hidden');
            } catch (error) {
                console.error('統計の読み込みエラー:', error);
                alert('統計の読み込みに失敗しました: ' + error.message);
                closeStatsModal();
            }
        }

        function closeStatsModal(event) {
            if (event && event.target.id !== 'statsModal') return;

            const modal = document.getElementById('statsModal');
            modal.classList.add('hidden');

            // グラフを破棄
            if (tokenChart) {
                tokenChart.destroy();
                tokenChart = null;
            }
        }

        function renderTokenChart(dailyData) {
            const ctx = document.getElementById('tokenChart');

            if (!ctx) {
                console.error('Canvas element not found');
                return;
            }

            // 既存のグラフを破棄
            if (tokenChart) {
                tokenChart.destroy();
                tokenChart = null;
            }

            // データが空の場合
            if (!dailyData || dailyData.length === 0) {
                console.log('日別データが空です');
                ctx.parentElement.innerHTML = '<p class="text-center py-8" style="color: var(--text-secondary);">まだデータがありません</p>';
                return;
            }

            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const textColor = isDark ? '#f9fafb' : '#111827';
            const gridColor = isDark ? '#374151' : '#e5e7eb';

            try {
                tokenChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: dailyData.map(d => {
                            const date = new Date(d.date);
                            return (date.getMonth() + 1) + '/' + date.getDate();
                        }),
                        datasets: [
                            {
                                label: '入力トークン',
                                data: dailyData.map(d => d.input_tokens || 0),
                                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 1
                            },
                            {
                                label: '出力トークン',
                                data: dailyData.map(d => d.output_tokens || 0),
                                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                                borderColor: 'rgba(16, 185, 129, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            x: {
                                stacked: true,
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    color: textColor,
                                    callback: function(value) {
                                        return formatNumber(value);
                                    }
                                },
                                grid: { color: gridColor }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: { color: textColor }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + formatNumber(context.parsed.y) + ' tokens';
                                    },
                                    footer: function(tooltipItems) {
                                        const index = tooltipItems[0].dataIndex;
                                        const data = dailyData[index];
                                        return '合計: ' + formatNumber(data.total_tokens || 0) + ' tokens\n' +
                                            'コスト: ¥' + (data.cost_jpy || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                    }
                                }
                            }
                        }
                    }
                });

                console.log('グラフ描画完了');
            } catch (error) {
                console.error('グラフ描画エラー:', error);
            }
        }

        function renderConversationList(conversations) {
            const listContainer = document.getElementById('conversationList');

            console.log('conversationList要素:', listContainer);  // デバッグ
            console.log('親要素:', listContainer?.parentElement?.id);  // デバッグ

            if (!listContainer) {
                console.error('会話リストコンテナが見つかりません');
                return;
            }

            if (!conversations || conversations.length === 0) {
                listContainer.innerHTML = '<p style="color: var(--text-secondary);" class="text-center py-4">データがありません</p>';
                return;
            }

            listContainer.innerHTML = conversations.map((conv, index) => `
                <div class="p-4 rounded-lg hover:bg-opacity-80 transition" style="background: var(--bg-tertiary);">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-lg font-bold" style="color: var(--text-secondary);">#${index + 1}</span>
                                <a href="/chat?conversation=${conv.id}" class="text-sm font-medium hover:underline truncate" style="color: var(--text-primary);" onclick="closeStatsModal()">
                                    ${conv.title || '無題の会話'}
                                </a>
                            </div>
                            <div class="flex gap-4 mt-2 text-xs" style="color: var(--text-secondary);">
                                <span>📊 ${formatNumber(conv.total_tokens || 0)} tokens</span>
                                <span>💬 ${conv.message_count || 0} メッセージ</span>
                            </div>
                        </div>
                        <div class="text-right ml-4">
                            <div class="text-lg font-bold" style="color: var(--text-primary);">¥${(conv.cost_jpy || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}</div>
                            <div class="text-xs" style="color: var(--text-secondary);">
                                入力: ${formatNumber(conv.input_tokens || 0)}<br>
                                出力: ${formatNumber(conv.output_tokens || 0)}
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');

            console.log('会話リスト描画完了');
        }

        // Escキーでモーダルを閉じる
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('statsModal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeStatsModal();
                }
            }
        });

    </script>

    <!-- トークン使用量統計モーダル -->
    <div id="statsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="closeStatsModal(event)">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden"
             style="background: var(--bg-primary);"
             onclick="event.stopPropagation()">
            <!-- ヘッダー -->
            <div class="flex items-center justify-between p-6 border-b" style="border-color: var(--border-color);">
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">📊 トークン使用量統計</h2>
                <button onclick="closeStatsModal()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
            </div>

            <!-- コンテンツ -->
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
                <!-- ローディング -->
                <div id="statsLoading" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2" style="color: var(--text-secondary);">読み込み中...</p>
                </div>

                <!-- 統計コンテンツ -->
                <div id="statsContent" class="hidden space-y-6">
                    <!-- 月間サマリー -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 rounded-lg" style="background: var(--bg-tertiary);">
                            <div class="text-sm" style="color: var(--text-secondary);">合計トークン</div>
                            <div class="text-2xl font-bold mt-1 font-mono" style="color: var(--text-primary);" id="totalTokens">-</div>
                        </div>
                        <div class="p-4 rounded-lg" style="background: var(--bg-tertiary);">
                            <div class="text-sm" style="color: var(--text-secondary);">合計コスト</div>
                            <div class="text-2xl font-bold mt-1 font-mono" style="color: var(--text-primary);" id="totalCost">-</div>
                        </div>
                        <div class="p-4 rounded-lg" style="background: var(--bg-tertiary);">
                            <div class="text-sm" style="color: var(--text-secondary);">メッセージ数</div>
                            <div class="text-2xl font-bold mt-1 font-mono" style="color: var(--text-primary);" id="totalMessages">-</div>
                        </div>
                    </div>

                    <!-- グラフ -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">日別使用量</h3>
                        <div class="p-4 rounded-lg" style="background: var(--bg-secondary);">
                            <canvas id="tokenChart" height="80"></canvas>
                        </div>
                    </div>

                    <!-- 会話別トップ10 -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">使用量の多い会話 Top 10</h3>
                        <div class="space-y-2" id="conversationList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
