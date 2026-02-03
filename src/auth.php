<?php
/**
 * Entra ID (Azure AD) JWT Token 検証ミドルウェア
 * 
 * Authorization: Bearer {token} ヘッダーからトークンを抽出し、
 * Microsoft の公開鍵で署名を検証します。
 */

require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;

class EntraIdAuth
{
    private string $tenantId;
    private string $clientId;
    private ?array $jwks = null;
    private int $jwksCacheTime = 0;
    private const JWKS_CACHE_TTL = 3600; // 1時間キャッシュ

    public function __construct()
    {
        $this->tenantId = getenv('AZURE_TENANT_ID') ?: '';
        $this->clientId = getenv('AZURE_CLIENT_ID') ?: '';
    }

    /**
     * 認証が有効かどうかを確認
     */
    public function isEnabled(): bool
    {
        return !empty($this->tenantId) && !empty($this->clientId);
    }

    /**
     * リクエストを認証
     * @return array|null 成功時はデコードされたトークン、失敗時はnull
     */
    public function authenticate(): ?array
    {
        // 認証が無効な場合はスキップ
        if (!$this->isEnabled()) {
            return ['_auth_disabled' => true];
        }

        $token = $this->extractBearerToken();
        if (!$token) {
            $this->sendUnauthorized('Authorization header missing or invalid');
            return null;
        }

        try {
            $decoded = $this->validateToken($token);
            return (array) $decoded;
        } catch (Exception $e) {
            $this->sendUnauthorized('Token validation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Authorization ヘッダーから Bearer トークンを抽出
     */
    private function extractBearerToken(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * JWT トークンを検証
     */
    private function validateToken(string $token): object
    {
        $keys = $this->getJwks();
        
        // トークンをデコード（署名検証なしでヘッダーを取得）
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        $header = json_decode(base64_decode(strtr($tokenParts[0], '-_', '+/')), true);
        $kid = $header['kid'] ?? null;
        
        if (!$kid || !isset($keys[$kid])) {
            throw new Exception('Unknown key ID');
        }
        
        // JWT を検証
        $decoded = JWT::decode($token, $keys[$kid]);
        
        // 追加の検証
        $this->validateClaims($decoded);
        
        return $decoded;
    }

    /**
     * クレームを検証
     */
    private function validateClaims(object $decoded): void
    {
        // Issuer 検証
        $expectedIssuer = "https://login.microsoftonline.com/{$this->tenantId}/v2.0";
        $altExpectedIssuer = "https://sts.windows.net/{$this->tenantId}/";
        
        if (!isset($decoded->iss) || 
            ($decoded->iss !== $expectedIssuer && $decoded->iss !== $altExpectedIssuer)) {
            throw new Exception('Invalid issuer');
        }
        
        // Audience 検証
        if (!isset($decoded->aud) || $decoded->aud !== $this->clientId) {
            // aud が配列の場合もチェック
            if (is_array($decoded->aud) && !in_array($this->clientId, $decoded->aud)) {
                throw new Exception('Invalid audience');
            } elseif (!is_array($decoded->aud)) {
                throw new Exception('Invalid audience');
            }
        }
        
        // 有効期限検証 (JWT ライブラリが自動で行うが念のため)
        if (isset($decoded->exp) && $decoded->exp < time()) {
            throw new Exception('Token expired');
        }
    }

    /**
     * Microsoft の JWKS を取得
     */
    private function getJwks(): array
    {
        // キャッシュが有効な場合は再利用
        if ($this->jwks !== null && (time() - $this->jwksCacheTime) < self::JWKS_CACHE_TTL) {
            return $this->jwks;
        }
        
        $jwksUrl = "https://login.microsoftonline.com/{$this->tenantId}/discovery/v2.0/keys";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ]);
        
        $jwksJson = @file_get_contents($jwksUrl, false, $context);
        if ($jwksJson === false) {
            throw new Exception('Failed to fetch JWKS');
        }
        
        $jwksData = json_decode($jwksJson, true);
        if (!$jwksData || !isset($jwksData['keys'])) {
            throw new Exception('Invalid JWKS response');
        }
        
        $this->jwks = JWK::parseKeySet($jwksData);
        $this->jwksCacheTime = time();
        
        return $this->jwks;
    }

    /**
     * 401 Unauthorized レスポンスを送信
     */
    private function sendUnauthorized(string $message): void
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        header('WWW-Authenticate: Bearer realm="Entra ID"');
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized',
            'message' => $message
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

// グローバルインスタンスを作成
$entraIdAuth = new EntraIdAuth();
