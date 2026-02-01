# デモ Web サーバー

Docker コンテナで動作するデモ用 Web サーバーです。Let's Encrypt による SSL 自動発行に対応。

## 機能

- **Web サーバー**: Apache 2.4 + PHP 8.2
- **リバースプロキシ**: Nginx Proxy
- **SSL 証明書**: Let's Encrypt 自動発行・更新
- **フロントエンド**: HTML/CSS/JavaScript によるシンプルな UI
- **バックエンド API**: PHP による REST API エンドポイント

## セットアップ

### 1. 環境変数の設定

```bash
# .env ファイルを作成
cp .env.example .env

# .env を編集してホスト名を設定
VIRTUAL_HOST=your-domain.com
LETSENCRYPT_EMAIL=your-email@example.com
```

### 2. 起動

```bash
# コンテナをビルドして起動
docker compose up -d --build

# ログを確認
docker-compose logs -f

# 停止
docker-compose down
```

## アクセス

- **Web UI**: https://your-domain.com
- **API**: https://your-domain.com/api/

> ⚠️ SSL 証明書の発行にはドメインが必要です。ローカル開発時は HTTP (http://localhost) で動作します。

## API エンドポイント

| エンドポイント | 説明 |
|---------------|------|
| `GET /api/hello` | 挨拶メッセージを返す |
| `GET /api/status` | サーバー状態を返す |
| `GET /api/time` | 現在時刻を返す |

## ディレクトリ構成

```
vmtemp/
├── docker-compose.yml  # Docker Compose 設定
├── Dockerfile          # Docker イメージ定義
├── .env.example        # 環境変数テンプレート
├── .env                # 環境変数 (要作成)
├── README.md           # このファイル
└── src/
    ├── index.html      # フロントエンド UI
    ├── api.php         # バックエンド API
    └── .htaccess       # URL リライト設定
```

## SSL 証明書について

- 初回起動時に Let's Encrypt から自動で証明書を取得
- 証明書は自動で更新されます（有効期限 60 日前に更新）
- `docker compose up -d` で再起動するたびに証明書の状態を確認
