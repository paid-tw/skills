---
name: payuni-checkout
description: >
  Implements PAYUNi UPP checkout integration including AES256 encryption,
  form submission, and payment callback handling. Use when integrating payment
  gateway, creating checkout flows, or building 統一金流 payment pages.
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

# 統一金流 UPP 支付串接任務

你的任務是在用戶的專案中實作統一金流 UPP 幕前支付功能。

## 串接 Checklist

完成以下步驟即可完成串接：

- [ ] **環境確認** - 確認框架類型與支付方式需求
- [ ] **環境變數** - 設定 PAYUNI_MERCHANT_ID、HASH_KEY、HASH_IV
- [ ] **支付模組** - 建立加密解密與訂單建立功能
- [ ] **支付表單** - 建立送出至統一金流的 HTML 表單
- [ ] **回調處理** - 建立 NotifyURL 與 ReturnURL 端點
- [ ] **測試驗證** - 使用測試環境驗證

---

## Step 1: 確認專案環境

詢問用戶：

1. **框架類型**：你使用什麼框架？
   - PHP (Laravel / CodeIgniter / 原生)
   - Node.js (Express / Next.js / NestJS)
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
- `PAYUNI_MERCHANT_ID`
- `PAYUNI_HASH_KEY`
- `PAYUNI_HASH_IV`

若未設定，引導用戶設定環境變數。

## Step 3: 建立支付模組

根據用戶框架建立支付模組檔案。

**建立位置建議:**
- Laravel: `app/Services/PayuniService.php`
- Express: `services/payuni.js` 或 `services/payuni.ts`
- Next.js: `lib/payuni.ts`
- Django: `payments/services.py`

**核心功能:**
1. `encrypt(data)` - AES-256-CBC 加密
2. `decrypt(data)` - AES-256-CBC 解密
3. `generateHashInfo(encryptInfo)` - SHA256 雜湊
4. `createOrder(orderData)` - 建立訂單並回傳表單資料
5. `verifyCallback(payload)` - 驗證回調通知

### Node.js/TypeScript 範例

```typescript
import crypto from 'crypto';

const config = {
  merchantId: process.env.PAYUNI_MERCHANT_ID!,
  hashKey: process.env.PAYUNI_HASH_KEY!,
  hashIV: process.env.PAYUNI_HASH_IV!,
  isTest: process.env.PAYUNI_TEST_MODE === 'true',
};

// AES-256-CBC 加密
function encrypt(data: string): string {
  const key = Buffer.from(config.hashKey.padEnd(32, '\0').slice(0, 32), 'utf8');
  const iv = Buffer.from(config.hashIV.padEnd(16, '\0').slice(0, 16), 'utf8');
  
  const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
  let encrypted = cipher.update(data, 'utf8', 'hex');
  encrypted += cipher.final('hex');
  return encrypted;
}

// SHA256 雜湊
function generateHashInfo(encryptInfo: string): string {
  return crypto
    .createHash('sha256')
    .update(encryptInfo)
    .digest('hex')
    .toUpperCase();
}

// 建立訂單
function createOrder(params: {
  orderId: string;
  amount: number;
  productName: string;
  returnUrl: string;
  notifyUrl: string;
}) {
  const tradeInfo = {
    MerID: config.merchantId,
    MerTradeNo: params.orderId,
    TradeAmt: params.amount,
    ProdDesc: params.productName,
    ReturnURL: params.returnUrl,
    NotifyURL: params.notifyUrl,
  };
  
  const queryString = new URLSearchParams(tradeInfo as any).toString();
  const encryptInfo = encrypt(queryString);
  const hashInfo = generateHashInfo(encryptInfo);
  
  return {
    MerID: config.merchantId,
    EncryptInfo: encryptInfo,
    HashInfo: hashInfo,
  };
}
```

## Step 4: 建立支付表單頁面

根據框架建立支付表單，需包含：

```html
<form method="post" action="https://sandbox-api.payuni.com.tw/api/upp">
    <input type="hidden" name="MerID" value="{商店代號}">
    <input type="hidden" name="EncryptInfo" value="{加密資料}">
    <input type="hidden" name="HashInfo" value="{SHA256雜湊}">
    <button type="submit">前往付款</button>
</form>
```

**注意：** 正式環境請改為 `https://api.payuni.com.tw/api/upp`

## Step 5: 建立回調處理

建立兩個端點：

1. **NotifyURL** (背景通知): `POST /api/webhooks/payuni`
   - 接收統一金流背景通知
   - 驗證簽名 (CheckCode)
   - 更新訂單狀態
   - 回應 `{ success: true }`

2. **ReturnURL** (前台返回): `GET /checkout/result`
   - 用戶支付完成後導向
   - 顯示交易結果

### 簽名驗證邏輯

```typescript
function verifyCheckCode(params: Record<string, string>): boolean {
  const { CheckCode, ...otherParams } = params;
  
  const sortedKeys = Object.keys(otherParams).sort();
  const paramStr = sortedKeys.map(k => `${k}=${otherParams[k]}`).join('&');
  const signStr = `HashKey=${config.hashKey}&${paramStr}&HashIV=${config.hashIV}`;
  
  const calculated = crypto
    .createHash('sha256')
    .update(signStr)
    .digest('hex')
    .toUpperCase();
    
  return calculated === CheckCode;
}
```

## Step 6: 測試驗證

引導用戶進行測試：
1. 使用測試環境 `https://sandbox-api.payuni.com.tw`
2. 驗證加密解密正確性
3. 確認回調可正常接收
4. 測試不同支付方式

---

## API 參考

### 端點

| 環境 | URL |
|------|-----|
| 測試 | `https://sandbox-api.payuni.com.tw/api/upp` |
| 正式 | `https://api.payuni.com.tw/api/upp` |

### TradeInfo 必要參數

| 參數 | 類型 | 說明 |
|------|------|------|
| MerID | String | 商店代號 |
| MerTradeNo | String(30) | 訂單編號（不可重複）|
| TradeAmt | Number | 金額 |
| ProdDesc | String | 商品描述 |
| ReturnURL | String | 前台返回網址 |
| NotifyURL | String | 背景通知網址 |

### 支付方式參數

| 參數 | 值 | 說明 |
|------|:---:|------|
| CREDIT | 1 | 信用卡 |
| LINEPAY | 1 | LINE Pay |
| APPLEPAY | 1 | Apple Pay |
| GOOGLEPAY | 1 | Google Pay |
| VACC | 1 | ATM 轉帳 |
| CVS | 1 | 超商代碼 |
| BARCODE | 1 | 超商條碼 |

---

## 詳細參考文件

- [程式碼範例 (PHP/Node.js)](references/code-examples.md)
- [完整交易參數](references/transaction-parameters.md)
- [回應參數說明](references/response-parameters.md)
- [錯誤代碼](references/error-codes.md)
- [疑難排解](references/troubleshooting.md)
