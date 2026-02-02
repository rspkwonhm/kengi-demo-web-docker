<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>デモ Web サーバー</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Hiragino Sans', 'Meiryo', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #ffffff;
        }

        .container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 600px;
            width: 90%;
            text-align: center;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #00d4ff, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 40px;
            font-size: 1.1rem;
        }

        .api-section {
            margin-bottom: 32px;
        }

        .api-section h2 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: #00d4ff;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .btn {
            padding: 16px 32px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(5, 150, 105, 0.4);
        }

        .btn-info {
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: white;
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(2, 132, 199, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #d97706, #f59e0b);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(217, 119, 6, 0.4);
        }

        .section-divider {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 24px 0;
        }

        .response-box {
            margin-top: 32px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 20px;
            text-align: left;
            display: none;
        }

        .response-box.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .response-label {
            font-size: 0.85rem;
            color: #00d4ff;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .response-content {
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.9rem;
            color: #e2e8f0;
            word-break: break-all;
            white-space: pre-wrap;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-success {
            background: #10b981;
        }

        .status-error {
            background: #ef4444;
        }

        footer {
            margin-top: 40px;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🚀 デモ Web サーバー</h1>
        <p class="subtitle">Docker コンテナで動作中 | Apache + PHP</p>

        <div class="api-section">
            <h2>📡 API テスト</h2>
            <div class="btn-group">
                <button class="btn btn-primary" onclick="callApi('/api/hello')">
                    <span>👋</span> 挨拶 API を呼び出す
                </button>
                <button class="btn btn-secondary" onclick="callApi('/api/status')">
                    <span>📊</span> サーバー状態を確認
                </button>
                <button class="btn btn-info" onclick="callApi('/api/time')">
                    <span>🕐</span> 現在時刻を取得
                </button>
            </div>
        </div>

        <hr class="section-divider">

        <div class="api-section">
            <h2>🔗 外部 APIM テスト</h2>
            <div class="btn-group">
                <button class="btn btn-warning" onclick="callApim(1, 'GET')">
                    <span>🌐</span> APIM #1 (GET)
                </button>
                <button class="btn btn-warning" onclick="callApim(2, 'POST')">
                    <span>🌐</span> APIM #2 (POST)
                </button>
            </div>
        </div>

        <div id="responseBox" class="response-box">
            <div class="response-label">API レスポンス</div>
            <div id="responseContent" class="response-content"></div>
        </div>

        <footer>
            <p>© 2026 デモ Web サーバー | Powered by Docker</p>
        </footer>
    </div>

    <script>
        // APIM URL は PHP から環境変数を取得
        const APIM_URLS = {
            1: '<?php echo getenv("APIM_TEST_URL") ?: ""; ?>',
            2: '<?php echo getenv("APIM_TEST_URL_2") ?: ""; ?>'
        };
        const APIM_KEYS = {
            1: '<?php echo getenv("APIM_TEST_KEY") ?: ""; ?>',
            2: '<?php echo getenv("APIM_TEST_KEY_2") ?: ""; ?>'
        };

        async function callApi(endpoint) {
            const responseBox = document.getElementById('responseBox');
            const responseContent = document.getElementById('responseContent');

            responseBox.classList.add('show');
            responseContent.innerHTML = '<span class="loading"></span> 読み込み中...';

            try {
                const response = await fetch(endpoint);
                const data = await response.json();

                responseContent.innerHTML =
                    '<span class="status-indicator status-success"></span>' +
                    '<strong>成功!</strong>\n\n' +
                    JSON.stringify(data, null, 2);
            } catch (error) {
                responseContent.innerHTML =
                    '<span class="status-indicator status-error"></span>' +
                    '<strong>エラー:</strong>\n\n' +
                    error.message;
            }
        }

        async function callApim(num, method = 'GET') {
            const responseBox = document.getElementById('responseBox');
            const responseContent = document.getElementById('responseContent');
            const apimUrl = APIM_URLS[num];
            const apimKey = APIM_KEYS[num];

            responseBox.classList.add('show');

            if (!apimUrl) {
                responseContent.innerHTML =
                    '<span class="status-indicator status-error"></span>' +
                    '<strong>設定エラー:</strong>\n\n' +
                    'APIM_TEST_URL' + (num === 2 ? '_2' : '') + ' が .env に設定されていません。';
                return;
            }

            responseContent.innerHTML = '<span class="loading"></span> APIM #' + num + ' (' + method + ') に接続中...\n' + apimUrl;

            try {
                const headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                };
                if (apimKey) {
                    headers['Ocp-Apim-Subscription-Key'] = apimKey;
                }

                const fetchOptions = {
                    method: method,
                    headers: headers
                };

                // POSTの場合はボディを追加
                if (method === 'POST') {
                    fetchOptions.body = JSON.stringify({ test: true, timestamp: Date.now() });
                }

                const response = await fetch(apimUrl, fetchOptions);

                const contentType = response.headers.get('content-type');
                let data;

                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    data = await response.text();
                }

                responseContent.innerHTML =
                    '<span class="status-indicator status-success"></span>' +
                    '<strong>APIM #' + num + ' 応答 (HTTP ' + response.status + ')</strong>\n\n' +
                    '<strong>URL:</strong> ' + apimUrl + '\n\n' +
                    (typeof data === 'object' ? JSON.stringify(data, null, 2) : data);
            } catch (error) {
                responseContent.innerHTML =
                    '<span class="status-indicator status-error"></span>' +
                    '<strong>APIM #' + num + ' エラー:</strong>\n\n' +
                    '<strong>URL:</strong> ' + apimUrl + '\n\n' +
                    error.message;
            }
        }
    </script>
</body>

</html>