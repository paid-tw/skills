# 退款程式碼範例

## PHP 退款功能

```php
<?php
class NewebPayRefundService
{
    private $merchantId;
    private $hashKey;
    private $hashIv;

    public function __construct()
    {
        $this->merchantId = getenv('NEWEBPAY_MERCHANT_ID');
        $this->hashKey = getenv('NEWEBPAY_HASH_KEY');
        $this->hashIv = getenv('NEWEBPAY_HASH_IV');
    }

    public function refundCreditCard($orderNo, $amount)
    {
        $apiUrl = getenv('NEWEBPAY_ENV') === 'production'
            ? 'https://core.newebpay.com/API/CreditCard/Close'
            : 'https://ccore.newebpay.com/API/CreditCard/Close';

        $data = [
            'RespondType' => 'JSON',
            'Version' => '1.1',
            'Amt' => $amount,
            'MerchantOrderNo' => $orderNo,
            'TimeStamp' => time(),
            'IndexType' => 1,
            'CloseType' => 2,
        ];

        $postData = $this->encrypt(http_build_query($data));

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'MerchantID_' => $this->merchantId,
                'PostData_' => $postData,
            ]),
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function refundEWallet($tradeNo, $orderNo, $amount)
    {
        $apiUrl = getenv('NEWEBPAY_ENV') === 'production'
            ? 'https://core.newebpay.com/API/EWallet/Refund'
            : 'https://ccore.newebpay.com/API/EWallet/Refund';

        $data = [
            'RespondType' => 'JSON',
            'Version' => '1.0',
            'TimeStamp' => time(),
            'TradeNo' => $tradeNo,
            'MerchantOrderNo' => $orderNo,
            'Amt' => $amount,
        ];

        $postData = $this->encrypt(http_build_query($data));

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'MerchantID_' => $this->merchantId,
                'PostData_' => $postData,
            ]),
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private function encrypt($data)
    {
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $this->hashKey,
            OPENSSL_RAW_DATA, $this->hashIv);
        return bin2hex($encrypted);
    }
}
```

### 使用範例

```php
$refund = new NewebPayRefundService();

// 信用卡退款
$result = $refund->refundCreditCard('ORDER_123456', 500);

// 電子錢包退款
$result = $refund->refundEWallet('24010112345678', 'ORDER_123456', 500);
```

---

## Node.js 退款功能

```javascript
const crypto = require('crypto');
const axios = require('axios');

class NewebPayRefundService {
  constructor() {
    this.merchantId = process.env.NEWEBPAY_MERCHANT_ID;
    this.hashKey = process.env.NEWEBPAY_HASH_KEY;
    this.hashIv = process.env.NEWEBPAY_HASH_IV;
    this.isProduction = process.env.NEWEBPAY_ENV === 'production';
  }

  async refundCreditCard(orderNo, amount) {
    const apiUrl = this.isProduction
      ? 'https://core.newebpay.com/API/CreditCard/Close'
      : 'https://ccore.newebpay.com/API/CreditCard/Close';

    const data = {
      RespondType: 'JSON',
      Version: '1.1',
      Amt: amount,
      MerchantOrderNo: orderNo,
      TimeStamp: Math.floor(Date.now() / 1000),
      IndexType: 1,
      CloseType: 2,
    };

    const postData = this.encrypt(new URLSearchParams(data).toString());

    const { data: result } = await axios.post(apiUrl,
      new URLSearchParams({
        MerchantID_: this.merchantId,
        PostData_: postData,
      }).toString(),
      { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }
    );

    return result;
  }

  async refundEWallet(tradeNo, orderNo, amount) {
    const apiUrl = this.isProduction
      ? 'https://core.newebpay.com/API/EWallet/Refund'
      : 'https://ccore.newebpay.com/API/EWallet/Refund';

    const data = {
      RespondType: 'JSON',
      Version: '1.0',
      TimeStamp: Math.floor(Date.now() / 1000),
      TradeNo: tradeNo,
      MerchantOrderNo: orderNo,
      Amt: amount,
    };

    const postData = this.encrypt(new URLSearchParams(data).toString());

    const { data: result } = await axios.post(apiUrl,
      new URLSearchParams({
        MerchantID_: this.merchantId,
        PostData_: postData,
      }).toString(),
      { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }
    );

    return result;
  }

  encrypt(data) {
    const cipher = crypto.createCipheriv('aes-256-cbc', this.hashKey, this.hashIv);
    let encrypted = cipher.update(data, 'utf8', 'hex');
    encrypted += cipher.final('hex');
    return encrypted;
  }
}

module.exports = NewebPayRefundService;
```

### 使用範例

```javascript
const NewebPayRefundService = require('./services/newebpay-refund');

const refund = new NewebPayRefundService();

// 信用卡退款
const result = await refund.refundCreditCard('ORDER_123456', 500);

// 電子錢包退款
const result = await refund.refundEWallet('24010112345678', 'ORDER_123456', 500);
```
