---
name: newebpay
description: 藍新金流(NewebPay)總覽與快速開始指南。觸發於「藍新」「NewebPay」「藍新金流」「藍新怎麼用」「藍新 help」「藍新串接教學」相關問題。
argument-hint: "[功能: checkout/query/refund]"
allowed-tools: Read, Grep, Glob
license: MIT
metadata:
  author: paid-tw
  version: "1.0.0"
---

# 藍新金流整合指南

本 skill 提供藍新金流(NewebPay)整合總覽，版本 NDNF-1.1.9。

## 用戶需求分析

用戶輸入: `$ARGUMENTS`

根據用戶需求，引導至對應的 skill：
- 若包含「串接」「checkout」「建立交易」「MPG」→ 建議使用 `/newebpay-checkout`
- 若包含「查詢」「query」「訂單狀態」→ 建議使用 `/newebpay-query`
- 若包含「退款」「refund」「取消」→ 建議使用 `/newebpay-refund`
- 若無特定指定 → 提供以下總覽

## 可用的 Skills

| Skill | 功能 | 使用時機 |
|-------|------|---------|
| `/newebpay-checkout` | MPG 幕前支付串接 | 建立交易、整合支付頁面 |
| `/newebpay-query` | 交易查詢 | 查詢訂單狀態 |
| `/newebpay-refund` | 退款作業 | 信用卡退款、電子錢包退款 |

## 環境設定

**測試環境:**
- API Base: `https://ccore.newebpay.com`
- 商店代號: 從藍新金流測試帳號取得
- HashKey/HashIV: 從藍新金流測試環境取得

**正式環境:**
- API Base: `https://core.newebpay.com`
- 商店代號: 從藍新金流正式帳號取得
- HashKey/HashIV: 從藍新金流正式環境取得

**環境變數設定:**

```bash
NEWEBPAY_MERCHANT_ID=MS12345678
NEWEBPAY_HASH_KEY=your_hash_key
NEWEBPAY_HASH_IV=your_hash_iv
NEWEBPAY_ENV=test  # test 或 production
```

## 支援的支付方式

- **信用卡**: 一次付清、分期付款、紅利折抵、銀聯卡、美國運通卡
- **行動支付**: Apple Pay、Google Pay、Samsung Pay
- **電子錢包**: LINE Pay、台灣Pay、BitoPay、TWQR
- **ATM**: WebATM、ATM轉帳
- **超商**: 代碼繳費、條碼繳費、取貨付款

## 加密解密（共用）

所有藍新 API 都使用相同的加密機制：

### AES256 加密

```php
function encrypt($data, $key, $iv) {
    $encrypted = openssl_encrypt(
        $data,
        "AES-256-CBC",
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
    return bin2hex($encrypted);
}
```

### AES256 解密

```php
function decrypt($encrypted_hex, $key, $iv) {
    $decrypted = openssl_decrypt(
        hex2bin($encrypted_hex),
        "AES-256-CBC",
        $key,
        OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
        $iv
    );
    return rtrim($decrypted, "\x00..\x1F");
}
```

### SHA256 簽章

```php
function generateSha($trade_info, $key, $iv) {
    $hash_string = "HashKey={$key}&{$trade_info}&HashIV={$iv}";
    return strtoupper(hash("sha256", $hash_string));
}
```

完整加解密實作請參閱 [scripts/encryption.php](scripts/encryption.php)

## 重要注意事項

1. **安全性**
   - HashKey 和 HashIV 必須妥善保管，不可暴露在前端
   - 所有交易資料都必須經過 AES256 加密
   - 建議使用 NotifyURL 在後端接收交易結果

2. **時間戳記**
   - TimeStamp 必須是 Unix timestamp (秒數)
   - 容許誤差值為 ±120 秒

3. **訂單編號**
   - MerchantOrderNo 在同一商店中不可重複
   - 長度限制 30 字元，限英文、數字、底線

4. **Port 限制**
   - ReturnURL、NotifyURL、CustomerURL 只接受 80 和 443 Port

## 相關 Skills

- `/newebpay-checkout` - MPG 幕前支付串接
- `/newebpay-query` - 交易查詢
- `/newebpay-refund` - 退款作業
- `/payment-help` - 查看所有可用的支付 skills
