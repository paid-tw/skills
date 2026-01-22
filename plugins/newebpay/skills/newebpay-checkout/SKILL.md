---
name: newebpay-checkout
description: >
  Processes NewebPay MPG checkout integration including AES256 encryption, SHA256 signing,
  and form submission setup. Use when integrating NewebPay payment gateway, creating transactions,
  or setting up MPG payment pages.
  Triggers: "newebpay checkout", "藍新串接", "建立交易", "MPG", "payment integration"
argument-hint: "[支付方式: 信用卡/LINE Pay/ATM/超商]"
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

# 藍新金流 MPG 幕前支付串接

本 skill 提供藍新金流 MPG (Multi Payment Gateway) 幕前支付的完整串接指南。

## 用戶需求分析

用戶輸入: `$ARGUMENTS`

根據用戶輸入的支付方式，提供對應的整合指南：
- 若包含「信用卡」「CREDIT」→ 聚焦信用卡相關參數與範例
- 若包含「LINE Pay」「LINEPAY」→ 聚焦 LINE Pay 整合
- 若包含「ATM」「轉帳」→ 聚焦 ATM/WebATM 整合
- 若包含「超商」「CVS」→ 聚焦超商代碼/條碼繳費
- 若包含「Apple Pay」「Google Pay」→ 聚焦行動支付整合

## 串接流程

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  準備交易資料  │ → │  AES256加密  │ → │  SHA256簽章  │ → │  Form POST  │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
                                                                │
                                                                ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  更新訂單狀態 │ ← │  解密回應資料 │ ← │  接收通知    │ ← │  藍新支付頁  │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
```

## API 端點

| 環境 | URL |
|-----|-----|
| 測試 | `https://ccore.newebpay.com/MPG/mpg_gateway` |
| 正式 | `https://core.newebpay.com/MPG/mpg_gateway` |

## 必要參數

| 參數名稱 | 類型 | 說明 |
|---------|------|------|
| MerchantID | String | 商店代號 |
| TradeInfo | String | AES256 加密後的交易資料 |
| TradeSha | String | SHA256 簽章 |
| Version | String | API 版本 `2.3` |

## TradeInfo 內容

詳細參數請參閱 [references/mpg-transaction.md](references/mpg-transaction.md)

### 基本參數

| 參數 | 類型 | 必填 | 說明 |
|-----|------|:----:|------|
| MerchantID | String(15) | ✓ | 商店代號 |
| RespondType | String | ✓ | 回傳格式 `JSON` |
| TimeStamp | Number | ✓ | Unix timestamp |
| Version | String | ✓ | `2.3` |
| MerchantOrderNo | String(30) | ✓ | 訂單編號（不可重複）|
| Amt | Number | ✓ | 金額 |
| ItemDesc | String(50) | ✓ | 商品描述 |
| Email | String(50) | ✓ | 付款人 Email |

### 支付方式參數

| 參數 | 值 | 說明 |
|-----|:---:|------|
| CREDIT | 1 | 信用卡 |
| INST | 3,6,12 | 分期期數 |
| LINEPAY | 1 | LINE Pay |
| APPLEPAY | 1 | Apple Pay |
| GOOGLEPAY | 1 | Google Pay |
| VACC | 1 | ATM 轉帳 |
| CVS | 1 | 超商代碼 |
| BARCODE | 1 | 超商條碼 |

## PHP 範例

