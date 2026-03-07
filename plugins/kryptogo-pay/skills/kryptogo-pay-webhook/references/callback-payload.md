# KryptoGO Payment Callback Payload 完整格式

## Callback 觸發時機

KryptoGO 會在以下時機發送 POST 請求到你的 `callback_url`：

1. 支付成功（`status: success`）
2. 支付過期（`status: expired`）
3. 金額不足等待退款（`status: insufficient_not_refunded`）
4. 金額不足已退款（`status: insufficient_refunded`）

## Payload 格式

```json
{
  "payment_intent_id": "0h39QkYfZps7AUD1xQsj3MDFVLIMaGoV",
  "client_id": "9c5a79fc1117310f976b53752659b61d",
  "fiat_amount": "300.0",
  "fiat_currency": "TWD",
  "payment_deadline": 1715462400,
  "status": "success",
  "payment_chain_id": "arb",
  "symbol": "USDT",
  "crypto_amount": "2.53",
  "payment_tx_hash": "0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef",
  "received_crypto_amount": "2.53",
  "aggregated_crypto_amount": "2.50",
  "order_data": {
    "order_id": "uid_12345",
    "item_id": "100"
  },
  "callback_url": "https://example.com/callback",
  "group_key": "buy_stone_with_usdt"
}
```

## 欄位說明

| 欄位 | 類型 | 說明 |
|------|------|------|
| `payment_intent_id` | String | 支付交易唯一識別碼 |
| `client_id` | String | 你的 KryptoGO Client ID |
| `fiat_amount` | String | 法幣金額 |
| `fiat_currency` | String | 法幣幣別（TWD / USD）|
| `payment_deadline` | Number | 支付截止時間（Unix timestamp）|
| `status` | String | 支付狀態 |
| `payment_chain_id` | String | 區塊鏈網路（如 `arb`）|
| `symbol` | String | 代幣符號（如 `USDT`）|
| `crypto_amount` | String | 需支付的加密貨幣金額 |
| `payment_tx_hash` | String / null | 支付交易 Hash（成功時有值）|
| `received_crypto_amount` | String / null | 實際收到的金額 |
| `aggregated_crypto_amount` | String / null | 扣除手續費後的金額 |
| `order_data` | Object / null | 你建立 Intent 時傳入的自訂資料 |
| `callback_url` | String / null | 回調 URL |
| `group_key` | String / null | 支付分類標籤 |

## 各狀態 Payload 差異

### success

所有金額欄位皆有值：

| 欄位 | 值 |
|------|------|
| `payment_tx_hash` | 有值 |
| `received_crypto_amount` | 有值 |
| `aggregated_crypto_amount` | 有值（= received - 手續費）|
| `refund_tx_hash` | null |
| `refund_amount` | null |

### expired

金額相關欄位為 null：

| 欄位 | 值 |
|------|------|
| `payment_tx_hash` | null |
| `received_crypto_amount` | null |
| `aggregated_crypto_amount` | null |

### insufficient_not_refunded

有收到金額但不足：

| 欄位 | 值 |
|------|------|
| `payment_tx_hash` | 有值 |
| `received_crypto_amount` | 有值（< crypto_amount）|
| `refund_tx_hash` | null（尚未退款）|
| `refund_amount` | null |

### insufficient_refunded

金額不足且已退款：

| 欄位 | 值 |
|------|------|
| `payment_tx_hash` | 有值 |
| `received_crypto_amount` | 有值 |
| `refund_tx_hash` | 有值 |
| `refund_amount` | 有值 |

## 回應要求

你的端點**必須**回應 HTTP 200 以確認收到回調。若未收到 200 回應，KryptoGO 可能會重試發送。

```
HTTP/1.1 200 OK
```

## 安全建議

1. 驗證 `payment_intent_id` 存在於你的資料庫
2. 驗證 `client_id` 與你的 Client ID 一致
3. 實作冪等處理避免重複更新
4. 記錄所有回調資料供稽核使用
5. 端點應使用 HTTPS
