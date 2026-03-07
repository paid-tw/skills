# KryptoGO Payment Webhook 疑難排解

## 常見問題

### 1. 未收到 Webhook 回調

**可能原因**:
- `callback_url` 未在建立 Payment Intent 時設定
- URL 非公開可存取（如 `localhost`）
- 防火牆阻擋了 KryptoGO 的請求
- URL 使用 HTTP 而非 HTTPS

**解決方式**:
1. 確認建立 Payment Intent 時有傳入 `callback_url`
2. 確認 URL 為公開可存取的 HTTPS URL
3. 開發時可使用 ngrok 等工具暴露 localhost：
   ```bash
   ngrok http 3001
   # 使用 ngrok 提供的 HTTPS URL 作為 callback_url
   ```
4. 檢查伺服器防火牆設定

### 2. 收到回調但處理失敗

**可能原因**:
- 未正確解析 JSON body
- `payment_intent_id` 在資料庫中找不到
- 資料庫更新錯誤

**解決方式**:
1. 確認使用 JSON body parser：
   ```javascript
   // Express
   app.use(express.json());
   ```
2. 記錄完整 payload 供除錯：
   ```javascript
   router.post('/callback', (req, res) => {
     console.log('Callback received:', JSON.stringify(req.body));
     // ...處理邏輯
   });
   ```

### 3. 重複收到相同回調

**原因**: 若你的端點未回應 200，KryptoGO 會重試

**解決方式**:
1. 確保端點回應 HTTP 200
2. 實作冪等性處理：
   ```javascript
   // 檢查是否已處理
   if (['paid', 'expired', 'refunded'].includes(order.status)) {
     return res.status(200).send(); // 跳過但仍回應 200
   }
   ```

### 4. 端點回應 timeout

**原因**: 回調處理時間過長

**解決方式**:
1. 先回應 200，再非同步處理：
   ```javascript
   router.post('/callback', (req, res) => {
     // 立即回應
     res.status(200).send();
     // 非同步處理
     processPayment(req.body).catch(console.error);
   });
   ```

### 5. 開發環境無法測試

**解決方式**:

使用 ngrok 暴露本地端點：
```bash
# 安裝 ngrok
npm install -g ngrok

# 暴露本地 3001 port
ngrok http 3001
```

將 ngrok URL 作為 `callback_url` 使用：
```javascript
openPaymentModal({
  fiat_amount: '10',
  fiat_currency: 'TWD',
  callback_url: 'https://abc123.ngrok.io/api/payment/callback',
});
```

## 除錯清單

- [ ] `callback_url` 已在建立 Payment Intent 時設定
- [ ] URL 為 HTTPS 且公開可存取
- [ ] 伺服器有 JSON body parser
- [ ] 端點回應 HTTP 200
- [ ] 有冪等性處理
- [ ] 有記錄完整 callback payload
- [ ] 防火牆未阻擋外部 POST 請求
