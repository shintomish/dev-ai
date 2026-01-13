<svg {{ $attributes }} viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
    <!-- 入力層（左側のノード） -->
    <circle cx="12" cy="12" r="4" fill="#4F46E5">
        <animate attributeName="r" values="4;5;4" dur="2s" repeatCount="indefinite"/>
        <animate attributeName="opacity" values="0.8;1;0.8" dur="2s" repeatCount="indefinite"/>
    </circle>
    <circle cx="12" cy="36" r="4" fill="#4F46E5">
        <animate attributeName="r" values="4;5;4" dur="2s" begin="0.5s" repeatCount="indefinite"/>
        <animate attributeName="opacity" values="0.8;1;0.8" dur="2s" begin="0.5s" repeatCount="indefinite"/>
    </circle>

    <!-- 出力層（右側のノード） -->
    <circle cx="36" cy="12" r="4" fill="#4F46E5">
        <animate attributeName="r" values="4;5;4" dur="2s" begin="1s" repeatCount="indefinite"/>
        <animate attributeName="opacity" values="0.8;1;0.8" dur="2s" begin="1s" repeatCount="indefinite"/>
    </circle>
    <circle cx="36" cy="36" r="4" fill="#4F46E5">
        <animate attributeName="r" values="4;5;4" dur="2s" begin="1.5s" repeatCount="indefinite"/>
        <animate attributeName="opacity" values="0.8;1;0.8" dur="2s" begin="1.5s" repeatCount="indefinite"/>
    </circle>

    <!-- 隠れ層（中央のノード） -->
    <circle cx="24" cy="24" r="6" fill="#7C3AED">
        <animate attributeName="r" values="6;7;6" dur="2s" repeatCount="indefinite"/>
    </circle>

    <!-- 中央ノードの内側の光 -->
    <circle cx="24" cy="24" r="3" fill="#FFFFFF" opacity="0.3">
        <animate attributeName="opacity" values="0.3;0.6;0.3" dur="2s" repeatCount="indefinite"/>
    </circle>

    <!-- 接続線（データフローアニメーション） -->
    <line x1="12" y1="12" x2="24" y2="24" stroke="#4F46E5" stroke-width="2.5">
        <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" repeatCount="indefinite"/>
        <animate attributeName="stroke-width" values="2;3;2" dur="2s" repeatCount="indefinite"/>
    </line>
    <line x1="36" y1="12" x2="24" y2="24" stroke="#4F46E5" stroke-width="2.5">
        <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" begin="0.5s" repeatCount="indefinite"/>
        <animate attributeName="stroke-width" values="2;3;2" dur="2s" begin="0.5s" repeatCount="indefinite"/>
    </line>
    <line x1="12" y1="36" x2="24" y2="24" stroke="#4F46E5" stroke-width="2.5">
        <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" begin="1s" repeatCount="indefinite"/>
        <animate attributeName="stroke-width" values="2;3;2" dur="2s" begin="1s" repeatCount="indefinite"/>
    </line>
    <line x1="36" y1="36" x2="24" y2="24" stroke="#4F46E5" stroke-width="2.5">
        <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" begin="1.5s" repeatCount="indefinite"/>
        <animate attributeName="stroke-width" values="2;3;2" dur="2s" begin="1.5s" repeatCount="indefinite"/>
    </line>

    <!-- データフローを表現する小さな点（パルス効果） -->
    <circle cx="18" cy="18" r="1.5" fill="#FFFFFF">
        <animate attributeName="opacity" values="0;1;0" dur="2s" repeatCount="indefinite"/>
        <animateMotion path="M 0 0 L 6 6" dur="2s" repeatCount="indefinite"/>
    </circle>
    <circle cx="30" cy="18" r="1.5" fill="#FFFFFF">
        <animate attributeName="opacity" values="0;1;0" dur="2s" begin="0.5s" repeatCount="indefinite"/>
        <animateMotion path="M 0 0 L -6 6" dur="2s" begin="0.5s" repeatCount="indefinite"/>
    </circle>
</svg>