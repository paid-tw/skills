# Payment Intent 完整欄位說明

## Payment Intent 物件

| 欄位 | 類型 | 說明 |
|------|------|------|
| `payment_intent_id` | String | 支付交易唯一識別碼 |
| `payment_chain_id` | String | 區塊鏈網路識別碼（如 `arb` = Arbitrum）|
| `payment_address` | String | 收款錢包地址 |
| `token_address` | String | 支付代幣合約地址 |
| `symbol` | String | 代幣符號（如 `USDT`、`USDC`）|
| `decimals` | Number | 代幣小數位數 |
| `crypto_amount` | String | 需支付的加密貨幣金額 |
| `fiat_amount` | String | 對應的法幣金額 |
| `fiat_currency` | `TWD` \| `USD` | 法幣幣別 |
| `payment_deadline` | Number | 支付截止時間（Unix timestamp）|
| `status` | PaymentStatus | 目前支付狀態 |
| `payment_tx_hash` | String \| null | 支付的區塊鏈交易 Hash |
| `received_crypto_amount` | String \| null | 實際收到的加密貨幣金額 |
| `aggregated_crypto_amount` | String \| null | 扣除手續費後的最終金額 |
| `refund_amount` | String \| null | 退款金額（如有退款）|
| `refund_tx_hash` | String \| null | 退款的區塊鏈交易 Hash |
| `callback_url` | String \| null | Webhook 通知 URL |
| `order_data` | Object \| null | 自訂訂單資料 |
| `group_key` | String \| null | 支付分類標籤 |

## 建立 Payment Intent 請求參數

| 參數 | 類型 | 必填 | 說明 |
|------|------|:----:|------|
| `fiat_amount` | String | ✓ | 法幣金額，最低 `0.01` |
| `fiat_currency` | String | ✓ | `TWD` 或 `USD` |
| `callback_url` | String | | Webhook 回調 URL，狀態變更時會收到 POST 通知 |
| `order_data` | Object | | 自訂訂單資料（序列化後最多 1000 字元）|
| `group_key` | String | | 支付分類標籤，方便在 Studio 或 API 篩選 |

## Payment Intent 回應範例

```json
{
  "code": 0,
  "data": {
    "payment_intent_id": "2MFV9kRD0IyNWauowhq2Bp3IUb3BWKPi",
    "payment_chain_id": "arb",
    "payment_address": "0xd8dA6BF26964aF9D7eEd9e03E53415D37aA96045",
    "token_address": "0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9",
    "symbol": "USDT",
    "decimals": 6,
    "crypto_amount": "9.23",
    "fiat_amount": "300.0",
    "fiat_currency": "TWD",
    "payment_deadline": 1733811354,
    "status": "pending",
    "payment_tx_hash": null,
    "refund_tx_hash": null,
    "received_crypto_amount": null,
    "aggregated_crypto_amount": null,
    "refund_amount": null,
    "order_data": {
      "order_id": "uid_12345",
      "item_id": "100"
    },
    "callback_url": "https://example.com/callback",
    "group_key": "buy_stone_with_usdt"
  }
}
```

## Payment Status 狀態說明

| 狀態 | 說明 | 後續處理 |
|------|------|---------|
| `pending` | 等待用戶付款 | 顯示支付頁面，等待用戶完成支付 |
| `success` | 付款成功，已確認 | 更新訂單狀態為已付款，發送確認通知 |
| `expired` | 付款逾時，未收到款項 | 標記訂單為過期，允許用戶重新下單 |
| `insufficient_not_refunded` | 收到金額不足，等待退款 | 記錄異常，等待系統自動退款 |
| `insufficient_refunded` | 金額不足，已退款 | 記錄退款資訊，通知用戶重新支付 |

## 支援的區塊鏈與代幣

| Chain ID | 區塊鏈 | 支援代幣 |
|----------|--------|---------|
| `arb` | Arbitrum | USDT, USDC |

> 更多區塊鏈支援將陸續新增。

## Arbitrum 代幣合約地址

| 代幣 | 合約地址 |
|------|---------|
| USDT | `0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9` |
| USDC | `0xff970a61a04b1ca14834a43f5de4533ebddb5cc8` |
