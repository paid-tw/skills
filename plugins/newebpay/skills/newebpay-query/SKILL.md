---
name: newebpay-query
description: >
  Queries NewebPay transaction status and payment details using QueryTradeInfo API.
  Use when checking order status, retrieving transaction information, or verifying payment completion.
  Triggers: "newebpay query", "藍新查詢", "查詢訂單", "交易狀態", "transaction status"
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

# 藍新金流交易查詢

本 skill 提供藍新金流單筆交易查詢 API 串接指南。

## API 端點

| 環境 | URL |
|-----|-----|
| 測試 | `https://ccore.newebpay.com/API/QueryTradeInfo` |
| 正式 | `https://core.newebpay.com/API/QueryTradeInfo` |

## 請求參數

| 參數 | 類型 | 必填 | 說明 |
|-----|------|:----:|------|
| MerchantID | String(15) | ✓ | 商店代號 |
| Version | String | ✓ | `1.3` |
| RespondType | String | ✓ | `JSON` |
| CheckValue | String | ✓ | SHA256 檢核碼 |
| TimeStamp | Number | ✓ | Unix timestamp |
| MerchantOrderNo | String(30) | ✓ | 商店訂單編號 |
| Amt | Number | ✓ | 訂單金額 |

## CheckValue 產生

```
原始: IV={HashIV}&Amt={金額}&MerchantID={商店代號}&MerchantOrderNo={訂單編號}&Key={HashKey}
結果: SHA256 後轉大寫
```

## PHP 範例

```php
<?php
$merchant_id = getenv('NEWEBPAY_MERCHANT_ID');
$hash_key = getenv('NEWEBPAY_HASH_KEY');
$hash_iv = getenv('NEWEBPAY_HASH_IV');

$order_no = 'ORDER_1234567890';
$amount = 1000;

// 產生 CheckValue
$check_value = strtoupper(hash('sha256',
    "IV={$hash_iv}&Amt={$amount}&MerchantID={$merchant_id}&MerchantOrderNo={$order_no}&Key={$hash_key}"
));

// 發送請求
$response = file_get_contents('https://ccore.newebpay.com/API/QueryTradeInfo', false,
    stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'MerchantID' => $merchant_id,
                'Version' => '1.3',
                'RespondType' => 'JSON',
                'CheckValue' => $check_value,
                'TimeStamp' => time(),
                'MerchantOrderNo' => $order_no,
                'Amt' => $amount,
            ]),
        ],
    ])
);

$result = json_decode($response, true);

if ($result['Status'] === 'SUCCESS') {
    echo "交易狀態: " . $result['Result']['TradeStatus'];
}
```

## Node.js 範例

```javascript
const crypto = require('crypto');
const axios = require('axios');

async function queryTrade(orderNo, amount) {
  const merchantId = process.env.NEWEBPAY_MERCHANT_ID;
  const hashKey = process.env.NEWEBPAY_HASH_KEY;
  const hashIV = process.env.NEWEBPAY_HASH_IV;

  const checkValue = crypto.createHash('sha256')
    .update(`IV=${hashIV}&Amt=${amount}&MerchantID=${merchantId}&MerchantOrderNo=${orderNo}&Key=${hashKey}`)
    .digest('hex')
    .toUpperCase();

  const { data } = await axios.post(
    'https://ccore.newebpay.com/API/QueryTradeInfo',
    new URLSearchParams({
      MerchantID: merchantId,
      Version: '1.3',
      RespondType: 'JSON',
      CheckValue: checkValue,
      TimeStamp: Math.floor(Date.now() / 1000),
      MerchantOrderNo: orderNo,
      Amt: amount,
    }).toString(),
    { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }
  );

  return data;
}
```

## 回應參數

### 基本回應

| 參數 | 說明 |
|-----|------|
| Status | `SUCCESS` 或錯誤代碼 |
| Message | 回傳訊息 |
| Result | 查詢結果 |

### Result 內容

| 參數 | 說明 |
|-----|------|
| TradeNo | 藍新交易序號 |
| MerchantOrderNo | 商店訂單編號 |
| TradeStatus | 交易狀態 |
| PaymentType | 支付方式 |
| PayTime | 付款時間 |
| Amt | 交易金額 |

### TradeStatus 交易狀態

| 值 | 說明 |
|:--:|------|
| 0 | 未付款 |
| 1 | 已付款 |
| 2 | 付款失敗 |
| 3 | 已取消 |
| 6 | 退款 |

### PaymentType 支付方式

| 值 | 說明 |
|-----|------|
| CREDIT | 信用卡 |
| VACC | ATM 轉帳 |
| CVS | 超商代碼 |
| LINEPAY | LINE Pay |

## 常見錯誤

| 代碼 | 說明 |
|-----|------|
| TRA10001 | 查無此筆交易 |
| TRA10002 | CheckValue 檢核錯誤 |
| TRA10003 | 時間戳記錯誤 |

## 相關 Skills

- `/newebpay` - 總覽與環境設定
- `/newebpay-checkout` - MPG 幕前支付串接
- `/newebpay-refund` - 退款作業
