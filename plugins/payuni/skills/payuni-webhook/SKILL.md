---
name: payuni-webhook
description: >
  Implements PAYUNi webhook handling including signature verification,
  replay attack prevention, and payment status updates. Use when building
  payment notification endpoints for 統一金流.
argument-hint: "[框架: Next.js/Express/其他]"
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

# 統一金流 Webhook 處理任務

你的任務是在用戶的專案中實作統一金流 Webhook 接收與處理功能。

## 串接 Checklist

- [ ] **框架確認** - 確認使用的框架
- [ ] **端點建立** - 建立 Webhook 接收端點
- [ ] **簽名驗證** - 實作 CheckCode 驗證
- [ ] **防重放** - 實作重複請求檢測
- [ ] **狀態更新** - 更新訂單狀態
- [ ] **測試驗證** - 驗證 Webhook 處理流程

---

## Step 1: 確認專案環境

用戶輸入: `$ARGUMENTS`

詢問用戶：

1. **框架類型**：
   - Next.js (App Router / Pages Router)
   - Express / Fastify
   - NestJS
   - 其他

2. **資料庫**：用什麼來儲存訂單？
   - PostgreSQL / MySQL
   - MongoDB
   - Prisma / Drizzle
   - Supabase
   - 其他

## Step 2: 建立 Webhook 端點

### Next.js App Router

```typescript
// app/api/webhooks/payuni/route.ts
import { NextRequest, NextResponse } from 'next/server';
import crypto from 'crypto';

const config = {
  hashKey: process.env.PAYUNI_HASH_KEY!,
  hashIV: process.env.PAYUNI_HASH_IV!,
};

// 簽名驗證（使用 constant-time 比較防止 timing attack）
function verifyCheckCode(params: Record<string, string>): boolean {
  const { CheckCode, ...otherParams } = params;
  if (!CheckCode) return false;

  const sortedKeys = Object.keys(otherParams).sort();
  const paramStr = sortedKeys.map(k => `${k}=${otherParams[k]}`).join('&');
  const signStr = `HashKey=${config.hashKey}&${paramStr}&HashIV=${config.hashIV}`;
  
  const calculated = crypto
    .createHash('sha256')
    .update(signStr)
    .digest('hex')
    .toUpperCase();

  try {
    return crypto.timingSafeEqual(
      Buffer.from(calculated),
      Buffer.from(CheckCode)
    );
  } catch {
    return false;
  }
}

export async function POST(request: NextRequest) {
  try {
    // 解析請求
    const contentType = request.headers.get('content-type');
    let params: Record<string, string>;
    
    if (contentType?.includes('application/json')) {
      params = await request.json();
    } else {
      const formData = await request.formData();
      params = Object.fromEntries(formData.entries()) as Record<string, string>;
    }

    // 驗證簽名
    if (!verifyCheckCode(params)) {
      console.error('[Webhook] Invalid signature');
      return NextResponse.json({ error: 'Invalid signature' }, { status: 401 });
    }

    // 取得訂單資訊
    const { Status, MerchantOrderNo, TradeNo, TradeAmt } = params;

    // TODO: 檢查是否已處理過此 TradeNo（防重放攻擊）
    // const exists = await db.webhookLog.findUnique({ where: { tradeNo: TradeNo } });
    // if (exists) return NextResponse.json({ success: true, message: 'Already processed' });

    if (Status === 'SUCCESS') {
      // TODO: 更新訂單狀態為已付款
      // await db.order.update({
      //   where: { id: MerchantOrderNo },
      //   data: { status: 'paid', paymentDetails: { tradeNo: TradeNo, amount: TradeAmt } }
      // });
      console.log('[Webhook] Payment success:', MerchantOrderNo);
    } else {
      // TODO: 更新訂單狀態為失敗
      console.log('[Webhook] Payment failed:', MerchantOrderNo);
    }

    // TODO: 記錄 webhook 請求（防重放）
    // await db.webhookLog.create({ data: { tradeNo: TradeNo, processedAt: new Date() } });

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error('[Webhook] Error:', error);
    return NextResponse.json({ error: 'Internal error' }, { status: 500 });
  }
}

// 健康檢查端點
export async function GET() {
  return NextResponse.json({
    message: 'PAYUNi Webhook endpoint',
    timestamp: new Date().toISOString(),
  });
}
```