```php
<?php
$merchant_id = getenv('NEWEBPAY_MERCHANT_ID');
$hash_key = getenv('NEWEBPAY_HASH_KEY');
$hash_iv = getenv('NEWEBPAY_HASH_IV');

// 準備交易資料
$order = [
    'MerchantID' => $merchant_id,
    'RespondType' => 'JSON',
    'TimeStamp' => time(),
    'Version' => '2.3',
    'MerchantOrderNo' => 'ORDER_' . time(),
    'Amt' => 1000,
    'ItemDesc' => '商品名稱',
    'Email' => 'buyer@example.com',
    'CREDIT' => 1,
    'ReturnURL' => 'https://yourdomain.com/return',
    'NotifyURL' => 'https://yourdomain.com/notify',
];

// AES256 加密
$data = http_build_query($order);
$trade_info = bin2hex(openssl_encrypt(
    addPadding($data),
    'AES-256-CBC',
    $hash_key,
    OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
    $hash_iv
));

// SHA256 簽章
$trade_sha = strtoupper(hash('sha256',
    "HashKey={$hash_key}&{$trade_info}&HashIV={$hash_iv}"
));

function addPadding($str, $block = 32) {
    $pad = $block - (strlen($str) % $block);
    return $str . str_repeat(chr($pad), $pad);
}
?>

<form method="post" action="https://ccore.newebpay.com/MPG/mpg_gateway">
    <input type="hidden" name="MerchantID" value="<?= $merchant_id ?>">
    <input type="hidden" name="TradeInfo" value="<?= $trade_info ?>">
    <input type="hidden" name="TradeSha" value="<?= $trade_sha ?>">
    <input type="hidden" name="Version" value="2.3">
    <button type="submit">前往付款</button>
</form>
```

## Node.js 範例

```javascript
const crypto = require('crypto');

const merchantId = process.env.NEWEBPAY_MERCHANT_ID;
const hashKey = process.env.NEWEBPAY_HASH_KEY;
const hashIV = process.env.NEWEBPAY_HASH_IV;

function encrypt(data) {
  const query = new URLSearchParams(data).toString();
  const cipher = crypto.createCipheriv('aes-256-cbc', hashKey, hashIV);
  let encrypted = cipher.update(query, 'utf8', 'hex');
  encrypted += cipher.final('hex');
  return encrypted;
}

function generateSha(tradeInfo) {
  return crypto.createHash('sha256')
    .update(`HashKey=${hashKey}&${tradeInfo}&HashIV=${hashIV}`)
    .digest('hex')
    .toUpperCase();
}

const order = {
  MerchantID: merchantId,
  RespondType: 'JSON',
  TimeStamp: Math.floor(Date.now() / 1000),
  Version: '2.3',
  MerchantOrderNo: `ORDER_${Date.now()}`,
  Amt: 1000,
  ItemDesc: '商品名稱',
  Email: 'buyer@example.com',
  CREDIT: 1,
  ReturnURL: 'https://yourdomain.com/return',
  NotifyURL: 'https://yourdomain.com/notify',
};

const tradeInfo = encrypt(order);
const tradeSha = generateSha(tradeInfo);
```

## 接收回應

```php
<?php
// NotifyURL / ReturnURL 接收
$trade_info = $_POST['TradeInfo'];

$decrypted = openssl_decrypt(
    hex2bin($trade_info),
    'AES-256-CBC',
    $hash_key,
    OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
    $hash_iv
);
$result = json_decode(trim($decrypted), true);

if ($result['Status'] === 'SUCCESS') {
    $order_no = $result['Result']['MerchantOrderNo'];
    // 更新訂單狀態...
}
```

詳細回應參數請參閱 [references/response-parameters.md](references/response-parameters.md)

## 常見情境

### LINE Pay
```php
$order['LINEPAY'] = 1;
$order['ReturnURL'] = 'https://yourdomain.com/linepay/return';
```

### 信用卡分期
```php
$order['CREDIT'] = 1;
$order['INST'] = '3,6,12';  // 3/6/12 期
```

### ATM 轉帳
```php
$order['VACC'] = 1;
$order['ExpireDate'] = date('Ymd', strtotime('+7 days'));
$order['CustomerURL'] = 'https://yourdomain.com/atm_info';
```

更多情境請參閱 [references/use-cases.md](references/use-cases.md)

## 相關 Skills

- `/newebpay` - 總覽與環境設定
- `/newebpay-query` - 交易查詢
- `/newebpay-refund` - 退款作業
