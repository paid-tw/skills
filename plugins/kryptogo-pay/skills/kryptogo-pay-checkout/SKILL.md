---
name: kryptogo-pay-checkout
description: >
  Implements KryptoGO Payment checkout integration including Payment Intent creation,
  React SDK usePayment hook, and Direct API integration. Use when integrating crypto
  payment, creating checkout flows, or building KryptoGO 穩定幣收款頁面.
argument-hint: "[整合方式: SDK/API] [框架: React/Express/Python]"
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

# KryptoGO Payment 收款串接任務

你的任務是在用戶的專案中實作 KryptoGO Payment 穩定幣收款功能。

## 串接 Checklist

完成以下步驟即可完成串接：

- [ ] **環境確認** - 確認整合方式（SDK / API）與框架類型
- [ ] **環境變數** - 設定 KRYPTOGO_CLIENT_ID、STUDIO_API_KEY、ORIGIN
- [ ] **支付模組** - 建立 Payment Intent 建立與管理功能
- [ ] **支付頁面** - 建立支付 UI 或 API 端點
- [ ] **回調處理** - 建立 Webhook 端點接收狀態更新
- [ ] **測試驗證** - 使用測試環境驗證完整流程

---

## Step 1: 確認專案環境

詢問用戶：

1. **整合方式**：你要如何串接？
   - React SDK（前端 `usePayment` Hook）
   - Direct API（後端 REST API）
   - 兩者都要

2. **專案框架**：你使用什麼框架？
   - React / Next.js（SDK 整合）
   - Node.js (Express / Fastify / NestJS)
   - Python (Django / Flask / FastAPI)
   - 其他

用戶輸入: `$ARGUMENTS`

## Step 2: 檢查環境變數

搜尋專案中的 `.env` 或設定檔，確認是否已設定：
- `KRYPTOGO_CLIENT_ID`（或 `CLIENT_ID`）
- `KRYPTOGO_STUDIO_API_KEY`（或 `STUDIO_API_KEY`）
- `KRYPTOGO_ORIGIN`（或 `ORIGIN`）

