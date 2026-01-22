---
name: newebpay-checkout
description: >
  Implements NewebPay MPG checkout integration including AES256 encryption,
  form submission, and payment callback handling. Use when integrating payment
  gateway, creating checkout flows, or building 藍新金流 payment pages.
argument-hint: "[支付方式: 信用卡/LINE Pay/ATM/超商]"
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

# 藍新金流 MPG 支付串接任務

你的任務是在用戶的專案中實作藍新金流 MPG 幕前支付功能。

## Step 1: 確認專案環境

詢問用戶：

1. **框架類型**：你使用什麼框架？
   - PHP (Laravel / CodeIgniter / 原生)
   - Node.js (Express / Fastify / NestJS)
   - Python (Django / Flask / FastAPI)
   - 其他

2. **支付方式**：需要支援哪些支付方式？（可複選）
   - 信用卡
   - LINE Pay
   - Apple Pay / Google Pay
   - ATM 轉帳
   - 超商代碼/條碼

用戶輸入: `$ARGUMENTS`

## Step 2: 檢查環境變數

搜尋專案中的 `.env` 或設定檔，確認是否已設定：
- `NEWEBPAY_MERCHANT_ID`
- `NEWEBPAY_HASH_KEY`
- `NEWEBPAY_HASH_IV`

若未設定，引導用戶設定環境變數。

## Step 3: 建立支付模組

根據用戶框架建立支付模組檔案。

**建立位置建議:**
- Laravel: `app/Services/NewebPayService.php`
- Express: `services/newebpay.js`
- Django: `payments/services.py`

**核心功能:**
1. `encrypt(data)` - AES256 加密
2. `decrypt(data)` - AES256 解密
3. `generateSha(tradeInfo)` - SHA256 簽章
4. `createOrder(orderData)` - 建立訂單並回傳表單資料
5. `handleNotify(payload)` - 處理回調通知

## Step 4: 建立支付表單頁面

根據框架建立支付表單，需包含：

```html
<form method="post" action="https://ccore.newebpay.com/MPG/mpg_gateway">
    <input type="hidden" name="MerchantID" value="{商店代號}">
    <input type="hidden" name="TradeInfo" value="{加密資料}">
    <input type="hidden" name="TradeSha" value="{SHA256簽章}">
    <input type="hidden" name="Version" value="2.3">
    <button type="submit">前往付款</button>
</form>
```

## Step 5: 建立回調處理

建立兩個端點：

1. **NotifyURL** (背景通知): `POST /payment/notify`
   - 接收藍新背景通知
   - 解密 TradeInfo
   - 更新訂單狀態
   - 回應 "OK"

2. **ReturnURL** (前台返回): `GET/POST /payment/return`
   - 用戶支付完成後導向
   - 顯示交易結果

## Step 6: 測試驗證

引導用戶進行測試：
1. 使用測試環境 `https://ccore.newebpay.com`
2. 測試信用卡號: `4000-2211-1111-1111`
3. 驗證加密解密正確性
4. 確認回調可正常接收

---

## API 參考

### 端點

| 環境 | URL |
|------|-----|
| 測試 | `https://ccore.newebpay.com/MPG/mpg_gateway` |
| 正式 | `https://core.newebpay.com/MPG/mpg_gateway` |

### TradeInfo 必要參數

| 參數 | 類型 | 說明 |
|------|------|------|
| MerchantID | String(15) | 商店代號 |
| RespondType | String | `JSON` |
| TimeStamp | Number | Unix timestamp |
| Version | String | `2.3` |
| MerchantOrderNo | String(30) | 訂單編號（不可重複）|
| Amt | Number | 金額 |
| ItemDesc | String(50) | 商品描述 |
| Email | String(50) | 付款人 Email |
| ReturnURL | String | 前台返回網址 |
| NotifyURL | String | 背景通知網址 |

### 支付方式參數

| 參數 | 值 | 說明 |
|------|:---:|------|
| CREDIT | 1 | 信用卡 |
| INST | 3,6,12 | 分期期數 |
| LINEPAY | 1 | LINE Pay |
| APPLEPAY | 1 | Apple Pay |
| GOOGLEPAY | 1 | Google Pay |
| VACC | 1 | ATM 轉帳 |
| CVS | 1 | 超商代碼 |
| BARCODE | 1 | 超商條碼 |

---

## 程式碼範本

### PHP 完整範例

