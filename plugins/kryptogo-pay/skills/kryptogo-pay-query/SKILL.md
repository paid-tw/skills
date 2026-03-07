---
name: kryptogo-pay-query
description: >
  Implements KryptoGO Payment query functionality for checking payment intent status
  and listing payment intents. Use when building order status checking, transaction
  verification, or payment confirmation features for KryptoGO 穩定幣支付.
argument-hint: "[查詢情境: 單筆查詢/列表查詢/狀態確認]"
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

# KryptoGO Payment 交易查詢任務

你的任務是在用戶的專案中實作 KryptoGO Payment 交易查詢功能。

## Step 1: 確認需求

用戶輸入: `$ARGUMENTS`

詢問用戶：

1. **查詢情境**：需要什麼查詢功能？
   - 單筆 Payment Intent 查詢（客戶查詢、客服查詢）
   - 列表查詢與篩選（批次對帳、管理後台）
   - 支付狀態確認（Webhook 備援方案）

2. **專案框架**：你使用什麼框架？
   - 確認是否已有 KryptoGO Payment 環境設定

## Step 2: 建立查詢功能

在現有的支付模組中加入查詢方法，或建立新模組。

**核心功能:**
1. `getPaymentIntent(paymentIntentId)` - 查詢單筆 Payment Intent
2. `listPaymentIntents(filters)` - 列出 Payment Intents

## Step 3: 實作程式碼

根據框架加入查詢功能。

## Step 4: 整合到應用

建議整合方式：
- **API 端點**: `GET /api/payment/intent/:id`
- **管理後台**: 訂單詳情頁顯示即時狀態
- **定時任務**: 對帳排程（輪詢未完成的訂單）

---

## API 參考

### 查詢單筆 Payment Intent

| 項目 | 說明 |
|------|------|
| 方法 | `GET` |
| 路徑 | `/v1/studio/api/payment/intent/{payment_intent_id}` |
| Headers | `X-Client-ID`, `X-STUDIO-API-KEY`, `Origin` |

### 列出 Payment Intents

| 項目 | 說明 |
|------|------|
| 方法 | `GET` |
| 路徑 | `/v1/studio/api/payment/intents` |
| Headers | `X-Client-ID`, `X-STUDIO-API-KEY`, `Origin` |

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

---

## 常見錯誤

| 代碼 | 說明 | 解決方式 |
|------|------|---------|
| 4001 | Invalid client_id | 確認 `X-Client-ID` 正確 |
| 4002 | Invalid API key | 確認 `X-STUDIO-API-KEY` 正確 |
| 4006 | Invalid payment_intent_id | 確認 Payment Intent ID 存在 |
