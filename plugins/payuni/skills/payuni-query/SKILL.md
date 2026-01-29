---
name: payuni-query
description: >
  Implements PAYUNi transaction query functionality using Query API.
  Use when building order status checking, transaction verification, or payment
  confirmation features for 統一金流.
argument-hint: "[查詢情境: 單筆查詢/批次對帳/狀態確認]"
context: fork
agent: general-purpose
disable-model-invocation: true
allowed-tools:
  - Read
  - Write
  - Edit
  - Bash
  - Grep
  - Glob
user-invocable: true
---

# 統一金流交易查詢任務

你的任務是在用戶的專案中實作統一金流交易查詢功能。

## Step 1: 確認需求

用戶輸入: `$ARGUMENTS`

詢問用戶：

1. **查詢情境**：需要什麼查詢功能？
   - 單筆訂單查詢（客戶查詢、客服查詢）
   - 批次對帳（每日/定時對帳）
   - 支付狀態確認（NotifyURL 備援）

2. **專案框架**：你使用什麼框架？
   - 確認是否已有 PAYUNi 環境設定

## Step 2: 建立查詢功能

在現有的支付模組中加入查詢方法，或建立新模組。

**核心功能:**
1. `generateQueryParams(orderNo)` - 產生查詢參數
2. `queryTrade(orderNo)` - 查詢單筆交易

## Step 3: 實作程式碼

### Node.js/TypeScript 範例

```typescript
import crypto from 'crypto';

const config = {
  merchantId: process.env.PAYUNI_MERCHANT_ID!,
  hashKey: process.env.PAYUNI_HASH_KEY!,
  hashIV: process.env.PAYUNI_HASH_IV!,
  isTest: process.env.PAYUNI_TEST_MODE === 'true',
};

function getQueryEndpoint(): string {
  return config.isTest
    ? 'https://sandbox-api.payuni.com.tw/api/query'
    : 'https://api.payuni.com.tw/api/query';
}

function encrypt(data: string): string {
  const key = Buffer.from(config.hashKey.padEnd(32, '\0').slice(0, 32), 'utf8');
  const iv = Buffer.from(config.hashIV.padEnd(16, '\0').slice(0, 16), 'utf8');
  
  const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
  let encrypted = cipher.update(data, 'utf8', 'hex');
  encrypted += cipher.final('hex');
  return encrypted;
}

function generateHashInfo(encryptInfo: string): string {
  return crypto
    .createHash('sha256')
    .update(encryptInfo)
    .digest('hex')
    .toUpperCase();
}

async function queryTrade(orderNo: string): Promise<{
  success: boolean;
  data?: any;
  error?: string;
}> {
  try {
    const queryData = {
      MerID: config.merchantId,
      MerTradeNo: orderNo,
    };
    
    const queryString = new URLSearchParams(queryData).toString();
    const encryptInfo = encrypt(queryString);
    const hashInfo = generateHashInfo(encryptInfo);
    
    const response = await fetch(getQueryEndpoint(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        MerID: config.merchantId,
        EncryptInfo: encryptInfo,
        HashInfo: hashInfo,
      }),
    });
    
    const result = await response.json();
    
    return {
      success: result.Status === 'SUCCESS',
      data: result,
    };
  } catch (error) {
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Query failed',
    };
  }
}

// 使用範例
const result = await queryTrade('ORDER-123');
if (result.success) {
  console.log('交易狀態:', result.data.TradeStatus);
}
```

## Step 4: 整合到應用

建議整合方式：
- **API 端點**: `GET /api/orders/:orderNo/status`
- **管理後台**: 訂單詳情頁顯示即時狀態
- **定時任務**: 對帳排程

---

## API 參考

### 端點

| 環境 | URL |
|------|-----|
| 測試 | `https://sandbox-api.payuni.com.tw/api/query` |
| 正式 | `https://api.payuni.com.tw/api/query` |

### 請求參數

| 參數 | 類型 | 必填 | 說明 |
|------|------|:----:|------|
| MerID | String | ✓ | 商店代號 |
| EncryptInfo | String | ✓ | 加密後的查詢參數 |
| HashInfo | String | ✓ | SHA256 雜湊值 |

### EncryptInfo 內容

| 參數 | 類型 | 必填 | 說明 |
|------|------|:----:|------|
| MerID | String | ✓ | 商店代號 |
| MerTradeNo | String | ✓ | 商店訂單編號 |

### 交易狀態 (TradeStatus)

| 值 | 說明 |
|:--:|------|
| 0 | 未付款 |
| 1 | 已付款 |
| 2 | 付款失敗 |
| 3 | 已取消 |
| 6 | 已退款 |

---

## 詳細參考文件

- [程式碼範例 (PHP/Node.js)](references/code-examples.md)

---

## 常見錯誤

| 代碼 | 說明 | 解決方式 |
|------|------|---------|
| TRA10001 | 查無此筆交易 | 確認訂單編號正確 |
| TRA10002 | 驗證錯誤 | 確認加密參數正確 |
| TRA10003 | 商店代號錯誤 | 確認 MerchantID 正確 |
