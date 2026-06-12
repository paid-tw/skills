# 台灣支付金流 Skills

[![Validate Plugins](https://github.com/paid-tw/skills/actions/workflows/validate.yml/badge.svg)](https://github.com/paid-tw/skills/actions/workflows/validate.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

台灣第三方金流 Skills for AI Agents - 藍新金流、綠界科技 ECPay、統一金流 PAYUNi、KryptoGO 穩定幣支付

支援 Claude Code、Cursor、Codex、GitHub Copilot 等 AI coding agents。

## 支援的支付服務

| 服務 | Plugin | 狀態 | 說明 |
|------|--------|------|------|
| 藍新金流 | `newebpay` | ✅ 可用 | 信用卡、LINE Pay、Apple Pay、ATM、超商 |
| KryptoGO Pay | `kryptogo-pay` | ✅ 可用 | 穩定幣支付（USDT/USDC on Arbitrum）|
| 綠界科技 | `ecpay` | ✅ 可用 | 全方位金流（信用卡、ATM、超商）|
| PAYUNi | `payuni` | 🚧 開發中 | 統一集團金流服務 |

## 安裝

### 方式一：npx skills（推薦）

使用 [skills CLI](https://skills.sh) 選擇性安裝需要的 skills：

```bash
# 查看可用的 skills
npx skills add paid-tw/skills --list

# 選擇性安裝
npx skills add paid-tw/skills --skill newebpay      # 只裝藍新金流
npx skills add paid-tw/skills --skill kryptogo-pay  # 只裝 KryptoGO 穩定幣支付
npx skills add paid-tw/skills --skill ecpay         # 只裝綠界科技
npx skills add paid-tw/skills --skill payuni        # 只裝 PAYUNi

# 安裝多個
npx skills add paid-tw/skills --skill newebpay ecpay

# 安裝全部
npx skills add paid-tw/skills --all
```

支援多種 AI coding agents：Claude Code、Cursor、Codex、GitHub Copilot、Antigravity、Roo Code 等。

### 方式二：Claude Code Plugin

```bash
# 1. 新增 marketplace
/plugin marketplace add paid-tw/skills

# 2. 選擇性安裝需要的 plugin
/plugin install newebpay@taiwan-payment-skills    # 藍新金流
/plugin install ecpay@taiwan-payment-skills       # 綠界科技
/plugin install payuni@taiwan-payment-skills      # PAYUNi

# 或安裝 help plugin 查看所有選項
/plugin install payment-help@taiwan-payment-skills
```

### 方式三：手動安裝

```bash
# 下載並安裝特定 skill
git clone --depth 1 https://github.com/paid-tw/skills.git /tmp/paid-tw-skills
cp -r /tmp/paid-tw-skills/plugins/newebpay/skills/newebpay ~/.claude/skills/
rm -rf /tmp/paid-tw-skills
```

## 快速開始

不確定從哪開始？安裝 `payment-help` skill：

```bash
npx skills add paid-tw/skills --skill payment-help
```

然後問 Claude：

```
台灣有哪些金流可以用？
```

或輸入 `/payment-help` 查看所有可用的 skills。

## 可用的 Skills

### payment-help

列出所有可用的支付 skills 和推薦。

**觸發方式**：說「台灣金流」「支付整合」「哪個金流比較好」

### newebpay（藍新金流）

安裝 `newebpay` plugin 後可使用以下 skills：

| Skill | 說明 | 使用時機 |
|-------|------|---------|
| `/newebpay` | 總覽與環境設定 | 初次了解、環境設定 |
| `/newebpay-checkout` | MPG 幕前支付串接 | 建立交易、整合支付頁面 |
| `/newebpay-query` | 交易查詢 API | 查詢訂單狀態 |
| `/newebpay-refund` | 退款 API | 信用卡/電子錢包退款 |

**觸發關鍵字**：藍新、NewebPay、藍新金流

**支援的支付方式**：

- 信用卡（一次付清、分期、紅利）
- 行動支付（LINE Pay、Apple Pay、Google Pay）
- ATM 轉帳、超商代碼/條碼

### kryptogo-pay（KryptoGO 穩定幣支付）

安裝 `kryptogo-pay` plugin 後可使用以下 skills：

| Skill | 說明 | 使用時機 |
|-------|------|---------|
| `/kryptogo-pay` | 總覽與環境設定 | 初次了解、環境設定 |
| `/kryptogo-pay-checkout` | 建立 Payment Intent 收款 | 建立支付、穩定幣收款 |
| `/kryptogo-pay-query` | 查詢 Payment Intent | 查詢支付狀態 |
| `/kryptogo-pay-webhook` | Webhook 回調處理 | 接收支付狀態通知 |
| `/kryptogo-pay-transfer` | 代幣轉帳/提領 | USDT/USDC 轉帳提領 |

**觸發關鍵字**：KryptoGO、穩定幣支付、crypto payment、USDT 收款

**支援的支付方式**：

- 穩定幣（USDT、USDC on Arbitrum）
- KryptoGO Wallet、MetaMask、WalletConnect
- QR Code 轉帳

### ecpay

| Skill | 說明 |
|-------|------|
| `/ecpay` | 綠界整合總覽與環境設定 |
| `/ecpay-checkout` | AIO 全方位金流串接（CheckMacValue 簽章、ReturnURL 通知、冪等入帳、測試指南）|

**支援的支付方式**：

- 信用卡（一次付清、分期、3D 驗證）
- Apple Pay
- ATM 轉帳、超商代碼/條碼（非同步付款）

### payuni

🚧 開發中

## 使用方式

安裝後，有三種方式使用：

### 1. 自動觸發

在對話中提到相關關鍵字時，Claude 會自動載入對應的 skill：

```
> 幫我串接藍新金流的信用卡付款
> 我想用綠界的電子發票 API
```

### 2. 手動呼叫

使用 `/skill名稱` 直接呼叫：

```
> /newebpay
> /ecpay
> /payuni
```

### 3. 功能專用 Skill（推薦）

直接呼叫特定功能的 skill，獲得最精準的回答：

```
> /newebpay-checkout LINE Pay   # LINE Pay 串接
> /newebpay-checkout 信用卡分期  # 信用卡分期串接
> /newebpay-query               # 查詢交易狀態
> /newebpay-refund 信用卡        # 信用卡退款
```

### 觸發關鍵字

| Skill | 觸發關鍵字 |
|-------|-----------|
| newebpay | 藍新、NewebPay、藍新金流 |
| newebpay-checkout | 藍新串接、建立交易、MPG |
| newebpay-query | 藍新查詢、查詢訂單、交易狀態 |
| newebpay-refund | 藍新退款、信用卡退款、取消交易 |
| kryptogo-pay | KryptoGO、穩定幣支付、crypto payment |
| kryptogo-pay-checkout | KryptoGO 收款、建立支付、payment intent |
| kryptogo-pay-query | KryptoGO 查詢、支付狀態 |
| kryptogo-pay-webhook | KryptoGO webhook、回調、callback |
| kryptogo-pay-transfer | KryptoGO 轉帳、提領、withdrawal |
| payment-help | 台灣金流、支付整合、哪個金流 |

## 目錄結構

```
paid-tw/skills/
├── .claude-plugin/
│   └── marketplace.json       # Marketplace 目錄
├── plugins/
│   ├── payment-help/          # Help plugin
│   │   └── skills/payment-help/
│   ├── newebpay/              # 藍新金流 plugin（含多個 skills）
│   │   ├── .claude-plugin/
│   │   │   └── plugin.json
│   │   └── skills/
│   │       ├── newebpay/           # 總覽
│   │       ├── newebpay-checkout/  # 串接
│   │       │   └── references/
│   │       ├── newebpay-query/     # 查詢
│   │       └── newebpay-refund/    # 退款
│   ├── kryptogo-pay/          # KryptoGO 穩定幣支付 plugin
│   │   ├── .claude-plugin/
│   │   │   └── plugin.json
│   │   └── skills/
│   │       ├── kryptogo-pay/          # 總覽
│   │       ├── kryptogo-pay-checkout/ # 收款
│   │       │   └── references/
│   │       ├── kryptogo-pay-query/    # 查詢
│   │       ├── kryptogo-pay-webhook/  # Webhook
│   │       │   └── references/
│   │       └── kryptogo-pay-transfer/ # 轉帳
│   │           └── references/
│   ├── ecpay/                 # 綠界科技 plugin
│   └── payuni/                # PAYUNi plugin
├── README.md
├── AGENTS.md
└── LICENSE
```

## 貢獻

歡迎貢獻！請參閱 [貢獻指南](docs/contributing.md)。

### 新增支付服務

1. Fork 這個 repo
2. 在 `plugins/` 下建立新目錄
3. 建立 `.claude-plugin/plugin.json` 和 `skills/<name>/SKILL.md`
4. 更新 `.claude-plugin/marketplace.json`
5. 提交 Pull Request

## 注意事項

- 各支付服務的 API 文件版權歸原業者所有
- 請以各業者官方最新文件為準
- HashKey、HashIV 等敏感資訊請妥善保管，不可暴露於前端

## 相關資源

- [藍新金流](https://www.newebpay.com)
- [KryptoGO Payment](https://payment.kryptogo.com)
- [綠界科技](https://www.ecpay.com.tw)
- [PAYUNi](https://www.payuni.com.tw)

## Contributors

- [@_linyiru](https://www.threads.com/@_linyiru)
- [@handbro666](https://www.threads.com/@handbro666)

## 授權

MIT License

---

Made with ❤️ for Taiwan developers
