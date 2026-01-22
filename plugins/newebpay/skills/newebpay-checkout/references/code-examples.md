# MPG 支付串接程式碼範例

## PHP 完整範例

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

### 使用範例

```php
$service = new NewebPayService();

// 建立訂單
$formData = $service->createOrder(
    'ORDER_' . time(),
    1000,
    '測試商品',
    'customer@example.com',
    ['CREDIT' => 1, 'LINEPAY' => 1]
);

// 處理回調
$result = $service->handleNotify($_POST['TradeInfo']);
if ($result['Status'] === 'SUCCESS') {
    // 更新訂單狀態
}
```

---

## Node.js 完整範例

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

### 使用範例 (Express)

```javascript
const express = require('express');
const NewebPayService = require('./services/newebpay');

const app = express();
const newebpay = new NewebPayService();

// 建立支付頁面
app.get('/checkout', (req, res) => {
  const formData = newebpay.createOrder(
    `ORDER_${Date.now()}`,
    1000,
    '測試商品',
    'customer@example.com',
    { CREDIT: 1, LINEPAY: 1 }
  );
  res.render('checkout', { formData });
});

// 背景通知
app.post('/payment/notify', express.urlencoded({ extended: true }), (req, res) => {
  const result = newebpay.handleNotify(req.body.TradeInfo);
  if (result.Status === 'SUCCESS') {
    // 更新訂單狀態
  }
  res.send('OK');
});
```
