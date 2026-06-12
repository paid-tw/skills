---
name: ecpay
description: >
  Provides ECPay (綠界科技) integration guide including payment setup and
  electronic invoice integration. Use when integrating ECPay payment gateway
  or e-invoice features.
user-invocable: true
---

# 綠界科技整合指南

## 快速開始

### 環境設定

**測試環境:**
- 收銀台 URL: `https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5`
- 測試特店編號: `3002607`（HashKey/HashIV 見 `/ecpay-checkout`）
- 廠商後台: `https://vendor-stage.ecpay.com.tw/`（`stagetest3` / `test1234`）

**正式環境:**
- 收銀台 URL: `https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5`
- 金鑰於廠商後台「系統設定 → 系統介接設定 → 介接資訊」取得

### 支援的支付方式

- 信用卡（一次付清、分期、3D 驗證）
- Apple Pay
- ATM 轉帳（非同步付款）
- 超商代碼/條碼（非同步付款）
- 電子發票

## 可用 Skills

- `/ecpay-checkout` - AIO 全方位金流串接（訂單建立、CheckMacValue 簽章、
  ReturnURL 通知處理、冪等入帳、測試環境 E2E）

## 重要注意事項

- **CheckMacValue 採 .NET 規格 URL encode**，各語言需做字元修正，
  詳見 `/ecpay-checkout` 的演算法文件
- **入帳以 ReturnURL 背景通知為準**，回應格式精確為 `1|OK`，通知會重送，
  入帳邏輯必須冪等
- **ReturnURL 僅限 80/443 port** 且必須對外公開
- `MerchantTradeDate` 必須為台北時區（雲端主機多為 UTC）
- 正式金鑰一律走環境變數，不可進版控

## 參考資源

- [ECPay 官方文件](https://developers.ecpay.com.tw/)

## 相關 Skills

- `/payment-help` - 查看所有可用的支付 skills
- `/newebpay` - 藍新金流串接
- `/payuni` - PAYUNi 統一金流串接
