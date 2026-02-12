# KryptoGO Payment 收款程式碼範例

## 目錄

- [React SDK 範例](#react-sdk-範例)
- [Node.js API 完整範例](#nodejs-api-完整範例)
- [Python API 完整範例](#python-api-完整範例)

---

## React SDK 範例

### Provider 設定

```tsx
// app/providers.tsx
'use client';

import { WagmiProvider } from 'wagmi';
import { getDefaultConfig, KryptogoKitProvider } from '@kryptogo/kryptogokit-sdk-react';
import { QueryClientProvider, QueryClient } from '@tanstack/react-query';
import '@kryptogo/kryptogokit-sdk-react/styles.css';

const queryClient = new QueryClient();
const clientId = process.env.NEXT_PUBLIC_KRYPTOGO_CLIENT_ID!;
const config = getDefaultConfig();

export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <WagmiProvider config={config}>
      <QueryClientProvider client={queryClient}>
        <KryptogoKitProvider clientId={clientId}>
          {children}
        </KryptogoKitProvider>
      </QueryClientProvider>
    </WagmiProvider>
  );
}
```

### 基本支付元件

```tsx
// components/PaymentButton.tsx
import { usePayment } from '@kryptogo/payment';

export function PaymentButton({ amount, currency = 'TWD' }: {
  amount: string;
  currency?: 'TWD' | 'USD';
}) {
  const {
    openPaymentModal,
    data,
    txHash,
    isLoading,
    isSuccess,
    isError,
    error,
  } = usePayment();

  return (
    <div>
      <button
        onClick={() => openPaymentModal({
          fiat_amount: amount,
          fiat_currency: currency,
        })}
        disabled={isLoading}
      >
        {isLoading ? '處理中...' : `付款 ${amount} ${currency}`}
      </button>

      {isSuccess && <p>付款成功！交易 Hash: {txHash}</p>}
      {isError && <p>付款失敗：{error?.message}</p>}
    </div>
  );
}
```

### 進階支付元件（含 Callback 與 Order Data）

```tsx
// components/CheckoutForm.tsx
import { usePayment } from '@kryptogo/payment';
import { useState } from 'react';

export function CheckoutForm({ orderId, productName, price }: {
  orderId: string;
  productName: string;
  price: string;
}) {
  const { openPaymentModal, data, isLoading, isSuccess, isError } = usePayment();

  const handleCheckout = () => {
    openPaymentModal({
      fiat_amount: price,
      fiat_currency: 'TWD',
      callback_url: 'https://your-server.com/payment/callback',
      order_data: {
        order_id: orderId,
        product_name: productName,
      },
      group_key: 'product_purchase',
    });
  };

  return (
    <div>
      <h3>{productName}</h3>
      <p>價格：{price} TWD</p>
      <button onClick={handleCheckout} disabled={isLoading}>
        {isLoading ? '支付處理中...' : '確認付款'}
      </button>

      {isSuccess && (
        <div>
          <p>付款成功！</p>
          <p>Payment ID: {data?.payment_intent_id}</p>
          <p>Tx Hash: {data?.payment_tx_hash}</p>
        </div>
      )}

      {isError && <p>支付失敗，請稍後重試</p>}
    </div>
  );
}
```

### 恢復未完成的支付

```tsx
// components/ResumePayment.tsx
import { usePayment } from '@kryptogo/payment';

export function ResumePayment({ paymentIntentId }: { paymentIntentId: string }) {
  const { openPaymentModal, isLoading, isSuccess } = usePayment();

  return (
    <button
      onClick={() => openPaymentModal({ payment_intent_id: paymentIntentId })}
      disabled={isLoading}
    >
      繼續付款
    </button>
  );
}
```

---

## Node.js API 完整範例

```javascript
const axios = require('axios');

class KryptoGoPaymentService {
  constructor() {
    this.clientId = process.env.KRYPTOGO_CLIENT_ID;
    this.apiKey = process.env.KRYPTOGO_STUDIO_API_KEY;
    this.origin = process.env.KRYPTOGO_ORIGIN;
    this.baseUrl = 'https://wallet.kryptogo.app';
  }

  get headers() {
    return {
      'Content-Type': 'application/json',
      'X-Client-ID': this.clientId,
      'Origin': this.origin,
      'X-STUDIO-API-KEY': this.apiKey,
    };
  }

  async createPaymentIntent(fiatAmount, fiatCurrency, options = {}) {
    const response = await axios.post(
      `${this.baseUrl}/v1/studio/api/payment/intent`,
      {
        fiat_amount: fiatAmount,
        fiat_currency: fiatCurrency,
        callback_url: options.callbackUrl,
        order_data: options.orderData,
        group_key: options.groupKey,
      },
      { headers: this.headers }
    );
    return response.data;
  }

  async getPaymentIntent(paymentIntentId) {
    const response = await axios.get(
      `${this.baseUrl}/v1/studio/api/payment/intent/${paymentIntentId}`,
      { headers: this.headers }
    );
    return response.data;
  }

  async listPaymentIntents(filters = {}) {
    const response = await axios.get(
      `${this.baseUrl}/v1/studio/api/payment/intents`,
      {
        headers: this.headers,
        params: filters,
      }
    );
    return response.data;
  }

  async transferTokens(chainId, contractAddress, amount, walletAddress) {
    const response = await axios.post(
      `${this.baseUrl}/v1/studio/api/asset_pro/transfer`,
      {
        chain_id: chainId,
        contract_address: contractAddress,
        amount: amount,
        wallet_address: walletAddress,
      },
      {
        headers: {
          'Content-Type': 'application/json',
          'X-STUDIO-API-KEY': this.apiKey,
        },
      }
    );
    return response.data;
  }
}

module.exports = KryptoGoPaymentService;
```

### 使用範例 (Express)

```javascript
const express = require('express');
const KryptoGoPaymentService = require('./services/kryptogo-payment');

const app = express();
app.use(express.json());

const payment = new KryptoGoPaymentService();

// 建立 Payment Intent
app.post('/api/checkout', async (req, res) => {
  try {
    const result = await payment.createPaymentIntent(
      req.body.amount,
      req.body.currency || 'TWD',
      {
        callbackUrl: 'https://your-domain.com/api/payment/callback',
        orderData: { order_id: req.body.orderId },
        groupKey: 'checkout',
      }
    );
    res.json(result);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Webhook 回調
app.post('/api/payment/callback', (req, res) => {
  const payment = req.body;

  switch (payment.status) {
    case 'success':
      console.log('付款成功', {
        txHash: payment.payment_tx_hash,
        amount: payment.received_crypto_amount,
      });
      break;
    case 'expired':
      console.log('付款逾時', payment.payment_intent_id);
      break;
    case 'insufficient_not_refunded':
      console.log('金額不足，等待退款', payment.payment_intent_id);
      break;
    case 'insufficient_refunded':
      console.log('已退款', {
        refundHash: payment.refund_tx_hash,
        refundAmount: payment.refund_amount,
      });
      break;
  }

  res.status(200).send();
});

app.listen(3001, () => console.log('Server running on port 3001'));
```

---

## Python API 完整範例

```python
import os
import requests

class KryptoGoPaymentService:
    def __init__(self):
        self.client_id = os.getenv('KRYPTOGO_CLIENT_ID')
        self.api_key = os.getenv('KRYPTOGO_STUDIO_API_KEY')
        self.origin = os.getenv('KRYPTOGO_ORIGIN')
        self.base_url = 'https://wallet.kryptogo.app'

    @property
    def headers(self):
        return {
            'Content-Type': 'application/json',
            'X-Client-ID': self.client_id,
            'Origin': self.origin,
            'X-STUDIO-API-KEY': self.api_key,
        }

    def create_payment_intent(self, fiat_amount, fiat_currency, callback_url=None, order_data=None, group_key=None):
        response = requests.post(
            f'{self.base_url}/v1/studio/api/payment/intent',
            json={
                'fiat_amount': fiat_amount,
                'fiat_currency': fiat_currency,
                'callback_url': callback_url,
                'order_data': order_data,
                'group_key': group_key,
            },
            headers=self.headers
        )
        return response.json()

    def get_payment_intent(self, payment_intent_id):
        response = requests.get(
            f'{self.base_url}/v1/studio/api/payment/intent/{payment_intent_id}',
            headers=self.headers
        )
        return response.json()

    def list_payment_intents(self, **filters):
        response = requests.get(
            f'{self.base_url}/v1/studio/api/payment/intents',
            headers=self.headers,
            params=filters
        )
        return response.json()

    def transfer_tokens(self, chain_id, contract_address, amount, wallet_address):
        response = requests.post(
            f'{self.base_url}/v1/studio/api/asset_pro/transfer',
            json={
                'chain_id': chain_id,
                'contract_address': contract_address,
                'amount': amount,
                'wallet_address': wallet_address,
            },
            headers={
                'Content-Type': 'application/json',
                'X-STUDIO-API-KEY': self.api_key,
            }
        )
        return response.json()
```

### 使用範例 (Flask)

```python
from flask import Flask, request, jsonify

app = Flask(__name__)
payment = KryptoGoPaymentService()

@app.route('/api/checkout', methods=['POST'])
def checkout():
    data = request.json
    result = payment.create_payment_intent(
        fiat_amount=data['amount'],
        fiat_currency=data.get('currency', 'TWD'),
        callback_url='https://your-domain.com/api/payment/callback',
        order_data={'order_id': data.get('order_id')},
        group_key='checkout'
    )
    return jsonify(result)

@app.route('/api/payment/callback', methods=['POST'])
def payment_callback():
    payment_data = request.json

    if payment_data['status'] == 'success':
        print('付款成功', {
            'txHash': payment_data['payment_tx_hash'],
            'amount': payment_data['received_crypto_amount'],
        })
    elif payment_data['status'] == 'expired':
        print('付款逾時', payment_data['payment_intent_id'])
    elif payment_data['status'] == 'insufficient_not_refunded':
        print('金額不足，等待退款')
    elif payment_data['status'] == 'insufficient_refunded':
        print('已退款', {
            'refundHash': payment_data['refund_tx_hash'],
            'refundAmount': payment_data['refund_amount'],
        })

    return '', 200

if __name__ == '__main__':
    app.run(port=3001)
```
