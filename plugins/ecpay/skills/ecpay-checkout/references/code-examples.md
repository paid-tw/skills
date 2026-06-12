# 程式碼範例

完整可運行的最小實作。Node.js 版本取自實際串接並通過
測試環境實刷驗證的程式碼；PHP 版為對應移植。

## Node.js（原生 http，零依賴）

### ecpay.js — 簽章與訂單

```js
import { createHash } from 'node:crypto';

const config = {
  merchantId: process.env.ECPAY_MERCHANT_ID ?? '3002607',           // 預設測試特店
  hashKey: process.env.ECPAY_HASH_KEY ?? 'pwFHCqoQZGmho4w6',
  hashIV: process.env.ECPAY_HASH_IV ?? 'EkRm7iFT261dpevs',
  checkoutUrl: process.env.ECPAY_CHECKOUT_URL
    ?? 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5',
};

function dotNetUrlEncode(s) {
  return encodeURIComponent(s)
    .toLowerCase()
    .replace(/%20/g, '+').replace(/~/g, '%7e').replace(/'/g, '%27')
    .replace(/%21/g, '!').replace(/%28/g, '(').replace(/%29/g, ')')
    .replace(/%2a/g, '*');
}

export function checkMacValue(params) {
  const sorted = Object.keys(params)
    .filter((k) => k !== 'CheckMacValue')
    .sort((a, b) => (a.toLowerCase() < b.toLowerCase() ? -1 : 1));
  const raw = `HashKey=${config.hashKey}&`
    + sorted.map((k) => `${k}=${params[k]}`).join('&')
    + `&HashIV=${config.hashIV}`;
  return createHash('sha256').update(dotNetUrlEncode(raw)).digest('hex').toUpperCase();
}

export const verifyNotify = (body) =>
  !!body.CheckMacValue && checkMacValue(body) === body.CheckMacValue;

// 注意：MerchantTradeDate 必須是台北時間（主機常是 UTC）
function taipeiTradeDate() {
  const t = new Date(Date.now() + 8 * 3600 * 1000);
  const p = (n) => String(n).padStart(2, '0');
  return `${t.getUTCFullYear()}/${p(t.getUTCMonth() + 1)}/${p(t.getUTCDate())} `
    + `${p(t.getUTCHours())}:${p(t.getUTCMinutes())}:${p(t.getUTCSeconds())}`;
}

export function buildCheckoutParams({ tradeNo, amount, itemName, returnUrl, clientBackUrl }) {
  const params = {
    MerchantID: config.merchantId,
    MerchantTradeNo: tradeNo,            // 英數、<=20 字、不可重複
    MerchantTradeDate: taipeiTradeDate(),
    PaymentType: 'aio',
    TotalAmount: String(amount),
    TradeDesc: 'Order checkout',
    ItemName: itemName,
    ReturnURL: returnUrl,                // 對外公開、80/443
    ClientBackURL: clientBackUrl,
    ChoosePayment: 'Credit',
    EncryptType: '1',
  };
  return { ...params, CheckMacValue: checkMacValue(params) };
}

export function checkoutFormHtml(params) {
  const esc = (s) => String(s).replace(/[&<>"]/g,
    (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const fields = Object.entries(params)
    .map(([k, v]) => `<input type="hidden" name="${esc(k)}" value="${esc(v)}">`)
    .join('\n');
  return `<!doctype html><html><body onload="document.forms[0].submit()">
<form method="post" action="${esc(config.checkoutUrl)}">${fields}
<noscript><button type="submit">前往綠界付款</button></noscript>
</form></body></html>`;
}
```

### notify 端點（Express 風格）

