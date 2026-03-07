---
name: kryptogo-pay-transfer
description: >
  Implements KryptoGO Payment token transfer/withdrawal functionality using the
  Asset Pro Transfer API. Use when building withdrawal features, sending tokens
  to users, or implementing 穩定幣提領轉帳 for KryptoGO Payment.
argument-hint: "[操作: 提領/轉帳] [代幣: USDT/USDC]"
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

# KryptoGO Payment 代幣轉帳/提領任務

你的任務是在用戶的專案中實作 KryptoGO Payment 穩定幣轉帳與提領功能。

## 串接 Checklist

- [ ] **環境確認** - 確認 API Key 已設定
- [ ] **轉帳模組** - 建立代幣轉帳功能
- [ ] **API 端點** - 建立提領/轉帳端點
- [ ] **安全驗證** - 加入提領前的身份驗證
- [ ] **測試驗證** - 驗證轉帳功能正常

---

## Step 1: 確認環境

詢問用戶：

1. **使用情境**：需要什麼轉帳功能？
   - 用戶提領（用戶將餘額提領到自己的錢包）
   - 獎勵發放（發送代幣到用戶錢包）
   - 批次轉帳（批量發送代幣）

2. **代幣與鏈**：要轉帳什麼代幣？
   - USDT on Arbitrum
   - USDC on Arbitrum

用戶輸入: `$ARGUMENTS`

## Step 2: 確認環境變數

Transfer API 只需要 `X-STUDIO-API-KEY`：
- `KRYPTOGO_STUDIO_API_KEY`

## Step 3: 建立轉帳功能

**核心功能:**
1. `transferTokens(chainId, contractAddress, amount, walletAddress)` - 轉帳代幣

## Step 4: 建立 API 端點

建議加入提領前的驗證邏輯：
1. 驗證用戶身份
2. 驗證提領地址格式
3. 確認金額合理
4. 執行轉帳

## Step 5: 測試

1. 確認 Studio 帳戶有足夠餘額
2. 使用小額（如 0.01）測試轉帳
3. 確認回傳 `tx_hash` 可在區塊鏈上查到

---

## API 參考

### 端點

| 項目 | 說明 |
|------|------|
| 方法 | `POST` |
| URL | `https://wallet.kryptogo.app/v1/studio/api/asset_pro/transfer` |

### Required Headers

| Header | 說明 |
|--------|------|
| `X-STUDIO-API-KEY` | Studio API Key |
| `Content-Type` | `application/json` |

### 請求參數

| 參數 | 類型 | 必填 | 說明 |
|------|------|:----:|------|
| chain_id | String | ✓ | 區塊鏈 ID（如 `arb`）|
| contract_address | String | ✓ | 代幣合約地址 |
| amount | String | ✓ | 轉帳金額 |
| wallet_address | String | ✓ | 目標錢包地址 |

### Arbitrum 代幣合約地址

| 代幣 | 合約地址 |
|------|---------|
| USDT | `0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9` |
| USDC | `0xff970a61a04b1ca14834a43f5de4533ebddb5cc8` |

### 成功回應

```json
{
  "code": 0,
  "data": {
    "id": "1-1627380000-1",
    "tx_hash": "0x1234567890abcdef",
    "transfer_time": 1627380000
  }
}
```

### 錯誤回應（餘額不足）

```json
{
  "code": 4015,
  "message": "Balance not enough",
  "data": {
    "insufficient_amount": "123.456"
  }
}
```

---

## 詳細參考文件

- [程式碼範例 (Node.js/Python)](references/code-examples.md)

---

## 重要注意事項

1. 轉帳為不可逆操作，請務必確認地址正確
2. 目前無提領限額
3. 確認 Studio 帳戶有足夠餘額
4. API Key 不可暴露在前端程式碼中
