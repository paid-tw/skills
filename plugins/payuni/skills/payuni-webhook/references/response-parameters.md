# PAYUNi Webhook 回應參數

## 付款成功通知參數

| 參數 | 類型 | 說明 |
|------|------|------|
| Status | String | 付款狀態 `SUCCESS` / `FAIL` / `PENDING` |
| Message | String | 狀態訊息 |
| MerchantOrderNo | String | 商店訂單編號 |
| TradeNo | String | PAYUNi 交易編號 |
| TradeAmt | String | 交易金額 |
| PaymentType | String | 付款方式代碼 |
| PayTime | String | 付款時間 (YYYY-MM-DD HH:mm:ss) |
| CheckCode | String | 驗證碼 (SHA256) |

## PaymentType 付款方式代碼

| 代碼 | 說明 |
|------|------|
| CREDIT | 信用卡 |
| VACC | ATM 轉帳 |
| CVS | 超商代碼 |
| BARCODE | 超商條碼 |
| LINEPAY | LINE Pay |
| APPLEPAY | Apple Pay |
| GOOGLEPAY | Google Pay |

## Status 狀態說明

| 值 | 說明 |
|:--:|------|
| SUCCESS | 付款成功 |
| FAIL | 付款失敗 |
| PENDING | 等待付款（ATM/超商） |

## ATM/超商特殊參數

當 PaymentType 為 VACC、CVS、BARCODE 時，會有額外參數：

| 參數 | 說明 |
|------|------|
| BankCode | 銀行代碼 (ATM) |
| CodeNo | 繳費代碼 (超商) |
| ExpireDate | 繳費期限 |

## 信用卡特殊參數

| 參數 | 說明 |
|------|------|
| Card4No | 卡號後四碼 |
| AuthBank | 發卡銀行代碼 |
| AuthCode | 授權碼 |
| Inst | 分期期數 (0 為一次付清) |

## CheckCode 驗證規則

1. 將所有回傳參數（除了 CheckCode）按字母順序排序
2. 組成 `key=value&key=value` 格式
3. 在開頭加上 `HashKey=xxx&`，結尾加上 `&HashIV=xxx`
4. 計算 SHA256 並轉大寫
5. 比對計算結果與收到的 CheckCode

```
原始: HashKey=xxx&key1=value1&key2=value2&HashIV=xxx
結果: SHA256(原始).toUpperCase()
```
