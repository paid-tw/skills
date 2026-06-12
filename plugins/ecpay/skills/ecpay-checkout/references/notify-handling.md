# ReturnURL 通知處理與冪等設計

## 鐵則

1. **入帳只認背景通知**（ReturnURL），不認前台跳轉。消費者付完可能直接
   關瀏覽器，前台頁面永遠不會載入，但綠界的 server-to-server 通知一定會來。
2. **回應格式精確為 `1|OK`**。常見錯誤：`1OK`、`OK`、`1|ok`、帶 BOM 或
   多餘換行 — 綠界判定失敗就會持續重送。
3. **通知會重送** — 沒收到 `1|OK` 時綠界會重試多次。你的入帳邏輯必須冪等。

## 處理流程

```
收到 POST (application/x-www-form-urlencoded)
  │
  ├─ 驗證 CheckMacValue ──不符──→ 拒絕（記 log，這可能是偽造攻擊）
  │
  ├─ SimulatePaid == "1"? ──是──→ 回 1|OK，「不要」入帳
  │      （後台「模擬付款」功能，用來驗證你的端點通不通，沒有真的錢）
  │
  ├─ RtnCode != "1"? ──是──→ 標記訂單失敗，回 1|OK（確認收到，停止重送）
  │
  ├─ 比對 TradeAmt 與訂單金額 ──不符──→ 拒絕（金額竄改防線）
  │
  └─ 冪等入帳：
       以「訂單狀態 pending→paid 的原子轉移」為入帳前提
       （DB transaction / Firestore transaction / SELECT FOR UPDATE）
       已是 paid → 直接回 1|OK，不重複入帳
```

## 冪等實作範例（概念，任何 DB 通用）

```js
async function creditOrder(tradeNo, notify) {
  return db.transaction(async (t) => {
    const order = await t.get(`orders/${tradeNo}`);
    if (!order) return { ok: false, reason: 'unknown-order' };
    if (order.status === 'paid') return { ok: true, alreadyPaid: true }; // 冪等出口
    if (Number(notify.TradeAmt) !== order.amount) {
      return { ok: false, reason: 'amount-mismatch' };
    }
    t.update(`orders/${tradeNo}`, { status: 'paid', ecpayTradeNo: notify.TradeNo });
    t.create('ledger', { tradeNo, amount: order.amount, at: now() });
    return { ok: true };
  });
}
```

## 收不到通知的排查清單（綠界官方 + 實戰）

- ReturnURL 是否對外公開？（本機開發要用 ngrok / cloudflared tunnel）
- 是否被防火牆 / WAF 擋掉？
- **Port 限制：綠界只打 80 / 443**，自訂 port 永遠收不到
- 回了 `1|OK` 以外的內容？（含多餘空白、HTML error page）
- HTTPS 憑證是否有效？（自簽憑證會失敗）

## 非同步付款（ATM / 超商）

信用卡通知幾乎即時，但 ATM / 超商代碼是「先取號、後付款」：

- 取號完成時會先收到一次取號結果通知（`RtnCode` 為取號成功碼，非 1）
- 消費者實際繳費後（可能數天後）才收到 `RtnCode=1` 的付款通知
- 訂單需設計 `pending → paid` 狀態機與取號逾期（`ExpireDate`）處理
