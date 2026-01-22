# 交易查詢程式碼範例

## PHP 查詢功能

```php
<?php
class NewebPayQueryService
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
            ? 'https://core.newebpay.com/API/QueryTradeInfo'
            : 'https://ccore.newebpay.com/API/QueryTradeInfo';
    }

    public function queryTrade($orderNo, $amount)
    {
        $checkValue = $this->generateCheckValue($orderNo, $amount);

        $params = [
            'MerchantID' => $this->merchantId,
            'Version' => '1.3',
            'RespondType' => 'JSON',
            'CheckValue' => $checkValue,
            'TimeStamp' => time(),
            'MerchantOrderNo' => $orderNo,
            'Amt' => $amount,
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private function generateCheckValue($orderNo, $amount)
    {
        $str = "IV={$this->hashIv}&Amt={$amount}&MerchantID={$this->merchantId}&MerchantOrderNo={$orderNo}&Key={$this->hashKey}";
        return strtoupper(hash('sha256', $str));
    }
}
```

### 使用範例

```php
$query = new NewebPayQueryService();
$result = $query->queryTrade('ORDER_123456', 1000);

if ($result['Status'] === 'SUCCESS') {
    $tradeStatus = $result['Result']['TradeStatus'];
    // 0=未付款, 1=已付款, 2=失敗, 3=取消, 6=退款
}
```

---

## Node.js 查詢功能

```javascript
const crypto = require('crypto');
const axios = require('axios');

class NewebPayQueryService {
  constructor() {
    this.merchantId = process.env.NEWEBPAY_MERCHANT_ID;
    this.hashKey = process.env.NEWEBPAY_HASH_KEY;
    this.hashIv = process.env.NEWEBPAY_HASH_IV;
    this.apiUrl = process.env.NEWEBPAY_ENV === 'production'
      ? 'https://core.newebpay.com/API/QueryTradeInfo'
      : 'https://ccore.newebpay.com/API/QueryTradeInfo';
  }

  async queryTrade(orderNo, amount) {
    const checkValue = this.generateCheckValue(orderNo, amount);

    const params = new URLSearchParams({
      MerchantID: this.merchantId,
      Version: '1.3',
      RespondType: 'JSON',
      CheckValue: checkValue,
      TimeStamp: Math.floor(Date.now() / 1000),
      MerchantOrderNo: orderNo,
      Amt: amount,
    });

    const { data } = await axios.post(this.apiUrl, params.toString(), {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    });

    return data;
  }

  generateCheckValue(orderNo, amount) {
    const str = `IV=${this.hashIv}&Amt=${amount}&MerchantID=${this.merchantId}&MerchantOrderNo=${orderNo}&Key=${this.hashKey}`;
    return crypto.createHash('sha256').update(str).digest('hex').toUpperCase();
  }
}

module.exports = NewebPayQueryService;
```

### 使用範例

```javascript
const NewebPayQueryService = require('./services/newebpay-query');

const query = new NewebPayQueryService();
const result = await query.queryTrade('ORDER_123456', 1000);

if (result.Status === 'SUCCESS') {
  const tradeStatus = result.Result.TradeStatus;
  // 0=未付款, 1=已付款, 2=失敗, 3=取消, 6=退款
}
```
