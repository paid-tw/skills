---
name: newebpay-query
description: >
  Implements NewebPay transaction query functionality using QueryTradeInfo API.
  Use when building order status checking, transaction verification, or payment
  confirmation features for 藍新金流.
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

# 藍新金流交易查詢任務

你的任務是在用戶的專案中實作藍新金流交易查詢功能。

## Step 1: 確認需求

詢問用戶：

1. **查詢情境**：需要什麼查詢功能？
   - 單筆訂單查詢（客戶查詢、客服查詢）
   - 批次對帳（每日/定時對帳）
   - 支付狀態確認（NotifyURL 備援）

2. **專案框架**：你使用什麼框架？
   - 確認是否已有 NewebPay 環境設定

## Step 2: 建立查詢功能

在現有的支付模組中加入查詢方法，或建立新模組。

**核心功能:**
1. `generateCheckValue(orderNo, amount)` - 產生 SHA256 檢核碼
2. `queryTrade(orderNo, amount)` - 查詢單筆交易

## Step 3: 實作程式碼

根據框架加入查詢功能。

## Step 4: 整合到應用

建議整合方式：
- **API 端點**: `GET /api/orders/:orderNo/status`
- **管理後台**: 訂單詳情頁顯示即時狀態
- **定時任務**: 對帳排程

---

## API 參考

### 端點

| 環境 | URL |
|------|-----|
| 測試 | `https://ccore.newebpay.com/API/QueryTradeInfo` |
| 正式 | `https://core.newebpay.com/API/QueryTradeInfo` |

### 請求參數

| 參數 | 類型 | 必填 | 說明 |
|------|------|:----:|------|
| MerchantID | String(15) | ✓ | 商店代號 |
| Version | String | ✓ | `1.3` |
| RespondType | String | ✓ | `JSON` |
| CheckValue | String | ✓ | SHA256 檢核碼 |
| TimeStamp | Number | ✓ | Unix timestamp |
| MerchantOrderNo | String(30) | ✓ | 商店訂單編號 |
| Amt | Number | ✓ | 訂單金額 |

### CheckValue 產生規則

```
原始字串: IV={HashIV}&Amt={金額}&MerchantID={商店代號}&MerchantOrderNo={訂單編號}&Key={HashKey}
結果: SHA256 後轉大寫
```

### TradeStatus 交易狀態

| 值 | 說明 |
|:--:|------|
| 0 | 未付款 |
| 1 | 已付款 |
| 2 | 付款失敗 |
| 3 | 已取消 |
| 6 | 退款 |

---

## 程式碼範本

### PHP 查詢功能

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

// 使用範例
$query = new NewebPayQueryService();
$result = $query->queryTrade('ORDER_123456', 1000);

if ($result['Status'] === 'SUCCESS') {
    $tradeStatus = $result['Result']['TradeStatus'];
    // 0=未付款, 1=已付款, 2=失敗, 3=取消, 6=退款
}
```

### Node.js 查詢功能

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

---

## 常見錯誤

| 代碼 | 說明 | 解決方式 |
|------|------|---------|
| TRA10001 | 查無此筆交易 | 確認訂單編號正確 |
| TRA10002 | CheckValue 檢核錯誤 | 確認參數順序與大小寫 |
| TRA10003 | 時間戳記錯誤 | 確認伺服器時間正確 |
