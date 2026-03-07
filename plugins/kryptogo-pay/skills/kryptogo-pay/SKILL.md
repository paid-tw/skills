---
name: kryptogo-pay
description: >
  Provides KryptoGO Payment integration overview and guides users to the appropriate skill.
  Use when starting KryptoGO crypto payment integration, setting up environment, or needing
  general guidance about KryptoGO 穩定幣支付.
argument-hint: "[功能: checkout/query/webhook/transfer]"
user-invocable: true
---

# KryptoGO Payment 穩定幣支付整合指南

你的任務是幫助用戶設定 KryptoGO Payment 環境並引導至適當的串接功能。

## 用戶需求分析

用戶輸入: `$ARGUMENTS`

根據用戶需求，判斷下一步：
- 若包含「收款」「checkout」「建立支付」「payment intent」→ 引導使用 `/kryptogo-pay-checkout`
- 若包含「查詢」「query」「訂單狀態」→ 引導使用 `/kryptogo-pay-query`
- 若包含「webhook」「回調」「callback」「通知」→ 引導使用 `/kryptogo-pay-webhook`
- 若包含「提領」「轉帳」「transfer」「withdrawal」→ 引導使用 `/kryptogo-pay-transfer`
- 若無特定指定 → 提供以下環境設定引導

## 環境設定檢查

詢問用戶以下問題：

1. **整合方式**：你要如何串接 KryptoGO Payment？
   - React SDK（前端 `@kryptogo/kryptogokit-sdk-react`）
   - Direct API（後端直接呼叫 REST API）
   - 兩者都要（前端 SDK + 後端 API）

2. **環境狀態**：是否已有 KryptoGO Studio 帳號？
   - 是，已有 Client ID 和 API Key
   - 否，需要申請

## 環境變數設定

引導用戶建立環境變數：

```bash
# 後端 API 整合用
KRYPTOGO_CLIENT_ID=your_client_id
KRYPTOGO_STUDIO_API_KEY=your_studio_api_key
KRYPTOGO_ORIGIN=your_domain
```

**指導用戶:**
1. 前往 [KryptoGO Studio](https://studio.kryptogo.com) 註冊帳號
2. 在 Account Settings 建立 API Key（僅顯示一次，請妥善保存）
3. 在專案根目錄建立或編輯 `.env` 檔案
4. 加入上述環境變數
5. 確保 `.env` 已加入 `.gitignore`

## 快速啟動（Express 範例伺服器）

若用戶希望快速開始：

```bash
npx create-kg-express-test my-app
cd my-app
cp .env.example .env
# 編輯 .env 填入 STUDIO_API_KEY、CLIENT_ID、ORIGIN
npm install && npm start
```

## 下一步

完成環境設定後，根據用戶需求引導：

| 需求 | Skill | 說明 |
|------|-------|------|
| 建立支付收款 | `/kryptogo-pay-checkout` | 建立 Payment Intent 收取穩定幣 |
| 查詢交易狀態 | `/kryptogo-pay-query` | 查詢單筆或列表 Payment Intent |
| 處理 Webhook 回調 | `/kryptogo-pay-webhook` | 接收支付狀態變更通知 |
| 提領/轉帳 | `/kryptogo-pay-transfer` | 將穩定幣轉至外部錢包 |

## API 資訊

| 項目 | 說明 |
|------|------|
| Base URL | `https://wallet.kryptogo.app` |
| API 文件 | `https://www.kryptogo.com/docs/api/payment` |
| Studio | `https://studio.kryptogo.com` |
| 支援代幣 | USDT、USDC（Arbitrum） |
| 支援法幣 | TWD、USD |
| 手續費 | 1% 固定費率 |
| 狀態更新 | 8 秒內（ETH 除外）|
| 最低金額 | fiat_amount >= 0.01 |

## 支援的支付方式

- **KryptoGO Wallet**（推薦）：掃描 QR Code 支付
- **MetaMask / WalletConnect**：瀏覽器錢包或 WalletConnect 連線
- **QR Code 轉帳**：從交易所或其他錢包手動轉帳

## 兩種整合路徑

### 1. React SDK 整合（前端）

適合：需要前端支付 UI 的場景

```bash
npm install @kryptogo/kryptogokit-sdk-react wagmi viem@2.x @tanstack/react-query
```

### 2. Direct API 整合（後端）

適合：純後端服務、自訂前端、跨平台整合

Required Headers:
- `X-Client-ID`: 你的 Client ID
- `X-STUDIO-API-KEY`: 你的 Studio API Key
- `Origin`: 你的網域

## 重要注意事項

1. API Key 必須保密，不可暴露在前端程式碼中
2. 所有 API 呼叫應從後端發起（保護憑證）
3. 目前支援 Arbitrum 鏈上的 USDT/USDC
4. Payment Intent 有效時間為 30 分鐘
5. 回調端點需回應 HTTP 200 確認收到
