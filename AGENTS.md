# 台灣支付金流 Skills - Agent Guidelines

本文件提供 AI coding agents（Claude Code、Cursor、Windsurf 等）整合台灣支付金流時的指南。

## Repository 概述

此 repository 是一個 Claude Code **Plugin Marketplace**，包含多個可選擇性安裝的 plugins，每個 plugin 教導 AI agents 如何整合特定的台灣支付服務。

## 目錄結構

```
paid-tw/skills/
├── .claude-plugin/
│   └── marketplace.json       # Marketplace 目錄
├── plugins/
│   ├── payment-help/          # Help plugin
│   │   └── skills/payment-help/
│   ├── newebpay/              # 藍新金流（含多個 skills）
│   │   ├── .claude-plugin/
│   │   │   └── plugin.json
│   │   └── skills/
│   │       ├── newebpay/           # 總覽
│   │       ├── newebpay-checkout/  # 串接
│   │       ├── newebpay-query/     # 查詢
│   │       └── newebpay-refund/    # 退款
│   ├── ecpay/                 # 綠界科技
│   └── payuni/                # PAYUNi
├── README.md
├── AGENTS.md
└── LICENSE
```

## 可用的 Skills

### 藍新金流 (newebpay plugin)

| Skill | 用途 | 觸發關鍵字 |
|-------|------|-----------|
| `newebpay` | 總覽與環境設定 | "藍新", "NewebPay" |
| `newebpay-checkout` | MPG 幕前支付串接 | "藍新串接", "建立交易", "MPG" |
| `newebpay-query` | 交易查詢 API | "藍新查詢", "查詢訂單" |
| `newebpay-refund` | 退款 API | "藍新退款", "信用卡退款" |

### KryptoGO 穩定幣支付 (kryptogo-pay plugin)

| Skill | 用途 | 觸發關鍵字 |
|-------|------|-----------|
| `kryptogo-pay` | 總覽與環境設定 | "KryptoGO", "穩定幣支付", "crypto payment" |
| `kryptogo-pay-checkout` | 建立 Payment Intent 收款 | "KryptoGO 收款", "建立支付", "payment intent" |
| `kryptogo-pay-query` | 查詢 Payment Intent | "KryptoGO 查詢", "支付狀態" |
| `kryptogo-pay-webhook` | Webhook 回調處理 | "KryptoGO webhook", "回調", "callback" |
| `kryptogo-pay-transfer` | 代幣轉帳/提領 | "KryptoGO 轉帳", "提領", "withdrawal" |

### 其他 Plugins

| Plugin | 用途 | 觸發關鍵字 |
|--------|------|-----------|
| `payment-help` | 列出所有可用的支付 skills | "台灣金流", "支付整合" |
| `ecpay` | 綠界科技串接 | "綠界", "ECPay" |
| `payuni` | PAYUNi 統一金流串接 | "PAYUNi", "統一金流" |

## Skill 選擇指南

當用戶詢問台灣支付整合時，根據任務選擇適當的 skill：

1. **不確定用哪個金流** → `payment-help`
2. **要串接藍新金流** → `newebpay` 或 `newebpay-checkout`
3. **要查詢藍新交易** → `newebpay-query`
4. **要藍新退款** → `newebpay-refund`
5. **要串接 KryptoGO 穩定幣支付** → `kryptogo-pay` 或 `kryptogo-pay-checkout`
6. **要查詢 KryptoGO 支付狀態** → `kryptogo-pay-query`
7. **要處理 KryptoGO Webhook** → `kryptogo-pay-webhook`
8. **要 KryptoGO 轉帳/提領** → `kryptogo-pay-transfer`
9. **要串接綠界科技** → `ecpay`
10. **要串接 PAYUNi** → `payuni`

## 整合情境

### 常見支付功能對應