### Express

```typescript
import express from 'express';
import crypto from 'crypto';

const router = express.Router();

router.post('/webhooks/payuni', async (req, res) => {
  try {
    const params = req.body;

    // 驗證簽名
    if (!verifyCheckCode(params)) {
      return res.status(401).json({ error: 'Invalid signature' });
    }

    const { Status, MerchantOrderNo, TradeNo } = params;

    if (Status === 'SUCCESS') {
      // 更新訂單狀態
      console.log('Payment success:', MerchantOrderNo);
    }

    res.json({ success: true });
  } catch (error) {
    console.error('Webhook error:', error);
    res.status(500).json({ error: 'Internal error' });
  }
});

export default router;
```

## Step 3: 實作防重放攻擊

建立 webhook 請求記錄表：

```sql
CREATE TABLE webhook_requests (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider VARCHAR(50) NOT NULL DEFAULT 'payuni',
  trade_no VARCHAR(100) UNIQUE,
  merchant_order_no VARCHAR(100),
  checksum VARCHAR(64),
  status VARCHAR(20) DEFAULT 'processing',
  processed_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_webhook_trade_no ON webhook_requests(trade_no);
CREATE INDEX idx_webhook_checksum ON webhook_requests(checksum);
```

### 防重放檢查邏輯

```typescript
async function checkDuplicate(tradeNo: string): Promise<boolean> {
  const existing = await db.webhookRequest.findUnique({
    where: { tradeNo }
  });
  return !!existing;
}

async function markProcessed(tradeNo: string): Promise<void> {
  await db.webhookRequest.create({
    data: {
      tradeNo,
      provider: 'payuni',
      processedAt: new Date(),
    }
  });
}
```

## Step 4: 測試 Webhook

### 本地測試方法

1. **使用 ngrok 暴露本地服務**
   ```bash
   ngrok http 3000
   ```

2. **設定 NotifyURL**
   將 ngrok 提供的 HTTPS URL 設定為 NotifyURL

3. **發起測試付款**
   在統一金流測試環境發起付款

4. **檢查日誌**
   確認 Webhook 正確接收並處理

---

## 回調參數說明

### 成功付款通知參數

| 參數 | 說明 |
|------|------|
| Status | 付款狀態 `SUCCESS` / `FAIL` |
| MerchantOrderNo | 商店訂單編號 |
| TradeNo | PAYUNi 交易編號 |
| TradeAmt | 交易金額 |
| PaymentType | 付款方式 |
| PayTime | 付款時間 |
| CheckCode | 驗證碼 |

### CheckCode 計算規則

```
1. 將所有參數（除了 CheckCode）按字母順序排序
2. 組成 key=value&key=value 的字串
3. 在開頭加上 HashKey=xxx&，結尾加上 &HashIV=xxx
4. 進行 SHA256 雜湊後轉大寫
```

---

## 安全注意事項

1. **使用 HTTPS** - 正式環境必須使用 HTTPS
2. **驗證簽名** - 每次都要驗證 CheckCode
3. **防重放攻擊** - 記錄已處理的 TradeNo
4. **Constant-time 比較** - 使用 `timingSafeEqual` 防止 timing attack
5. **記錄日誌** - 記錄所有 Webhook 請求供除錯

---

## 詳細參考文件

- [程式碼範例](references/code-examples.md)
- [回應參數說明](references/response-parameters.md)
- [疑難排解](references/troubleshooting.md)
