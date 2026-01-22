---
name: newebpay
description: 藍新金流(NewebPay)串接指南。使用於 "藍新", "NewebPay", "藍新金流", "藍新信用卡" 相關問題。
license: MIT
metadata:
  author: paid-tw
  version: "1.0.0"
---

# 藍新金流整合指南

> 待從 newebpay-integration 遷移完整內容

## 快速開始

### 環境設定

**測試環境:**
- MPG交易: `https://ccore.newebpay.com/MPG/mpg_gateway`

**正式環境:**
- MPG交易: `https://core.newebpay.com/MPG/mpg_gateway`

### 支援的支付方式

- 信用卡（一次付清、分期、紅利）
- 行動支付（LINE Pay、Apple Pay、Google Pay）
- ATM 轉帳
- 超商代碼/條碼

## 參考資源

詳細資料請參閱：

- 完整 MPG 參數：[references/mpg-transaction.md](references/mpg-transaction.md)
- 回應參數說明：[references/response-parameters.md](references/response-parameters.md)
- 錯誤碼對照：[references/error-codes.md](references/error-codes.md)
- 使用情境範例：[references/use-cases.md](references/use-cases.md)

## 相關 Skills

- `/payment-help` - 查看所有可用的支付 skills
- `/ecpay` - 綠界科技串接
- `/payuni` - PAYUNi 統一金流串接
