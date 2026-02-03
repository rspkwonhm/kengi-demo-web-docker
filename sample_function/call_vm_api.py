"""
Azure Function - MSAL Client Credentials Flow サンプル

このサンプルは、Azure Function から VM 上の Web API に
Entra ID 認証付きでリクエストを送信する方法を示します。

必要な環境変数:
- AZURE_TENANT_ID: Azure AD テナント ID
- AZURE_CLIENT_ID: アプリケーション (クライアント) ID
- AZURE_CLIENT_SECRET: クライアント シークレット
- VM_API_BASE_URL: VM の Web API ベース URL (例: https://your-vm.example.com)
"""

import os
import logging
import requests
from msal import ConfidentialClientApplication

# ロギング設定
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


class EntraIdClient:
    """Entra ID (Azure AD) 認証クライアント"""
    
    def __init__(self):
        self.tenant_id = os.environ.get("AZURE_TENANT_ID", "{TenantID}")
        self.client_id = os.environ.get("AZURE_CLIENT_ID", "{ClientID}")
        self.client_secret = os.environ.get("AZURE_CLIENT_SECRET", "{ClientSecret}")
        
        # MSAL クライアントを初期化
        self.authority = f"https://login.microsoftonline.com/{self.tenant_id}"
        self.app = ConfidentialClientApplication(
            client_id=self.client_id,
            client_credential=self.client_secret,
            authority=self.authority
        )
        
        # スコープ: VM API の Client ID を指定
        # Client Credentials Flow では /.default が必要
        self.scopes = [f"{self.client_id}/.default"]
    
    def get_access_token(self) -> str:
        """
        Client Credentials Flow でアクセストークンを取得
        
        Returns:
            str: アクセストークン
        
        Raises:
            Exception: トークン取得に失敗した場合
        """
        # まずキャッシュからトークンを取得試行
        result = self.app.acquire_token_silent(self.scopes, account=None)
        
        if not result:
            logger.info("キャッシュにトークンがないため、新規取得します")
            result = self.app.acquire_token_for_client(scopes=self.scopes)
        
        if "access_token" in result:
            logger.info("アクセストークンを取得しました")
            return result["access_token"]
        else:
            error_msg = result.get("error_description", result.get("error", "Unknown error"))
            logger.error(f"トークン取得エラー: {error_msg}")
            raise Exception(f"Failed to acquire token: {error_msg}")


def call_vm_api(endpoint: str, method: str = "GET", data: dict = None) -> dict:
    """
    VM 上の Web API を Entra ID 認証付きで呼び出す
    
    Args:
        endpoint: API エンドポイント (例: "/api/hello")
        method: HTTP メソッド (GET, POST, etc.)
        data: POST/PUT 時のリクエストボディ
    
    Returns:
        dict: API レスポンス
    """
    # 認証クライアントを初期化してトークン取得
    auth_client = EntraIdClient()
    access_token = auth_client.get_access_token()
    
    # VM API の URL を構築
    base_url = os.environ.get("VM_API_BASE_URL", "https://your-vm.example.com")
    url = f"{base_url}{endpoint}"
    
    # リクエストヘッダーに Bearer トークンを設定
    headers = {
        "Authorization": f"Bearer {access_token}",
        "Content-Type": "application/json",
        "Accept": "application/json"
    }
    
    logger.info(f"VM API を呼び出し中: {method} {url}")
    
    # リクエスト送信
    if method.upper() == "GET":
        response = requests.get(url, headers=headers, timeout=30)
    elif method.upper() == "POST":
        response = requests.post(url, headers=headers, json=data, timeout=30)
    else:
        raise ValueError(f"Unsupported method: {method}")
    
    logger.info(f"レスポンス: {response.status_code}")
    
    response.raise_for_status()
    return response.json()


# ========================================
# Azure Function エントリーポイント例
# ========================================

def main():
    """
    使用例: VM API を呼び出して結果を表示
    
    実行前に以下の環境変数を設定してください:
    - AZURE_TENANT_ID
    - AZURE_CLIENT_ID
    - AZURE_CLIENT_SECRET
    - VM_API_BASE_URL
    """
    try:
        # /api/hello エンドポイントを呼び出し
        result = call_vm_api("/api/hello")
        print("=== API Response ===")
        print(result)
        
    except requests.exceptions.HTTPError as e:
        if e.response.status_code == 401:
            print("認証エラー: トークンが無効または期限切れです")
        else:
            print(f"HTTP エラー: {e}")
    except Exception as e:
        print(f"エラー: {e}")


if __name__ == "__main__":
    main()