```js
import express from 'express';
import { verifyNotify } from './ecpay.js';
import { creditOrder } from './orders.js'; // 冪等入帳，見 notify-handling.md

const app = express();
app.use(express.urlencoded({ extended: false }));

app.post('/payment/ecpay/notify', async (req, res) => {
  const body = req.body;
  if (!verifyNotify(body)) {
    return res.status(400).type('text/plain').send('0|CheckMacValue mismatch');
  }
  if (body.SimulatePaid === '1') {
    return res.type('text/plain').send('1|OK');         // 模擬付款：認可但不入帳
  }
  if (body.RtnCode !== '1') {
    await markFailed(body.MerchantTradeNo, body.RtnMsg);
    return res.type('text/plain').send('1|OK');          // 確認收到，停止重送
  }
  const result = await creditOrder(body.MerchantTradeNo, body);
  if (!result.ok) {
    return res.status(400).type('text/plain').send(`0|${result.reason}`);
  }
  res.type('text/plain').send('1|OK');                   // 精確回 1|OK
});
```

## PHP（原生）

```php
<?php
const ECPAY_MERCHANT_ID = '3002607';
const ECPAY_HASH_KEY    = 'pwFHCqoQZGmho4w6';
const ECPAY_HASH_IV     = 'EkRm7iFT261dpevs';
const ECPAY_CHECKOUT    = 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5';

function ecpayCheckMacValue(array $params): string
{
    unset($params['CheckMacValue']);
    uksort($params, fn($a, $b) => strcasecmp($a, $b));
    $raw = 'HashKey=' . ECPAY_HASH_KEY;
    foreach ($params as $k => $v) $raw .= "&{$k}={$v}";
    $raw .= '&HashIV=' . ECPAY_HASH_IV;
    $encoded = strtolower(urlencode($raw));
    $encoded = str_replace(['%21','%28','%29','%2a'], ['!','(',')','*'], $encoded);
    return strtoupper(hash('sha256', $encoded));
}

function ecpayBuildOrder(string $tradeNo, int $amount, string $itemName,
                         string $returnUrl): array
{
    date_default_timezone_set('Asia/Taipei'); // MerchantTradeDate 必須台北時間
    $params = [
        'MerchantID'        => ECPAY_MERCHANT_ID,
        'MerchantTradeNo'   => $tradeNo,
        'MerchantTradeDate' => date('Y/m/d H:i:s'),
        'PaymentType'       => 'aio',
        'TotalAmount'       => (string)$amount,
        'TradeDesc'         => 'Order checkout',
        'ItemName'          => $itemName,
        'ReturnURL'         => $returnUrl,
        'ChoosePayment'     => 'Credit',
        'EncryptType'       => '1',
    ];
    $params['CheckMacValue'] = ecpayCheckMacValue($params);
    return $params;
}

// notify.php — ReturnURL 端點
$body = $_POST;
$mac  = $body['CheckMacValue'] ?? '';
if (ecpayCheckMacValue($body) !== $mac) {
    http_response_code(400);
    exit('0|CheckMacValue mismatch');
}
if (($body['SimulatePaid'] ?? '0') === '1') exit('1|OK'); // 模擬付款不入帳
if (($body['RtnCode'] ?? '') !== '1')       exit('1|OK'); // 失敗通知，確認收到
// ... 冪等入帳（DB transaction，pending→paid 原子轉移）...
exit('1|OK');
```

## 驗證腳本範例（自測冪等與防竄改）

```js
// 對自己的 notify 端點做攻防測試：
// 1. 合法通知 → 1|OK 且入帳
// 2. 重送同一筆 → 1|OK 但不重複入帳
// 3. 竄改 CheckMacValue → 400
// 4. 合法簽章但金額不符 → 400
// 5. SimulatePaid=1 → 1|OK 且不入帳
const notify = sign({ MerchantTradeNo: tradeNo, RtnCode: '1', TradeAmt: '1000', ... });
await post('/payment/ecpay/notify', notify);            // → 入帳
await post('/payment/ecpay/notify', notify);            // → 冪等
await post('/payment/ecpay/notify', { ...notify, CheckMacValue: 'F'.repeat(64) }); // → 拒絕
```
