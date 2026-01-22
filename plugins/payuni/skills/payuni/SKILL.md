---
name: payuni
description: >
  Provides PAYUNi (統一金流) integration guide including payment gateway setup
  and API configuration. Use when integrating PAYUNi payment services or
  unified payment solutions.
user-invocable: true
---

# PAYUNi 統一金流整合指南

> 🚧 開發中

## 快速開始

### 環境設定

**測試環境:**
- 測試 URL: `https://sandbox-api.payuni.com.tw/`
- 測試卡號:
  - VISA: `4147-6310-0000-0001`
  - JCB: `3560-5110-0000-0001`
  - 到期日: 任意未來日期
  - CVV: 任意三碼

**正式環境:**
- 正式 URL: `https://api.payuni.com.tw/`

### 支援的支付方式

- 信用卡
- ATM 轉帳
- 超商代碼
- 行動支付

## 參考資源

- [PAYUNi 官方文件](https://docs.payuni.com.tw/)
- 詳細 API 說明與錯誤碼對照將於後續版本提供

## 相關 Skills

- `/payment-help` - 查看所有可用的支付 skills
- `/newebpay` - 藍新金流串接
- `/ecpay` - 綠界科技串接
