# KryptoGO Payment 錯誤代碼

## API 回應格式

### 成功回應

```json
{
  "code": 0,
  "data": { ... }
}
```

### 錯誤回應

```json
{
  "code": 4015,
  "message": "Balance not enough",
  "data": {
    "insufficient_amount": "123.456"
  }
}
```

## 常見錯誤代碼

| Code | Message | 說明 | 解決方式 |
|:----:|---------|------|---------|
| 0 | Success | 成功 | - |
| 4001 | Invalid client_id | Client ID 無效或不存在 | 確認 `X-Client-ID` header 正確 |
| 4002 | Invalid API key | API Key 無效 | 確認 `X-STUDIO-API-KEY` 正確 |
| 4003 | Invalid origin | Origin 不匹配 | 確認 `Origin` header 與 Studio 設定一致 |
| 4004 | Invalid fiat_amount | 法幣金額無效 | 確認金額格式正確且 >= 0.01 |
| 4005 | Invalid fiat_currency | 法幣幣別無效 | 僅支援 `TWD` 或 `USD` |
| 4006 | Invalid payment_intent_id | Payment Intent ID 不存在 | 確認 ID 正確 |
| 4010 | Rate limit exceeded | 超過 API 速率限制 | 降低請求頻率 |
| 4015 | Balance not enough | 餘額不足（轉帳時）| 確認帳戶有足夠餘額 |
| 5000 | Internal server error | 伺服器內部錯誤 | 稍後重試，或聯繫技術支援 |

## 錯誤處理範例

### Node.js

```javascript
try {
  const result = await payment.createPaymentIntent('100', 'TWD');
  if (result.code === 0) {
    // 成功
    console.log('Payment Intent created:', result.data.payment_intent_id);
  } else {
    // API 錯誤
    console.error(`API Error [${result.code}]: ${result.message}`);
  }
} catch (error) {
  if (error.response) {
    // HTTP 錯誤
    console.error('HTTP Error:', error.response.status, error.response.data);
  } else {
    // 網路錯誤
    console.error('Network Error:', error.message);
  }
}
```

### Python

```python
try:
    result = payment.create_payment_intent('100', 'TWD')
    if result.get('code') == 0:
        print('Payment Intent created:', result['data']['payment_intent_id'])
    else:
        print(f"API Error [{result.get('code')}]: {result.get('message')}")
except requests.exceptions.HTTPError as e:
    print(f"HTTP Error: {e.response.status_code} {e.response.text}")
except requests.exceptions.RequestException as e:
    print(f"Network Error: {e}")
```

## Payment Status 異常處理

| 狀態 | 處理邏輯 |
|------|---------|
| `expired` | 允許用戶重新建立 Payment Intent |
| `insufficient_not_refunded` | 記錄異常，系統會自動處理退款 |
| `insufficient_refunded` | 記錄退款 tx_hash，通知用戶重新支付 |
