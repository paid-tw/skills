---
name: ecpay-checkout
description: >
  Implements ECPay AIO (全方位金流) checkout integration including
  CheckMacValue signing, form submission, and ReturnURL payment
  notification handling. Use when integrating ECPay payment gateway,
  creating checkout flows, or building 綠界金流 payment pages.
argument-hint: "[支付方式: 信用卡/ATM/超商/Apple Pay]"
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

# 綠界金流 AIO 支付串接任務

你的任務是在用戶的專案中實作綠界全方位金流（AIO）幕前支付功能。

## 串接 Checklist

完成以下步驟即可完成串接：

- [ ] **環境確認** - 確認框架類型與支付方式需求
- [ ] **環境變數** - 設定 ECPAY_MERCHANT_ID、ECPAY_HASH_KEY、ECPAY_HASH_IV
- [ ] **支付模組** - 建立 CheckMacValue 簽章與訂單建立功能
- [ ] **支付表單** - 建立自動送出至綠界收銀台的 HTML 表單
- [ ] **回調處理** - 建立 ReturnURL 端點（驗簽 + 冪等 + 回 `1|OK`）
- [ ] **測試驗證** - 使用測試環境、測試卡號與後台「模擬付款」驗證

---

## Step 1: 確認專案環境

詢問用戶：

1. **框架類型**：你使用什麼框架？
   - PHP (Laravel / CodeIgniter / 原生)
   - Node.js (Express / Fastify / 原生 http)
   - Python (Django / Flask / FastAPI)
   - 其他

2. **支付方式**：需要支援哪些支付方式？（可複選）
   - 信用卡（一次付清 / 分期）
   - Apple Pay
   - ATM 轉帳（注意：非同步付款，訂單需有 pending 狀態）
   - 超商代碼 / 條碼（同上，非同步）
   - 全功能（`ChoosePayment=ALL`，由消費者在收銀台選擇）

用戶輸入: `$ARGUMENTS`

## Step 2: 檢查環境變數

搜尋專案中的 `.env` 或設定檔，確認是否已設定：
- `ECPAY_MERCHANT_ID`
- `ECPAY_HASH_KEY`
- `ECPAY_HASH_IV`

若未設定，可先以測試環境金鑰開發（見下方 API 參考），正式金鑰於綠界廠商後台
「系統設定 → 系統介接設定 → 介接資訊」取得。

## Step 3: 建立支付模組

根據用戶框架建立支付模組檔案。

**建立位置建議:**
- Laravel: `app/Services/EcpayService.php`
- Express/Node: `services/ecpay.js`
- Django: `payments/services.py`

**核心功能:**
1. `checkMacValue(params)` - 簽章產生（**最容易做錯的部分**，演算法見
   [references/checkmacvalue.md](references/checkmacvalue.md)）
2. `verifyNotify(body)` - 驗證綠界回傳通知的簽章
3. `createOrder(orderData)` - 建立訂單參數並回傳表單資料
4. `handleNotify(payload)` - 處理付款結果通知（冪等入帳）

## Step 4: 建立支付表單頁面

綠界 AIO 是表單 POST 跳轉模式（非 API redirect）：

```html
<form method="post" action="https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5">
    <input type="hidden" name="MerchantID" value="{特店編號}">
    <input type="hidden" name="MerchantTradeNo" value="{訂單編號}">
    <input type="hidden" name="MerchantTradeDate" value="{yyyy/MM/dd HH:mm:ss}">
    <input type="hidden" name="PaymentType" value="aio">
    <input type="hidden" name="TotalAmount" value="{金額}">
    <input type="hidden" name="TradeDesc" value="{交易描述}">
    <input type="hidden" name="ItemName" value="{商品名稱}">
    <input type="hidden" name="ReturnURL" value="{付款通知網址}">
    <input type="hidden" name="ChoosePayment" value="Credit">
    <input type="hidden" name="EncryptType" value="1">
    <input type="hidden" name="CheckMacValue" value="{簽章}">
    <button type="submit">前往綠界付款</button>
</form>
```

## Step 5: 建立回調處理

建立兩個端點：

1. **ReturnURL** (背景通知，server-to-server): `POST /payment/ecpay/notify`
   - 接收 `application/x-www-form-urlencoded` 通知
   - 驗證 CheckMacValue（不符直接拒絕）
   - 檢查 `SimulatePaid=1`：後台模擬付款，**回 `1|OK` 但不可入帳**
   - 檢查 `RtnCode=1` 為成功；比對金額與訂單一致
   - **冪等處理**：綠界會重送通知，同一筆訂單不可重複入帳
   - 成功處理後回應字串 `1|OK`（常見錯誤：`1OK`、`OK` 都不行）

