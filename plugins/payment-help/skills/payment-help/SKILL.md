---
name: payment-help
description: >
  Lists all available Taiwan payment integration skills and provides recommendations
  based on user requirements. Use when comparing payment gateways or choosing a payment solution.
  Triggers: "payment help", "金流推薦", "支付比較", "哪個金流", "which payment"
user-invocable: true
license: MIT
metadata:
  author: paid-tw
  version: "1.0.0"
---

# 台灣金流服務比較與推薦

本 skill 提供台灣主要第三方支付金流服務的比較與推薦。

## 用戶需求分析

用戶輸入: `$ARGUMENTS`

根據用戶需求，提供最適合的金流推薦。

## 可用的 Skills

### NewebPay (藍新金流)

| Skill | 功能 | 使用方式 |
|-------|------|---------|
| `/newebpay` | 總覽與環境設定 | `/newebpay` |
| `/newebpay-checkout` | MPG 幕前支付串接 | `/newebpay-checkout [支付方式]` |
| `/newebpay-query` | 交易查詢 | `/newebpay-query` |
| `/newebpay-refund` | 退款作業 | `/newebpay-refund [類型]` |

**支援**: 信用卡, LINE Pay, Apple Pay, Google Pay, ATM, 超商代碼/條碼

### ECPay (綠界科技)

| Skill | 功能 | 使用方式 |
|-------|------|---------|
| `/ecpay` | ECPay 串接指南 | `/ecpay` |

**支援**: 信用卡, ATM, 超商, 電子發票

### PAYUNi (統一金流)

| Skill | 功能 | 使用方式 |
|-------|------|---------|
| `/payuni` | PAYUNi 串接指南 | `/payuni` |

**支援**: 信用卡, ATM, 超商, 行動支付

## 金流比較表

| 功能特色 | NewebPay | ECPay | PAYUNi |
|---------|----------|-------|--------|
| 市場定位 | 中大型電商 | 台灣最大 | 統一集團 |
| 信用卡 | ✅ | ✅ | ✅ |
| LINE Pay | ✅ | ✅ | ✅ |
| Apple Pay | ✅ | ✅ | ✅ |
| 電子發票 | ❌ | ✅ | ❌ |
| 超商取貨付款 | ✅ | ✅ | ✅ |
| API 文件品質 | 優良 | 普通 | 普通 |
| 整合難度 | 中等 | 中等 | 中等 |

## 推薦指南

根據不同需求選擇：

### 需要電子發票
**推薦: ECPay**
- 內建電子發票功能
- 可與金流一併整合

### 高流量 / 穩定性優先
**推薦: NewebPay**
- 系統穩定性高
- 適合中大型電商

### 統一集團相關
**推薦: PAYUNi**
- 統一企業體系
- 與 7-11 系統整合佳

### 快速上線
**任一皆可**
- 三家整合難度相近
- 選擇自己熟悉的文件風格

## 使用範例

```bash
# 查看金流比較
/payment-help

# 開始 NewebPay 串接
/newebpay

# 串接信用卡支付
/newebpay-checkout 信用卡

# 查詢交易狀態
/newebpay-query

# 處理退款
/newebpay-refund
```

## 相關資源

- **Agent**: `payment-integrator` - 台灣金流串接專家
