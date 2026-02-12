# KryptoGO Payment Webhook 程式碼範例

## 目錄

- [Node.js (Express) 範例](#nodejs-express-範例)
- [Node.js (NestJS) 範例](#nodejs-nestjs-範例)
- [Python (Flask) 範例](#python-flask-範例)
- [Python (FastAPI) 範例](#python-fastapi-範例)

---

## Node.js (Express) 範例

```javascript
const express = require('express');
const router = express.Router();

router.post('/api/payment/callback', express.json(), async (req, res) => {
  const payment = req.body;
  const { payment_intent_id, status } = payment;

  // 1. 驗證 payment_intent_id 存在
  const order = await Order.findOne({
    where: { payment_intent_id },
  });

  if (!order) {
    console.warn('Unknown payment_intent_id:', payment_intent_id);
    return res.status(200).send(); // 仍回應 200 避免重試
  }

  // 2. 冪等檢查：若訂單已為最終狀態則跳過
  if (['success', 'expired', 'insufficient_refunded'].includes(order.status)) {
    return res.status(200).send();
  }

  // 3. 根據狀態更新訂單
  switch (status) {
    case 'success':
      await order.update({
        status: 'paid',
        tx_hash: payment.payment_tx_hash,
        received_amount: payment.received_crypto_amount,
        aggregated_amount: payment.aggregated_crypto_amount,
      });
      // 發送確認通知給用戶
      await notifyUser(order.user_id, 'payment_success');
      break;

    case 'expired':
      await order.update({ status: 'expired' });
      break;

    case 'insufficient_not_refunded':
      await order.update({
        status: 'insufficient',
        received_amount: payment.received_crypto_amount,
      });
      break;

    case 'insufficient_refunded':
      await order.update({
        status: 'refunded',
        refund_tx_hash: payment.refund_tx_hash,
        refund_amount: payment.refund_amount,
      });
      break;
  }

  // 4. 回應 200 確認收到
  res.status(200).send();
});

module.exports = router;
```

---

## Node.js (NestJS) 範例

```typescript
import { Controller, Post, Body, HttpCode } from '@nestjs/common';

interface PaymentCallback {
  payment_intent_id: string;
  client_id: string;
  fiat_amount: string;
  fiat_currency: 'TWD' | 'USD';
  status: 'pending' | 'success' | 'expired' | 'insufficient_not_refunded' | 'insufficient_refunded';
  payment_chain_id: string;
  symbol: string;
  crypto_amount: string;
  payment_tx_hash: string | null;
  received_crypto_amount: string | null;
  aggregated_crypto_amount: string | null;
  refund_tx_hash: string | null;
  refund_amount: string | null;
  order_data: Record<string, any> | null;
  callback_url: string | null;
  group_key: string | null;
}

@Controller('api/payment')
export class PaymentController {
  constructor(private readonly orderService: OrderService) {}

  @Post('callback')
  @HttpCode(200)
  async handleCallback(@Body() payment: PaymentCallback) {
    const order = await this.orderService.findByPaymentIntentId(
      payment.payment_intent_id,
    );

    if (!order) return;

    // 冪等檢查
    if (['paid', 'expired', 'refunded'].includes(order.status)) return;

    switch (payment.status) {
      case 'success':
        await this.orderService.markPaid(order.id, {
          txHash: payment.payment_tx_hash,
          receivedAmount: payment.received_crypto_amount,
          aggregatedAmount: payment.aggregated_crypto_amount,
        });
        break;

      case 'expired':
        await this.orderService.markExpired(order.id);
        break;

      case 'insufficient_not_refunded':
        await this.orderService.markInsufficient(order.id, {
          receivedAmount: payment.received_crypto_amount,
        });
        break;

      case 'insufficient_refunded':
        await this.orderService.markRefunded(order.id, {
          refundTxHash: payment.refund_tx_hash,
          refundAmount: payment.refund_amount,
        });
        break;
    }
  }
}
```

---

## Python (Flask) 範例

```python
from flask import Flask, request

app = Flask(__name__)

@app.route('/api/payment/callback', methods=['POST'])
def payment_callback():
    payment = request.json
    payment_intent_id = payment.get('payment_intent_id')
    status = payment.get('status')

    # 1. 驗證 payment_intent_id 存在
    order = Order.query.filter_by(payment_intent_id=payment_intent_id).first()
    if not order:
        return '', 200  # 仍回應 200

    # 2. 冪等檢查
    if order.status in ('paid', 'expired', 'refunded'):
        return '', 200

    # 3. 根據狀態更新訂單
    if status == 'success':
        order.status = 'paid'
        order.tx_hash = payment.get('payment_tx_hash')
        order.received_amount = payment.get('received_crypto_amount')
        order.aggregated_amount = payment.get('aggregated_crypto_amount')
        db.session.commit()
        notify_user(order.user_id, 'payment_success')

    elif status == 'expired':
        order.status = 'expired'
        db.session.commit()

    elif status == 'insufficient_not_refunded':
        order.status = 'insufficient'
        order.received_amount = payment.get('received_crypto_amount')
        db.session.commit()

    elif status == 'insufficient_refunded':
        order.status = 'refunded'
        order.refund_tx_hash = payment.get('refund_tx_hash')
        order.refund_amount = payment.get('refund_amount')
        db.session.commit()

    return '', 200
```

---

## Python (FastAPI) 範例

```python
from fastapi import FastAPI, Response
from pydantic import BaseModel
from typing import Optional, Dict, Any

app = FastAPI()

class PaymentCallback(BaseModel):
    payment_intent_id: str
    client_id: str
    fiat_amount: str
    fiat_currency: str
    status: str
    payment_chain_id: str
    symbol: str
    crypto_amount: str
    payment_tx_hash: Optional[str] = None
    received_crypto_amount: Optional[str] = None
    aggregated_crypto_amount: Optional[str] = None
    refund_tx_hash: Optional[str] = None
    refund_amount: Optional[str] = None
    order_data: Optional[Dict[str, Any]] = None
    callback_url: Optional[str] = None
    group_key: Optional[str] = None

@app.post('/api/payment/callback')
async def payment_callback(payment: PaymentCallback, response: Response):
    order = await get_order_by_payment_intent_id(payment.payment_intent_id)

    if not order:
        response.status_code = 200
        return

    # 冪等檢查
    if order.status in ('paid', 'expired', 'refunded'):
        response.status_code = 200
        return

    if payment.status == 'success':
        await update_order(order.id, status='paid',
                          tx_hash=payment.payment_tx_hash,
                          received_amount=payment.received_crypto_amount)

    elif payment.status == 'expired':
        await update_order(order.id, status='expired')

    elif payment.status == 'insufficient_not_refunded':
        await update_order(order.id, status='insufficient',
                          received_amount=payment.received_crypto_amount)

    elif payment.status == 'insufficient_refunded':
        await update_order(order.id, status='refunded',
                          refund_tx_hash=payment.refund_tx_hash,
                          refund_amount=payment.refund_amount)

    response.status_code = 200
```