2. **ClientBackURL / OrderResultURL** (前台返回): 使用者付完導回的頁面
   - 只做顯示用途，**訂單狀態一律以背景通知為準**
   - （使用者可能付完直接關瀏覽器，永遠不會回到這頁）

## Step 6: 測試驗證

引導用戶進行測試：
1. 使用測試環境特店（見下方 API 參考）建立訂單
2. 測試信用卡號: `4311-9511-1111-1111`（安全碼任意三碼、效期任意未來年月）
3. 3D 驗證簡訊碼固定為 `1234`
4. 登入測試後台 `https://vendor-stage.ecpay.com.tw/`（帳號 `stagetest3` / `test1234`）
   → 一般訂單查詢 → 點「模擬付款」驗證 ReturnURL 可收到通知
5. 驗證冪等：手動重送同一筆通知，確認不會重複入帳

---

## API 參考

### 端點

| 環境 | URL |
|------|-----|
| 測試 | `https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5` |
| 正式 | `https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5` |

### 測試環境特店（綠界官方公開測試金鑰）

| 項目 | 值 |
|------|-----|
| MerchantID | `3002607` |
| HashKey | `pwFHCqoQZGmho4w6` |
| HashIV | `EkRm7iFT261dpevs` |
| 測試後台 | `https://vendor-stage.ecpay.com.tw/`（`stagetest3` / `test1234`） |

### 訂單必要參數

| 參數 | 類型 | 說明 |
|------|------|------|
| MerchantID | String(10) | 特店編號 |
| MerchantTradeNo | String(20) | 訂單編號，**英數字、不可重複** |
| MerchantTradeDate | String(20) | `yyyy/MM/dd HH:mm:ss`，**必須為台北時區**（雲端主機多為 UTC，要轉換） |
| PaymentType | String | 固定 `aio` |
| TotalAmount | Int | 金額（新台幣整數） |
| TradeDesc | String(200) | 交易描述 |
| ItemName | String(400) | 商品名稱，多項以 `#` 分隔 |
| ReturnURL | String(200) | 背景通知網址，**必須對外公開且僅限 80/443 port** |
| ChoosePayment | String | `Credit`/`ATM`/`CVS`/`BARCODE`/`ALL` 等 |
| EncryptType | Int | 固定 `1`（SHA256） |
| CheckMacValue | String | 簽章（見演算法文件） |

### 通知重要參數（ReturnURL 收到）

| 參數 | 說明 |
|------|------|
| RtnCode | `1` = 付款成功；其他值見錯誤碼 |
| MerchantTradeNo | 你的訂單編號 |
| TradeNo | 綠界交易編號 |
| TradeAmt | 實際付款金額（**務必與訂單比對**） |
| SimulatePaid | `1` = 後台模擬付款，**勿入帳** |
| PaymentType | 實際使用的付款方式 |
| CheckMacValue | 簽章（**務必驗證**） |

---

## 重要注意事項（實戰驗證過的坑）

1. **CheckMacValue 的 URL encode 是 .NET 規格**，與 JS `encodeURIComponent` /
   PHP `urlencode` 都有差異，必須做字元替換 — 詳見
   [references/checkmacvalue.md](references/checkmacvalue.md)
2. **MerchantTradeDate 時區**：雲端主機（Cloud Run / Lambda / Heroku）預設 UTC，
   直接 format 會差 8 小時，可能導致訂單建立失敗或對帳混亂
3. **ReturnURL 限 80/443**，自訂 port 收不到通知；本機開發需用 ngrok/tunnel
4. **金鑰絕不入版控**：正式 HashKey/HashIV 一律走環境變數
5. **入帳以背景通知為準**，且必須冪等（綠界未收到 `1|OK` 會重試多次）
6. **ATM/超商是非同步付款**：消費者取號後可能隔數日才付款，訂單需設計
   `pending → paid` 狀態機與逾期處理

## 詳細參考文件

- [CheckMacValue 演算法與各語言實作](references/checkmacvalue.md)
- [程式碼範例 (Node.js / PHP)](references/code-examples.md)
- [通知處理與冪等設計](references/notify-handling.md)
- [測試環境與 E2E 自動化](references/testing.md)

## 相關 Skills

- `/payment-help` - 查看所有可用的支付 skills
- `/newebpay-checkout` - 藍新金流串接
