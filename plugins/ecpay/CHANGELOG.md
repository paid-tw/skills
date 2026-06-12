# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-06-12

### Added
- `/ecpay-checkout` - AIO 全方位金流串接 skill（實戰驗證：測試環境實刷
  信用卡 + 3D 驗證 + ReturnURL 通知入帳全流程通過）
  - CheckMacValue 演算法文件（.NET URL encode 陷阱、Node.js/PHP 實作）
  - ReturnURL 通知處理與冪等設計（SimulatePaid、重送、金額比對）
  - 測試環境指南（官方測試金鑰/卡號、後台模擬付款、Playwright E2E 踩坑紀錄）
  - Node.js 與 PHP 完整程式碼範例

### Changed
- `/ecpay` 總覽 skill 脫離「開發中」狀態，補上實際內容與安全注意事項

## [1.1.0] - 2026-01-22

### Added
- Added `skills` path field to plugin.json per official spec

## [1.0.0] - 2026-01-20

### Added
- Initial release
- `/ecpay` - ECPay integration guide
- Environment setup for test and production
- Support for credit card, ATM, CVS, and electronic invoice
