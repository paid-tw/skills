# 貢獻指南

感謝你有興趣為台灣支付金流 Skills 做出貢獻！

## 如何貢獻

### 回報問題

如果你發現 bug 或有功能建議，請在 GitHub Issues 開啟新的 issue。

### 新增支付服務 Skill

1. Fork 這個 repository
2. 建立新的 branch: `git checkout -b feature/new-payment-provider`
3. 在 `skills/` 下建立新目錄
4. 按照以下結構建立檔案
5. 提交 Pull Request

## Skill 結構

每個 skill 必須包含以下結構：

```
skills/<provider-name>/
├── SKILL.md              # 必要：主要說明文件
├── references/           # 建議：詳細 API 文件
│   ├── api-reference.md
│   ├── error-codes.md
│   └── use-cases.md
└── scripts/              # 選用：工具腳本
    └── encryption.php
```

## SKILL.md 格式

```markdown
---
name: provider-name
description: |
  簡短描述這個 skill 的功能。
  說明何時會用到這個 skill。
user-invocable: true
allowed-tools: Read, Grep, Glob, Bash
---

# 支付服務名稱

## 快速開始

### 環境設定
- 測試環境 URL
- 正式環境 URL
- 必要的金鑰說明

### 支援的支付方式
- 列出支援的支付方式

## 核心流程
說明主要的串接流程

## 加密方式
提供加解密的說明和範例

## 常見使用情境
提供 2-3 個常見的使用範例

## 參考資源
- 連結到 references/ 下的詳細文件

## 重要注意事項
- 安全性提醒
- 常見錯誤
```

## Frontmatter 欄位

| 欄位 | 必要 | 說明 |
|------|------|------|
| `name` | 是 | 小寫，用連字符，最多 64 字元 |
| `description` | 是 | 描述功能與使用時機 |
| `user-invocable` | 否 | 預設 true |
| `allowed-tools` | 否 | 允許的工具 |

## 程式碼風格

- 使用繁體中文撰寫說明
- 技術名詞保留英文
- 程式碼範例優先使用 PHP，但歡迎提供多語言版本

## 審核標準

PR 會根據以下標準審核：

1. SKILL.md 格式正確
2. 內容完整且正確
3. 提供足夠的使用範例
4. 包含必要的安全性提醒

## 問題與討論

如有任何問題，歡迎在 GitHub Discussions 發起討論。
