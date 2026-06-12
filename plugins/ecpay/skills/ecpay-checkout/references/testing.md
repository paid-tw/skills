# 測試環境與 E2E 自動化

## 測試資源總表

| 項目 | 值 |
|------|-----|
| 收銀台 | `https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5` |
| 特店編號 | `3002607` |
| HashKey / HashIV | `pwFHCqoQZGmho4w6` / `EkRm7iFT261dpevs` |
| 廠商後台 | `https://vendor-stage.ecpay.com.tw/`（`stagetest3` / `test1234`） |
| 一般測試卡 | `4311-9511-1111-1111`（CVV 任意、效期任意未來年月） |
| 海外測試卡 | `4000-2011-1111-1111` |
| 3D 驗證碼 | 固定 `1234`（不需手機收簡訊） |

以上為綠界官方公開的測試環境資料，僅能用於 stage 環境。

## 三層測試策略

### 1. 簽章自測（不出門）
產生訂單參數後自己驗一次 MAC；把偽造的通知 POST 到自己的
ReturnURL，驗證：驗簽通過、冪等（重送不重複入帳）、金額竄改被拒、
`SimulatePaid=1` 不入帳。

### 2. 後台模擬付款（驗證通知通路）
廠商後台 → 一般訂單查詢 → 全方位金流訂單 → 「模擬付款」按鈕。
綠界會對你的 ReturnURL 發送 `SimulatePaid=1` 的真實通知 —
這是驗證「綠界打得到你的 server」最快的方式，不需要走完付款流程。

### 3. 測試卡實刷（全流程）
用測試卡走完收銀台 + 3D 驗證，驗證真實的付款成功通知與入帳。

## 收銀台 E2E 自動化（Playwright）— 實測踩坑紀錄

用 Playwright 自動化測試環境刷卡時，收銀台有幾個非顯而易見的行為：

1. **卡號等欄位必須用鍵盤輸入**（`pressSequentially`），用 `fill()`
   直接設值不會觸發它的驗證，送出時會報「請輸入信用卡卡號」
2. **按「立即付款」會跳出測試環境警告彈窗**（「您目前正在使用的是
   綠界科技的付款測試環境…」），要按「關閉」後**再按一次**立即付款
3. 之後還有一個**付款確認對話框**（「您確定使用信用卡，支付…」），按「確定」
4. 跳轉到 `cc-stage.ecpay.com.tw` 的 3D 頁後，要先按
   「**取得OTP服務密碼**」按鈕，輸入框才會出現，再填 `1234` 送出
5. 付款成功頁 URL 為 `…/PaymentCenter/…/credit/result`，顯示「付款成功」

```js
// 流程骨架
await page.goto(checkoutFormUrl);                  // 自動 POST 到收銀台
await typeCard(page);                              // pressSequentially 填卡
await page.click('#CreditPaySubmit');              // 立即付款（A 標籤，id 固定）
await dismiss(page, '關閉');                       // 測試環境警告
await page.click('#CreditPaySubmit');              // 再按一次
await dismiss(page, '確定');                       // 付款確認框
await page.waitForURL(/cc-stage/);                 // 3D 驗證頁
await page.click('text=取得OTP服務密碼');
await page.locator('input').first().pressSequentially('1234');
await submit(page);                                // → 付款成功頁 + 背景通知
```

## 切換正式環境檢查清單

- [ ] 端點換成 `https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5`
- [ ] MerchantID / HashKey / HashIV 換成正式後台「系統介接設定」的值
- [ ] 金鑰走環境變數，確認測試金鑰沒有殘留在程式碼或版控
- [ ] 小額實刷一筆 + 退款一筆驗證
- [ ] 確認對帳：綠界後台訂單 vs 自家 ledger
