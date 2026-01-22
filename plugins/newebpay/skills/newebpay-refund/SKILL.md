---
name: newebpay-refund
description: >
  Processes NewebPay refund requests for credit cards and e-wallets using CreditCard/Close
  and EWallet/Refund APIs. Use when handling refunds, canceling transactions, or processing return payments.
  Triggers: "newebpay refund", "藍新退款", "信用卡退款", "取消交易", "refund payment"
argument-hint: "[類型: 信用卡/電子錢包]"
allowed-tools:
  - Read
  - Write
  - Edit
  - Bash
  - Grep
  - Glob
user-invocable: true
license: MIT
metadata:
  author: paid-tw
  version: "1.0.0"
---

# 藍新金流退款作業

本 skill 提供藍新金流退款 API 串接指南，包含信用卡退款與電子錢包退款。

## 用戶需求分析

用戶輸入: `$ARGUMENTS`

- 若包含「信用卡」「CREDIT」→ 聚焦信用卡退款 API
- 若包含「LINE Pay」「電子錢包」→ 聚焦電子錢包退款 API
- 若無特定指定 → 提供完整概覽

## 信用卡退款

### API 端點

| 環境 | URL |
|-----|-----|
| 測試 | `https://ccore.newebpay.com/API/CreditCard/Close` |
| 正式 | `https://core.newebpay.com/API/CreditCard/Close` |

### 請求參數

| 參數 | 類型 | 必填 | 說明 |
|-----|------|:----:|------|
| MerchantID_ | String | ✓ | 商店代號 |
| PostData_ | String | ✓ | AES256 加密資料 |

### PostData_ 內容

| 參數 | 類型 | 必填 | 說明 |
|-----|------|:----:|------|
| RespondType | String | ✓ | `JSON` |
| Version | String | ✓ | `1.1` |
| Amt | Number | ✓ | 退款金額 |
| MerchantOrderNo | String | ✓ | 原訂單編號 |
| TimeStamp | Number | ✓ | Unix timestamp |
| IndexType | Number | ✓ | 1: 使用訂單編號 |
| CloseType | Number | ✓ | 2: 退款 |

### PHP 範例

```php
<?php
$merchant_id = getenv('NEWEBPAY_MERCHANT_ID');
$hash_key = getenv('NEWEBPAY_HASH_KEY');
$hash_iv = getenv('NEWEBPAY_HASH_IV');

// 退款資料
$refund_data = [
    'RespondType' => 'JSON',
    'Version' => '1.1',
    'Amt' => 1000,  // 退款金額
    'MerchantOrderNo' => 'ORDER_1234567890',
    'TimeStamp' => time(),
    'IndexType' => 1,
    'CloseType' => 2,  // 退款
];

// AES256 加密
$post_data = bin2hex(openssl_encrypt(
    http_build_query($refund_data),
    'AES-256-CBC',
    $hash_key,
    OPENSSL_RAW_DATA,
    $hash_iv
));

// 發送請求
$ch = curl_init('https://ccore.newebpay.com/API/CreditCard/Close');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'MerchantID_' => $merchant_id,
        'PostData_' => $post_data,
    ]),
    CURLOPT_RETURNTRANSFER => true,
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($result['Status'] === 'SUCCESS') {
    echo "退款成功";
}
```

### 回應參數

| 參數 | 說明 |
|-----|------|
| Status | `SUCCESS` 或錯誤代碼 |
| Message | 回傳訊息 |
| Result.MerchantOrderNo | 訂單編號 |
| Result.Amt | 退款金額 |
| Result.TradeNo | 藍新交易序號 |

---

## 電子錢包退款

適用於 LINE Pay、台灣 Pay 等電子錢包。

### API 端點

| 環境 | URL |
|-----|-----|
| 測試 | `https://ccore.newebpay.com/API/EWallet/Refund` |
| 正式 | `https://core.newebpay.com/API/EWallet/Refund` |

### 請求參數

| 參數 | 類型 | 必填 | 說明 |
|-----|------|:----:|------|
| MerchantID_ | String | ✓ | 商店代號 |
| PostData_ | String | ✓ | AES256 加密資料 |

### PostData_ 內容

| 參數 | 類型 | 必填 | 說明 |
|-----|------|:----:|------|
| RespondType | String | ✓ | `JSON` |
| Version | String | ✓ | `1.0` |
| TimeStamp | Number | ✓ | Unix timestamp |
| TradeNo | String | ✓ | 藍新交易序號 |
| MerchantOrderNo | String | ✓ | 原訂單編號 |
| Amt | Number | ✓ | 退款金額 |

### PHP 範例

```php
<?php
$refund_data = [
    'RespondType' => 'JSON',
    'Version' => '1.0',
    'TimeStamp' => time(),
    'TradeNo' => '23120712345678',  // 藍新交易序號
    'MerchantOrderNo' => 'ORDER_1234567890',
    'Amt' => 1000,
];

$post_data = bin2hex(openssl_encrypt(
    http_build_query($refund_data),
    'AES-256-CBC',
    $hash_key,
    OPENSSL_RAW_DATA,
    $hash_iv
));

$ch = curl_init('https://ccore.newebpay.com/API/EWallet/Refund');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'MerchantID_' => $merchant_id,
        'PostData_' => $post_data,
    ]),
    CURLOPT_RETURNTRANSFER => true,
]);
$response = curl_exec($ch);
curl_close($ch);
```

---

## 常見錯誤

| 代碼 | 說明 |
|-----|------|
| CRE10001 | 無此交易紀錄 |
| CRE10002 | 已退款或取消 |
| CRE10003 | 退款金額錯誤 |
| CRE10004 | 超過可退款期限 |

## 注意事項

1. **退款期限**：信用卡一般為交易後 180 天內
2. **部分退款**：可退款金額 ≤ 原交易金額
3. **退款次數**：同一筆交易可多次部分退款
4. **電子錢包**：需使用藍新交易序號 (TradeNo)

## 相關 Skills

- `/newebpay` - 總覽與環境設定
- `/newebpay-checkout` - MPG 幕前支付串接
- `/newebpay-query` - 交易查詢
