---
name: kryptogo-pay-webhook
description: >
  Implements KryptoGO Payment webhook/callback handling for receiving payment status
  notifications. Use when building webhook endpoints, handling payment callbacks,
  or implementing 支付狀態通知 for KryptoGO Payment.
argument-hint: "[框架: Express/Flask/FastAPI/NestJS]"
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

# KryptoGO Payment Webhook 回調處理任務

你的任務是在用戶的專案中實作 KryptoGO Payment Webhook 回調處理。

## 串接 Checklist

- [ ] **端點建立** - 建立 POST endpoint 接收回調
- [ ] **狀態處理** - 處理所有 5 種支付狀態
- [ ] **冪等處理** - 實作冪等性避免重複處理
- [ ] **回應確認** - 回應 HTTP 200 確認收到
- [ ] **測試驗證** - 驗證端點可正常運作

---

## Step 1: 確認環境

詢問用戶：

1. **框架類型**：你使用什麼後端框架？
   - Node.js (Express / Fastify / NestJS)
   - Python (Django / Flask / FastAPI)
   - 其他

2. **資料庫**：使用什麼資料庫儲存訂單？
   - 需要知道如何更新訂單狀態

用戶輸入: `$ARGUMENTS`

## Step 2: 建立 Webhook 端點

建立 POST 端點接收 KryptoGO 的回調通知。

**端點路徑建議**: `/api/payment/callback` 或 `/webhook/kryptogo`

**必要行為**:
1. 接收 POST JSON body
2. 驗證 `payment_intent_id` 存在於資料庫
3. 根據 `status` 更新訂單狀態
4. 回應 HTTP 200

## Step 3: 處理所有支付狀態

必須處理以下 5 種狀態：

| 狀態 | 處理邏輯 |
|------|---------|
| `pending` | 通常不會收到此狀態的回調 |
| `success` | 更新訂單為已付款，記錄 `payment_tx_hash` |
| `expired` | 標記訂單為過期 |
| `insufficient_not_refunded` | 記錄異常，等待退款 |
| `insufficient_refunded` | 記錄退款資訊 `refund_tx_hash`、`refund_amount` |

## Step 4: 實作冪等性

確保同一個 `payment_intent_id` 的回調不會被重複處理：
- 檢查訂單是否已經更新為最終狀態
- 使用 `payment_intent_id` 作為冪等鍵

## Step 5: 測試

1. 建立一個 Payment Intent（帶 `callback_url`）
2. 完成支付後確認端點收到回調
3. 驗證訂單狀態正確更新
4. 驗證回應 HTTP 200

---

## Callback Payload 格式

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
  "payment_tx_hash": "0x1234567890abcdef...",
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

## 重要欄位

| 欄位 | 說明 |
|------|------|
| `payment_intent_id` | 用來比對你資料庫中的訂單 |
| `status` | 判斷該做什麼處理 |
| `payment_tx_hash` | 成功時的區塊鏈交易 Hash |
| `received_crypto_amount` | 實際收到的加密貨幣金額 |
| `aggregated_crypto_amount` | 扣除手續費後的金額 |
| `refund_tx_hash` | 退款時的區塊鏈交易 Hash |
| `refund_amount` | 退款金額 |
| `order_data` | 你建立 Payment Intent 時傳入的自訂資料 |

---

## 詳細參考文件

- [程式碼範例 (Node.js/Python)](references/code-examples.md)
- [Callback Payload 完整格式](references/callback-payload.md)
- [疑難排解](references/troubleshooting.md)