| 功能需求 | 藍新 | KryptoGO | 綠界 | PAYUNi |
|---------|------|----------|------|--------|
| 信用卡付款 | ✅ | ❌ | ✅ | ✅ |
| LINE Pay | ✅ | ❌ | ✅ | ✅ |
| Apple Pay | ✅ | ❌ | ✅ | ✅ |
| ATM 轉帳 | ✅ | ❌ | ✅ | ✅ |
| 超商代碼 | ✅ | ❌ | ✅ | ✅ |
| 超商條碼 | ✅ | ❌ | ✅ | ✅ |
| 電子發票 | ❌ | ❌ | ✅ | ❌ |
| 穩定幣（USDT/USDC）| ❌ | ✅ | ❌ | ❌ |
| Webhook 回調 | ✅ | ✅ | ✅ | ✅ |
| 代幣轉帳/提領 | ❌ | ✅ | ❌ | ❌ |

### 環境變數

各金流服務需要的環境變數：

```bash
# 藍新金流
NEWEBPAY_MERCHANT_ID=MS12345678
NEWEBPAY_HASH_KEY=your_hash_key
NEWEBPAY_HASH_IV=your_hash_iv

# KryptoGO 穩定幣支付
KRYPTOGO_CLIENT_ID=your_client_id
KRYPTOGO_STUDIO_API_KEY=your_studio_api_key
KRYPTOGO_ORIGIN=your_domain

# 綠界科技
ECPAY_MERCHANT_ID=2000132
ECPAY_HASH_KEY=your_hash_key
ECPAY_HASH_IV=your_hash_iv

# PAYUNi
PAYUNI_MERCHANT_ID=your_merchant_id
PAYUNI_HASH_KEY=your_hash_key
PAYUNI_HASH_IV=your_hash_iv
```

### 共同模式

**傳統金流加密流程（藍新、綠界、PAYUNi）**
1. 組織交易資料（JSON 或 Query String）
2. AES256 加密
3. SHA256 簽章
4. Form POST 到金流平台

**KryptoGO 穩定幣支付流程**
1. 後端呼叫 API 建立 Payment Intent（帶 API Key）
2. 前端使用 React SDK 或自訂 UI 引導用戶支付
3. Webhook 回調通知支付狀態變更
4. 回應 HTTP 200 確認收到

**回傳處理**
- NotifyURL / Webhook：幕後通知（更新訂單狀態）
- ReturnURL：前端導回（顯示結果頁）

## 回應指南

協助用戶整合支付時：

1. **語言**：使用繁體中文（zh-TW）回覆
2. **框架**：除非指定，假設使用 PHP 或 Node.js
3. **安全性**：提醒 HashKey/HashIV 不可暴露於前端
4. **測試**：建議先在測試環境驗證
5. **錯誤處理**：包含 try/catch 和用戶友善的錯誤訊息

## SKILL.md 標準

每個 skill 遵循此 frontmatter 格式：

```yaml
---
name: skill-name
description: 清楚描述功能與觸發關鍵字...
license: MIT
metadata:
  author: paid-tw
  version: "1.0.0"
---
```

**內容指南**：
- SKILL.md 保持在 500 行以內
- 詳細資料放在 references/ 目錄
- 包含可運作的程式碼範例
- 結尾連結相關 skills

## 測試整合

用戶可使用以下方式測試：

### 藍新金流測試
- 測試環境：`https://ccore.newebpay.com/MPG/mpg_gateway`
- 測試信用卡：參考藍新測試文件

### 綠界科技測試
- 測試環境：`https://payment-stage.ecpay.com.tw/`
- 測試商店代號：`2000132`

### PAYUNi 測試
- 測試環境：`https://sandbox-api.payuni.com.tw/`
- 測試卡號：
  - VISA：`4147-6310-0000-0001`
  - JCB：`3560-5110-0000-0001`
  - 到期日：任意未來日期
  - CVV：任意三碼

## 相關資源

- [藍新金流官網](https://www.newebpay.com)
- [KryptoGO 官網](https://www.kryptogo.com)
- [KryptoGO Studio](https://studio.kryptogo.com)
- [KryptoGO API 文件](https://www.kryptogo.com/docs/api/payment)
- [綠界科技官網](https://www.ecpay.com.tw)
- [PAYUNi 官網](https://www.payuni.com.tw)
- [Claude Code Plugins 文件](https://docs.anthropic.com/en/docs/claude-code/plugins)
