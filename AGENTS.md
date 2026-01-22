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
│   │   ├── .claude-plugin/
│   │   │   └── plugin.json
│   │   └── skills/
│   │       └── payment-help/
│   │           └── SKILL.md
│   ├── newebpay/              # 藍新金流
│   │   ├── .claude-plugin/
│   │   │   └── plugin.json
│   │   └── skills/
│   │       └── newebpay/
│   │           ├── SKILL.md
│   │           └── references/
│   ├── ecpay/                 # 綠界科技
│   │   └── ...
│   └── payuni/                # PAYUNi
│       └── ...
├── README.md
├── AGENTS.md
└── LICENSE
```

## 可用的 Plugins

| Plugin | 用途 | 觸發關鍵字 |
|--------|------|-----------|
| `payment-help` | 列出所有可用的支付 skills | "台灣金流", "支付整合", "哪些金流" |
| `newebpay` | 藍新金流串接 | "藍新", "NewebPay", "藍新金流" |
| `ecpay` | 綠界科技串接 | "綠界", "ECPay", "綠界科技" |
| `payuni` | PAYUNi 統一金流串接 | "PAYUNi", "統一金流" |

## Skill 選擇指南

當用戶詢問台灣支付整合時，根據任務選擇適當的 skill：

1. **不確定用哪個金流** → `payment-help`
2. **要串接藍新金流** → `newebpay`
3. **要串接綠界科技** → `ecpay`
4. **要串接 PAYUNi** → `payuni`
5. **詢問「台灣有哪些金流」** → `payment-help`

## 整合情境

### 常見支付功能對應

| 功能需求 | 藍新 | 綠界 | PAYUNi |
|---------|------|------|--------|
| 信用卡付款 | ✅ | ✅ | ✅ |
| LINE Pay | ✅ | ✅ | ✅ |
| Apple Pay | ✅ | ✅ | ✅ |
| ATM 轉帳 | ✅ | ✅ | ✅ |
| 超商代碼 | ✅ | ✅ | ✅ |
| 超商條碼 | ✅ | ✅ | ✅ |
| 電子發票 | ❌ | ✅ | ❌ |

### 環境變數

各金流服務需要的環境變數：

```bash
# 藍新金流
NEWEBPAY_MERCHANT_ID=MS12345678
NEWEBPAY_HASH_KEY=your_hash_key
NEWEBPAY_HASH_IV=your_hash_iv

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

**加密流程（三家皆相同概念）**
1. 組織交易資料（JSON 或 Query String）
2. AES256 加密
3. SHA256 簽章
4. Form POST 到金流平台

**回傳處理**
- NotifyURL：幕後通知（更新訂單狀態）
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
- [綠界科技官網](https://www.ecpay.com.tw)
- [PAYUNi 官網](https://www.payuni.com.tw)
- [Claude Code Plugins 文件](https://docs.anthropic.com/en/docs/claude-code/plugins)