```php
<?php
class NewebPayService
{
    private $merchantId;
    private $hashKey;
    private $hashIv;
    private $apiUrl;

    public function __construct()
    {
        $this->merchantId = getenv('NEWEBPAY_MERCHANT_ID');
        $this->hashKey = getenv('NEWEBPAY_HASH_KEY');
        $this->hashIv = getenv('NEWEBPAY_HASH_IV');
        $this->apiUrl = getenv('NEWEBPAY_ENV') === 'production'
            ? 'https://core.newebpay.com/MPG/mpg_gateway'
            : 'https://ccore.newebpay.com/MPG/mpg_gateway';
    }

    public function createOrder($orderNo, $amount, $itemDesc, $email, $paymentMethods = [])
    {
        $tradeData = [
            'MerchantID' => $this->merchantId,
            'RespondType' => 'JSON',
            'TimeStamp' => time(),
            'Version' => '2.3',
            'MerchantOrderNo' => $orderNo,
            'Amt' => $amount,
            'ItemDesc' => $itemDesc,
            'Email' => $email,
            'ReturnURL' => 'https://yourdomain.com/payment/return',
            'NotifyURL' => 'https://yourdomain.com/payment/notify',
        ];

        foreach ($paymentMethods as $method => $value) {
            $tradeData[$method] = $value;
        }

        $tradeInfo = $this->encrypt(http_build_query($tradeData));
        $tradeSha = $this->generateSha($tradeInfo);

        return [
            'MerchantID' => $this->merchantId,
            'TradeInfo' => $tradeInfo,
            'TradeSha' => $tradeSha,
            'Version' => '2.3',
            'ApiUrl' => $this->apiUrl,
        ];
    }

    public function handleNotify($tradeInfo)
    {
        $decrypted = $this->decrypt($tradeInfo);
        return json_decode($decrypted, true);
    }

    private function encrypt($data)
    {
        $padded = $this->addPadding($data);
        $encrypted = openssl_encrypt($padded, 'AES-256-CBC', $this->hashKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $this->hashIv);
        return bin2hex($encrypted);
    }

    private function decrypt($data)
    {
        $decrypted = openssl_decrypt(hex2bin($data), 'AES-256-CBC', $this->hashKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $this->hashIv);
        return rtrim($decrypted, "\x00..\x1F");
    }

    private function generateSha($tradeInfo)
    {
        return strtoupper(hash('sha256',
            "HashKey={$this->hashKey}&{$tradeInfo}&HashIV={$this->hashIv}"));
    }

    private function addPadding($str, $block = 32)
    {
        $pad = $block - (strlen($str) % $block);
        return $str . str_repeat(chr($pad), $pad);
    }
}
```

### Node.js 完整範例

```javascript
const crypto = require('crypto');

class NewebPayService {
  constructor() {
    this.merchantId = process.env.NEWEBPAY_MERCHANT_ID;
    this.hashKey = process.env.NEWEBPAY_HASH_KEY;
    this.hashIv = process.env.NEWEBPAY_HASH_IV;
    this.apiUrl = process.env.NEWEBPAY_ENV === 'production'
      ? 'https://core.newebpay.com/MPG/mpg_gateway'
      : 'https://ccore.newebpay.com/MPG/mpg_gateway';
  }

  createOrder(orderNo, amount, itemDesc, email, paymentMethods = {}) {
    const tradeData = {
      MerchantID: this.merchantId,
      RespondType: 'JSON',
      TimeStamp: Math.floor(Date.now() / 1000),
      Version: '2.3',
      MerchantOrderNo: orderNo,
      Amt: amount,
      ItemDesc: itemDesc,
      Email: email,
      ReturnURL: 'https://yourdomain.com/payment/return',
      NotifyURL: 'https://yourdomain.com/payment/notify',
      ...paymentMethods,
    };

    const tradeInfo = this.encrypt(new URLSearchParams(tradeData).toString());
    const tradeSha = this.generateSha(tradeInfo);

    return {
      MerchantID: this.merchantId,
      TradeInfo: tradeInfo,
      TradeSha: tradeSha,
      Version: '2.3',
      ApiUrl: this.apiUrl,
    };
  }

  handleNotify(tradeInfo) {
    const decrypted = this.decrypt(tradeInfo);
    return JSON.parse(decrypted);
  }

  encrypt(data) {
    const cipher = crypto.createCipheriv('aes-256-cbc', this.hashKey, this.hashIv);
    let encrypted = cipher.update(data, 'utf8', 'hex');
    encrypted += cipher.final('hex');
    return encrypted;
  }

  decrypt(data) {
    const decipher = crypto.createDecipheriv('aes-256-cbc', this.hashKey, this.hashIv);
    let decrypted = decipher.update(data, 'hex', 'utf8');
    decrypted += decipher.final('utf8');
    return decrypted.replace(/[\x00-\x1F]+$/g, '');
  }

  generateSha(tradeInfo) {
    return crypto.createHash('sha256')
      .update(`HashKey=${this.hashKey}&${tradeInfo}&HashIV=${this.hashIv}`)
      .digest('hex').toUpperCase();
  }
}

module.exports = NewebPayService;
```

---

## 詳細參考文件

- [完整交易參數](references/mpg-transaction.md)
- [回應參數說明](references/response-parameters.md)
- [錯誤代碼](references/error-codes.md)
- [常見情境](references/use-cases.md)
- [疑難排解](references/troubleshooting.md)
