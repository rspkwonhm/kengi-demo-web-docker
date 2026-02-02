# Azure Application Gateway SSL 設定ガイド

Docker コンテナは HTTP (80) で動作し、SSL 終端は Azure Application Gateway で処理します。

## 構成図

```
[クライアント] --HTTPS:443--> [Application Gateway + SSL] --HTTP:80--> [VM Docker]
```

---

## 1. Let's Encrypt 証明書の発行

### Certbot のインストール

```bash
sudo apt update
sudo apt install certbot -y
```

### 証明書の発行

**方法 A: HTTP チャレンジ** (80 ポートが開いている場合)

```bash
sudo certbot certonly --standalone -d your-domain.com
```

**方法 B: DNS チャレンジ** (80 ポートが不要)

```bash
sudo certbot certonly --manual --preferred-challenges dns -d your-domain.com
# → DNS TXT レコードを追加する案内が表示されます
```

---

## 2. PFX 形式への変換

Azure Application Gateway は PFX 形式の証明書が必要です。

```bash
sudo openssl pkcs12 -export \
  -out ~/certificate.pfx \
  -inkey /etc/letsencrypt/live/your-domain.com/privkey.pem \
  -in /etc/letsencrypt/live/your-domain.com/fullchain.pem \
  -password pass:YourSecurePassword123
```

### 権限の変更

```bash
sudo chmod 644 ~/certificate.pfx
sudo chown $USER:$USER ~/certificate.pfx
```

---

## 3. Application Gateway への証明書アップロード

### Azure CLI のインストール (必要な場合)

```bash
curl -sL https://aka.ms/InstallAzureCLIDeb | sudo bash
az login
```

### 証明書のアップロード

```bash
az network application-gateway ssl-cert create \
  --gateway-name <agw-name> \
  --resource-group <resource-group> \
  --name letsencrypt-cert \
  --cert-file ~/certificate.pfx \
  --cert-password YourSecurePassword123
```

---

## 4. リスナーとルールの設定

### HTTPS リスナーの作成

```bash
az network application-gateway http-listener create \
  --gateway-name <agw-name> \
  --resource-group <resource-group> \
  --name https-listener \
  --frontend-port appGatewayFrontendPort443 \
  --ssl-cert letsencrypt-cert \
  --host-name your-domain.com
```

### バックエンドプールの作成

```bash
az network application-gateway address-pool create \
  --gateway-name <agw-name> \
  --resource-group <resource-group> \
  --name docker-pool \
  --servers <vm-private-ip>
```

### HTTP 設定の作成

```bash
az network application-gateway http-settings create \
  --gateway-name <agw-name> \
  --resource-group <resource-group> \
  --name backend-http \
  --port 80 \
  --protocol Http
```

### ルーティングルールの作成

```bash
az network application-gateway rule create \
  --gateway-name <agw-name> \
  --resource-group <resource-group> \
  --name https-to-http-rule \
  --http-listener https-listener \
  --address-pool docker-pool \
  --http-settings backend-http \
  --priority 100
```

---

## 5. 証明書の更新

Let's Encrypt 証明書は 90 日で期限切れになります。

```bash
# 証明書の更新
sudo certbot renew

# PFX の再生成
sudo openssl pkcs12 -export \
  -out ~/certificate.pfx \
  -inkey /etc/letsencrypt/live/your-domain.com/privkey.pem \
  -in /etc/letsencrypt/live/your-domain.com/fullchain.pem \
  -password pass:YourSecurePassword123

# 権限の変更
sudo chmod 644 ~/certificate.pfx
sudo chown $USER:$USER ~/certificate.pfx

# AGW の証明書を更新
az network application-gateway ssl-cert update \
  --gateway-name <agw-name> \
  --resource-group <resource-group> \
  --name letsencrypt-cert \
  --cert-file ~/certificate.pfx \
  --cert-password YourSecurePassword123
```

---

## Azure Portal での設定

CLI の代わりに Azure Portal でも設定可能です：

1. **Application Gateway** → 対象の AGW を選択
2. **リスナー** → 追加 → HTTPS + 証明書をアップロード
3. **バックエンドプール** → VM の IP を追加
4. **規則** → リスナーとバックエンドを接続
