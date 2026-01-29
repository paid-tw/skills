# PAYUNi 疑難排解

## 常見問題

### 1. 加密/解密失敗

**症狀：** 送出後收到加密錯誤或無法解析回應

**解決方式：**
1. 確認 HashKey 和 HashIV 正確（從 PAYUNi 後台取得）
2. 確認 Key 補齊到 32 位元，IV 補齊到 16 位元
3. 確認使用 AES-256-CBC 模式
4. 確認輸出格式為 hex（而非 base64）

```typescript
// 正確的加密方式
const key = Buffer.from(hashKey.padEnd(32, '\0').slice(0, 32), 'utf8');
const iv = Buffer.from(hashIV.padEnd(16, '\0').slice(0, 16), 'utf8');
const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
```

---

### 2. CheckCode 驗證失敗

**症狀：** Webhook 收到但驗證失敗

**解決方式：**
1. 確認參數按**字母順序**排序
2. 確認**不包含** CheckCode 本身
3. 確認格式為 `HashKey=xxx&key1=value1&key2=value2&HashIV=xxx`
4. 確認 SHA256 結果轉為**大寫**

```typescript
// 正確的驗證順序
const sortedKeys = Object.keys(otherParams).sort();
const paramStr = sortedKeys.map(k => `${k}=${otherParams[k]}`).join('&');
const signStr = `HashKey=${hashKey}&${paramStr}&HashIV=${hashIV}`;
const hash = crypto.createHash('sha256').update(signStr).digest('hex').toUpperCase();
```

---

### 3. Webhook 收不到通知

**症狀：** 付款完成但沒收到 Webhook

**解決方式：**
1. 確認 NotifyURL 可從外網存取（不能是 localhost）
2. 確認使用 HTTPS（正式環境）
3. 確認伺服器回應 200 OK
4. 檢查防火牆是否阻擋
5. 使用 ngrok 進行本地測試

```bash
# 使用 ngrok 測試
ngrok http 3000
# 將產生的 https URL 設為 NotifyURL
```

---

### 4. 訂單編號重複

**症狀：** 建立訂單時收到重複錯誤

**解決方式：**
1. 訂單編號必須唯一，建議使用 UUID 或時間戳記組合
2. 重新發起付款時產生新的訂單編號

```typescript
// 建議的訂單編號格式
const orderId = `ORD-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
```

---

### 5. 測試環境可用，正式環境失敗

**症狀：** 測試環境正常，切換到正式環境後失敗

**解決方式：**
1. 確認已切換到正式環境 API 端點
2. 確認使用正式環境的 HashKey/HashIV
3. 確認商店帳號已開通正式環境
4. 確認 NotifyURL/ReturnURL 是正式的 HTTPS 網址

---

## 測試工具

### 驗證加密結果

```typescript
// 測試加密是否正確
const testData = 'MerID=TEST123&Amt=100';
const encrypted = encrypt(testData);
const decrypted = decrypt(encrypted);
console.log('原始:', testData);
console.log('解密:', decrypted);
console.log('相符:', testData === decrypted);
```

### 驗證 CheckCode

```typescript
// 測試 CheckCode 計算
const testParams = {
  Status: 'SUCCESS',
  MerchantOrderNo: 'ORD123',
  TradeNo: 'TRD456',
  TradeAmt: '100',
};

const checkCode = calculateCheckCode(testParams);
console.log('計算的 CheckCode:', checkCode);
```

---

## 聯繫支援

如果問題仍未解決：

1. 查閱 [PAYUNi 官方文件](https://docs.payuni.com.tw/)
2. 聯繫 PAYUNi 客服
3. 提供以下資訊：
   - 商店代號（MerchantID）
   - 錯誤訊息
   - 發生時間
   - 請求/回應內容（去除敏感資訊）
