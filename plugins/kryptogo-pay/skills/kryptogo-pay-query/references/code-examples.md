# KryptoGO Payment 查詢程式碼範例

## 目錄

- [Node.js 範例](#nodejs-範例)
- [Python 範例](#python-範例)
- [cURL 範例](#curl-範例)

---

## Node.js 範例

### 查詢單筆 Payment Intent

```javascript
const axios = require('axios');

async function getPaymentIntent(paymentIntentId) {
  const response = await axios.get(
    `https://wallet.kryptogo.app/v1/studio/api/payment/intent/${paymentIntentId}`,
    {
      headers: {
        'X-Client-ID': process.env.KRYPTOGO_CLIENT_ID,
        'Origin': process.env.KRYPTOGO_ORIGIN,
        'X-STUDIO-API-KEY': process.env.KRYPTOGO_STUDIO_API_KEY,
      },
    }
  );
  return response.data;
}

// 使用範例
const result = await getPaymentIntent('2MFV9kRD0IyNWauowhq2Bp3IUb3BWKPi');
if (result.code === 0) {
  console.log('狀態:', result.data.status);
  console.log('金額:', result.data.fiat_amount, result.data.fiat_currency);
}
```

### 列出 Payment Intents

```javascript
async function listPaymentIntents(filters = {}) {
  const response = await axios.get(
    'https://wallet.kryptogo.app/v1/studio/api/payment/intents',
    {
      headers: {
        'X-Client-ID': process.env.KRYPTOGO_CLIENT_ID,
        'Origin': process.env.KRYPTOGO_ORIGIN,
        'X-STUDIO-API-KEY': process.env.KRYPTOGO_STUDIO_API_KEY,
      },
      params: filters,
    }
  );
  return response.data;
}

// 使用範例
const intents = await listPaymentIntents();
console.log('所有 Payment Intents:', intents);
```

### Express 端點整合

```javascript
const express = require('express');
const router = express.Router();

// 查詢單筆
router.get('/api/payment/intent/:id', async (req, res) => {
  try {
    const result = await getPaymentIntent(req.params.id);
    res.json(result);
  } catch (error) {
    res.status(error.response?.status || 500).json({
      error: error.response?.data || error.message,
    });
  }
});

// 列出所有
router.get('/api/payment/intents', async (req, res) => {
  try {
    const result = await listPaymentIntents(req.query);
    res.json(result);
  } catch (error) {
    res.status(error.response?.status || 500).json({
      error: error.response?.data || error.message,
    });
  }
});
```

### 輪詢支付狀態（Webhook 備援）

```javascript
async function pollPaymentStatus(paymentIntentId, maxAttempts = 60, intervalMs = 5000) {
  for (let i = 0; i < maxAttempts; i++) {
    const result = await getPaymentIntent(paymentIntentId);

    if (result.code === 0 && result.data.status !== 'pending') {
      return result.data;
    }

    await new Promise(resolve => setTimeout(resolve, intervalMs));
  }

  throw new Error('Payment status polling timeout');
}

// 使用範例：每 5 秒查詢一次，最多查 60 次（5 分鐘）
const finalStatus = await pollPaymentStatus('payment_intent_id_here');
console.log('最終狀態:', finalStatus.status);
```

---

## Python 範例

### 查詢單筆 Payment Intent

```python
import os
import requests

def get_payment_intent(payment_intent_id):
    response = requests.get(
        f'https://wallet.kryptogo.app/v1/studio/api/payment/intent/{payment_intent_id}',
        headers={
            'X-Client-ID': os.getenv('KRYPTOGO_CLIENT_ID'),
            'Origin': os.getenv('KRYPTOGO_ORIGIN'),
            'X-STUDIO-API-KEY': os.getenv('KRYPTOGO_STUDIO_API_KEY'),
        }
    )
    return response.json()

# 使用範例
result = get_payment_intent('2MFV9kRD0IyNWauowhq2Bp3IUb3BWKPi')
if result.get('code') == 0:
    print('狀態:', result['data']['status'])
    print('金額:', result['data']['fiat_amount'], result['data']['fiat_currency'])
```

### 列出 Payment Intents

```python
def list_payment_intents(**filters):
    response = requests.get(
        'https://wallet.kryptogo.app/v1/studio/api/payment/intents',
        headers={
            'X-Client-ID': os.getenv('KRYPTOGO_CLIENT_ID'),
            'Origin': os.getenv('KRYPTOGO_ORIGIN'),
            'X-STUDIO-API-KEY': os.getenv('KRYPTOGO_STUDIO_API_KEY'),
        },
        params=filters
    )
    return response.json()

# 使用範例
intents = list_payment_intents()
print('所有 Payment Intents:', intents)
```

### 輪詢支付狀態

```python
import time

def poll_payment_status(payment_intent_id, max_attempts=60, interval_sec=5):
    for _ in range(max_attempts):
        result = get_payment_intent(payment_intent_id)
        if result.get('code') == 0 and result['data']['status'] != 'pending':
            return result['data']
        time.sleep(interval_sec)
    raise TimeoutError('Payment status polling timeout')
```

---

## cURL 範例

### 查詢單筆

```bash
curl -X GET \
  https://wallet.kryptogo.app/v1/studio/api/payment/intent/YOUR_PAYMENT_INTENT_ID \
  -H 'X-Client-ID: YOUR_CLIENT_ID' \
  -H 'Origin: YOUR_DOMAIN' \
  -H 'X-STUDIO-API-KEY: YOUR_API_KEY'
```

### 列出所有

```bash
curl -X GET \
  https://wallet.kryptogo.app/v1/studio/api/payment/intents \
  -H 'X-Client-ID: YOUR_CLIENT_ID' \
  -H 'Origin: YOUR_DOMAIN' \
  -H 'X-STUDIO-API-KEY: YOUR_API_KEY'
```