若未設定，引導用戶前往 [KryptoGO Studio](https://studio.kryptogo.com) 取得金鑰。

## Step 3: 建立支付模組

### 路徑 A: React SDK 整合

**安裝依賴:**

```bash
npm install @kryptogo/kryptogokit-sdk-react wagmi viem@2.x @tanstack/react-query
```

**建立 Provider 設定:**

```tsx
import { WagmiProvider } from 'wagmi';
import { getDefaultConfig, KryptogoKitProvider } from '@kryptogo/kryptogokit-sdk-react';
import { QueryClientProvider, QueryClient } from '@tanstack/react-query';
import '@kryptogo/kryptogokit-sdk-react/styles.css';

const queryClient = new QueryClient();
const clientId = process.env.NEXT_PUBLIC_KRYPTOGO_CLIENT_ID;
const config = getDefaultConfig();

function App({ children }) {
  return (
    <WagmiProvider config={config}>
      <QueryClientProvider client={queryClient}>
        <KryptogoKitProvider clientId={clientId}>
          {children}
        </KryptogoKitProvider>
      </QueryClientProvider>
    </WagmiProvider>
  );
}
```

**建立支付元件（使用 `usePayment` Hook）:**

```tsx
import { usePayment } from '@kryptogo/payment';

function PaymentButton() {
  const {
    openPaymentModal,
    closePaymentModal,
    data,
    txHash,
    error,
    isLoading,
    isSuccess,
    isError
  } = usePayment();

  const handlePayment = () => {
    openPaymentModal({
      fiat_amount: '100',
      fiat_currency: 'TWD',
      callback_url: 'https://your-server.com/payment/callback',
      order_data: {
        orderId: '12345',
        productName: 'Product Name',
      },
      group_key: 'product_purchase',
    });
  };

  return (
    <div>
      <button onClick={handlePayment} disabled={isLoading}>
        {isLoading ? '處理中...' : '立即付款'}
      </button>
      {isSuccess && <p>付款成功！TxHash: {txHash}</p>}
      {isError && <p>付款失敗：{error?.message}</p>}
    </div>
  );
}
```

### 路徑 B: Direct API 整合

**建立位置建議:**
- Express: `services/kryptogo-payment.js`
- NestJS: `src/payment/payment.service.ts`
- Python: `services/kryptogo_payment.py`

**核心功能:**
1. `createPaymentIntent(fiatAmount, fiatCurrency, options)` - 建立 Payment Intent
2. `getPaymentIntent(paymentIntentId)` - 查詢單筆支付
3. `listPaymentIntents(filters)` - 列出支付意圖
4. `handleWebhook(payload)` - 處理 Webhook 回調

## Step 4: 建立支付端點

### Express API 端點範例

```javascript
const express = require('express');
const axios = require('axios');
const router = express.Router();

const KG_BASE_URL = 'https://wallet.kryptogo.app';
const headers = {
  'Content-Type': 'application/json',
  'X-Client-ID': process.env.KRYPTOGO_CLIENT_ID,
  'Origin': process.env.KRYPTOGO_ORIGIN,
  'X-STUDIO-API-KEY': process.env.KRYPTOGO_STUDIO_API_KEY,
};

// 建立 Payment Intent
router.post('/payment/intent', async (req, res) => {
  const { fiat_amount, fiat_currency, callback_url, order_data, group_key } = req.body;

  const response = await axios.post(
    `${KG_BASE_URL}/v1/studio/api/payment/intent`,
    { fiat_amount, fiat_currency, callback_url, order_data, group_key },
    { headers }
  );

  res.json(response.data);
});
```

## Step 5: 建立回調處理

建立 Webhook 端點接收支付狀態更新：

```javascript
router.post('/payment/callback', (req, res) => {
  const payment = req.body;

  switch (payment.status) {
    case 'success':
      // 更新訂單為已付款
      break;
    case 'expired':
      // 標記訂單為過期
      break;
    case 'insufficient_not_refunded':
      // 金額不足，等待退款
      break;
    case 'insufficient_refunded':
      // 已退款處理
      break;
  }

  res.status(200).send();
});
```

## Step 6: 測試驗證

引導用戶進行測試：
1. 在 KryptoGO Studio 建立測試用 Client ID
2. 建立一個小額 Payment Intent（fiat_amount: "0.01"）
3. 驗證 Payment Intent 回傳正確
4. 確認 Webhook 回調可正常接收
5. 確認支付狀態更新正確

---

## API 參考

### 端點

| 方法 | 路徑 | 說明 |
|------|------|------|
| POST | `/v1/studio/api/payment/intent` | 建立 Payment Intent |
| GET | `/v1/studio/api/payment/intent/{id}` | 查詢單筆 Payment Intent |
| GET | `/v1/studio/api/payment/intents` | 列出 Payment Intents |
| POST | `/v1/studio/api/asset_pro/transfer` | 代幣轉帳/提領 |

### Required Headers

| Header | 說明 |
|--------|------|
| `X-Client-ID` | KryptoGO Client ID |
| `X-STUDIO-API-KEY` | Studio API Key |
| `Origin` | 你的網域 |
| `Content-Type` | `application/json` |

### 建立 Payment Intent 參數

| 參數 | 類型 | 必填 | 說明 |
|------|------|:----:|------|
| fiat_amount | String | ✓ | 法幣金額（最低 0.01）|
| fiat_currency | String | ✓ | `TWD` 或 `USD` |
| callback_url | String | | Webhook 回調 URL |
| order_data | Object | | 自訂訂單資料（最多 1000 字元）|
| group_key | String | | 支付分類標籤 |

### Payment Status 狀態

| 狀態 | 說明 |
|------|------|
| `pending` | 等待付款 |
| `success` | 付款成功 |
| `expired` | 付款逾時 |
| `insufficient_not_refunded` | 金額不足，等待退款 |
| `insufficient_refunded` | 金額不足，已退款 |

---

## 詳細參考文件

- [程式碼範例 (Node.js/Python)](references/code-examples.md)
- [Payment Intent 完整欄位](references/payment-intent-fields.md)
- [錯誤代碼](references/error-codes.md)
- [疑難排解](references/troubleshooting.md)
