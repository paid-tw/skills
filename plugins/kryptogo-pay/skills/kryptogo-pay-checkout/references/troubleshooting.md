# KryptoGO Payment 疑難排解

## 常見問題

### 1. Client ID 無效

**症狀**: API 回傳 `Invalid client_id` 錯誤

**原因**: `X-Client-ID` header 值不正確

**解決方式**:
1. 前往 [KryptoGO Studio](https://studio.kryptogo.com) 確認 Client ID
2. 確認環境變數 `KRYPTOGO_CLIENT_ID` 正確設定
3. 確認 header 名稱為 `X-Client-ID`（注意大小寫）

### 2. API Key 認證失敗

**症狀**: API 回傳 401 或 `Invalid API key`

**原因**: `X-STUDIO-API-KEY` header 值不正確或已過期

**解決方式**:
1. API Key 只在建立時顯示一次，若遺失需重新建立
2. 確認環境變數 `KRYPTOGO_STUDIO_API_KEY` 正確
3. 確認 header 名稱為 `X-STUDIO-API-KEY`

### 3. Origin 不匹配

**症狀**: API 回傳 `Invalid origin`

**原因**: `Origin` header 與 Studio 中設定的網域不一致

**解決方式**:
1. 確認 `Origin` header 包含完整網域（如 `https://your-domain.com`）
2. 確認與 KryptoGO Studio 中的設定一致

### 4. fiat_amount 被拒絕

**症狀**: API 回傳 `Invalid fiat_amount`

**原因**: 金額格式不正確或低於最低限額

**解決方式**:
1. 確認 `fiat_amount` 為字串格式（如 `"100.0"` 而非 `100`）
2. 確認金額 >= `0.01`
3. 確認金額為有效數字

### 5. Webhook 回調未收到

**症狀**: 建立 Payment Intent 時有設定 `callback_url`，但未收到回調

**解決方式**:
1. 確認 `callback_url` 為公開可存取的 HTTPS URL
2. 確認端點正確回應 HTTP 200
3. 檢查防火牆或安全群組是否阻擋 KryptoGO 的請求
4. 在 KryptoGO Studio 後台確認支付狀態

### 6. Payment Intent 過期

**症狀**: 支付尚未完成但已過期

**原因**: Payment Intent 有 30 分鐘有效期

**解決方式**:
1. 建立新的 Payment Intent 重新支付
2. 若用戶已轉帳但過期，系統會自動退款
3. 使用 `/kryptogo-pay-query` 查詢支付狀態確認

### 7. React SDK usePayment Hook 無法使用

**症狀**: `usePayment` 回傳 undefined 或報錯

**解決方式**:
1. 確認已安裝所有依賴：
   ```bash
   npm install @kryptogo/kryptogokit-sdk-react wagmi viem@2.x @tanstack/react-query
   ```
2. 確認已匯入 CSS：`import '@kryptogo/kryptogokit-sdk-react/styles.css'`
3. 確認元件被 `KryptogoKitProvider`、`QueryClientProvider`、`WagmiProvider` 包裹
4. 確認使用 wagmi 2.0+

### 8. 金額不足退款

**症狀**: 用戶轉了比 `crypto_amount` 少的金額

**處理方式**:
1. 狀態會變為 `insufficient_not_refunded`
2. 系統會自動處理退款至用戶的 Studio 帳戶
3. 退款完成後狀態變為 `insufficient_refunded`
4. 退款資訊在 `refund_tx_hash` 和 `refund_amount` 欄位

### 9. 轉帳餘額不足

**症狀**: 呼叫 transfer API 時回傳 `Balance not enough`

**解決方式**:
1. 確認 Studio 帳戶有足夠餘額
2. 回應中的 `insufficient_amount` 欄位會顯示不足金額
3. 在 KryptoGO Studio 確認可用餘額

## 環境設定確認清單

- [ ] `KRYPTOGO_CLIENT_ID` 已設定且正確
- [ ] `KRYPTOGO_STUDIO_API_KEY` 已設定且正確
- [ ] `KRYPTOGO_ORIGIN` 已設定且與 Studio 一致
- [ ] `.env` 已加入 `.gitignore`
- [ ] API 呼叫從後端發起（非前端）
- [ ] Webhook 端點為 HTTPS 且可公開存取
- [ ] Webhook 端點回應 HTTP 200

## 支援管道

- KryptoGO Studio: https://studio.kryptogo.com
- API 文件: https://www.kryptogo.com/docs/api/payment
- Email: support@kryptogo.com
