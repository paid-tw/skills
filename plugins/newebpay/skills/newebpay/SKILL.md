---
name: newebpay
description: >
  Provides NewebPay integration overview and guides users to the appropriate skill.
  Use when starting NewebPay integration, setting up environment, or needing general guidance
  about 藍新金流.
argument-hint: "[功能: checkout/query/refund]"
user-invocable: true
---

# 藍新金流整合指南

你的任務是幫助用戶設定藍新金流(NewebPay)環境並引導至適當的串接功能。

## 用戶需求分析

用戶輸入: `$ARGUMENTS`

根據用戶需求，判斷下一步：
- 若包含「串接」「checkout」「建立交易」「MPG」→ 引導使用 `/newebpay-checkout`
- 若包含「查詢」「query」「訂單狀態」→ 引導使用 `/newebpay-query`
- 若包含「退款」「refund」「取消」→ 引導使用 `/newebpay-refund`
- 若無特定指定 → 提供以下環境設定引導

## 環境設定檢查

詢問用戶以下問題：

1. **專案框架**：你使用什麼框架？
   - PHP (Laravel / 原生 PHP / 其他)
   - Node.js (Express / NestJS / 原生 / 其他)
   - Python (Django / Flask / FastAPI / 其他)
   - 其他

2. **環境狀態**：是否已有藍新金流商店帳號？
   - 是，已有測試環境帳號
   - 是，已有正式環境帳號
   - 否，需要申請

## 環境變數設定

引導用戶建立環境變數：

```bash
NEWEBPAY_MERCHANT_ID=MS12345678
NEWEBPAY_HASH_KEY=your_hash_key
NEWEBPAY_HASH_IV=your_hash_iv
NEWEBPAY_ENV=test  # test 或 production
```

**指導用戶:**
1. 在專案根目錄建立或編輯 `.env` 檔案
2. 加入上述環境變數
3. 確保 `.env` 已加入 `.gitignore`

## 加密模組

根據用戶框架，提供對應的加密解密函式。詳細實作請參閱 [scripts/encryption.php](../../scripts/encryption.php)

**PHP:**
```php
function encrypt($data, $key, $iv) {
    $encrypted = openssl_encrypt($data, "AES-256-CBC", $key, OPENSSL_RAW_DATA, $iv);
    return bin2hex($encrypted);
}

function decrypt($encrypted_hex, $key, $iv) {
    return rtrim(openssl_decrypt(hex2bin($encrypted_hex), "AES-256-CBC", $key,
        OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv), "\x00..\x1F");
}

function generateSha($trade_info, $key, $iv) {
    return strtoupper(hash("sha256", "HashKey={$key}&{$trade_info}&HashIV={$iv}"));
}
```

**Node.js:**
```javascript
const crypto = require('crypto');

function encrypt(data, key, iv) {
  const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
  let encrypted = cipher.update(data, 'utf8', 'hex');
  encrypted += cipher.final('hex');
  return encrypted;
}

function generateSha(tradeInfo, key, iv) {
  return crypto.createHash('sha256')
    .update(`HashKey=${key}&${tradeInfo}&HashIV=${iv}`)
    .digest('hex').toUpperCase();
}
```

## 下一步

完成環境設定後，根據用戶需求引導：

| 需求 | Skill | 說明 |
|------|-------|------|
| 建立支付頁面 | `/newebpay-checkout` | MPG 幕前支付串接 |
| 查詢交易狀態 | `/newebpay-query` | 交易查詢 API |
| 處理退款 | `/newebpay-refund` | 信用卡/電子錢包退款 |

## 環境資訊

| 環境 | API Base URL |
|------|--------------|
| 測試 | `https://ccore.newebpay.com` |
| 正式 | `https://core.newebpay.com` |

## 支援的支付方式

- **信用卡**: 一次付清、分期付款、紅利折抵
- **行動支付**: Apple Pay、Google Pay、Samsung Pay
- **電子錢包**: LINE Pay、台灣Pay、TWQR
- **ATM**: WebATM、ATM轉帳
- **超商**: 代碼繳費、條碼繳費

## 重要注意事項

1. HashKey 和 HashIV 必須保密，不可暴露在前端
2. 時間戳記容許誤差 ±120 秒
3. 訂單編號不可重複，限 30 字元
4. ReturnURL/NotifyURL 只接受 80/443 Port
