# 疑難排解

本文檔列出藍新金流整合過程中常見的問題和解決方案。

## 目錄

- [加密解密問題](#加密解密問題)
- [參數設定問題](#參數設定問題)
- [支付方式問題](#支付方式問題)
- [回傳處理問題](#回傳處理問題)
- [測試環境問題](#測試環境問題)
- [進階問題](#進階問題)
- [常見錯誤訊息對照](#常見錯誤訊息對照)
- [取得協助](#取得協助)

---

## 加密解密問題

### Q1: TradeInfo 解密失敗

**症狀**: 收到回傳資料但無法解密,或解密後是亂碼

**可能原因**:
1. HashKey 或 HashIV 錯誤
2. 使用測試環境的金鑰解正式環境的資料 (或反之)
3. 未正確移除 PKCS7 padding
4. 加密模式設定錯誤
5. **用標準 PKCS7(pad ≤ 16)處理藍新的 padding** — 藍新以 32 bytes 為單位做 padding(官方 PHP 範例 `addpadding()` 的 `$block = 32`),pad byte 可達 0x20,Node.js 等語言內建的 autoPadding 解不開(見下方 Node.js 特別注意)

**解決方案**:
```php
// 1. 確認使用正確的金鑰
$key = "你的HashKey";  // 從藍新金流後台取得
$iv = "你的HashIV";

// 2. 確認環境對應
// 測試環境: ccore.newebpay.com
// 正式環境: core.newebpay.com

// 3. 正確的解密流程
function decrypt_trade_info($encrypted_hex, $key, $iv) {
    // 轉為二進位
    $encrypted = hex2bin($encrypted_hex);
    
    // AES-256-CBC 解密
    $decrypted = openssl_decrypt(
        $encrypted, 
        "AES-256-CBC", 
        $key, 
        OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
        $iv
    );
    
    // 移除 PKCS7 padding
    $slast = ord(substr($decrypted, -1));
    if (preg_match("/" . chr($slast) . "{" . $slast . "}/", $decrypted)) {
        $decrypted = substr($decrypted, 0, strlen($decrypted) - $slast);
    }
    
    return $decrypted;
}
```

**Node.js 特別注意 — 真實通知解密隨機失敗(bad decrypt)**:

**症狀特徵**: 自己 `encrypt()` 再 `decrypt()` 測試都正常,但藍新送來的真實 Notify 約半數解密失敗。

**原因**: Node 的 `crypto` 預設 autoPadding 只接受標準 PKCS7(pad byte ≤ 16),但藍新以 32 bytes 為單位 padding,pad byte 落在 17~32 時 `decipher.final()` 直接丟 `bad decrypt`。因為自己加密用的是標準 PKCS7,自我測試永遠發現不了這個問題。

```javascript
// 正確: 關掉 autoPadding,自行驗證並移除 1~32 的 padding
function decryptTradeInfo(encryptedHex, hashKey, hashIv) {
  const decipher = crypto.createDecipheriv('aes-256-cbc', hashKey, hashIv);
  decipher.setAutoPadding(false);
  const buf = Buffer.concat([decipher.update(encryptedHex, 'hex'), decipher.final()]);
  const pad = buf[buf.length - 1];
  if (!(pad >= 1 && pad <= 32 && pad <= buf.length)) {
    throw new Error('padding 異常(HashKey/HashIV 不對?)');
  }
  return buf.slice(0, buf.length - pad).toString('utf8');
}
```

### Q2: TradeSha 驗證失敗

**症狀**: 簽章驗證不通過

**解決方案**:
```php
// 正確的 TradeSha 產生方式
$hash_string = "HashKey={$key}&{$trade_info}&HashIV={$iv}";
$trade_sha = strtoupper(hash("sha256", $hash_string));

// 注意事項:
// 1. 順序固定: HashKey -> TradeInfo -> HashIV
// 2. 使用 & 符號連接
// 3. 結果必須轉大寫
// 4. TradeInfo 是已加密的十六進位字串
```

## 參數設定問題

### Q3: MPG01012 - 商店訂單編號設定錯誤

**症狀**: 提交交易時收到 MPG01012 錯誤

**原因**:
- 訂單編號包含不允許的字元
- 訂單編號超過30字元
- 訂單編號重複

**解決方案**:
```php
// 正確的訂單編號產生方式
function generate_order_no() {
    // 格式: ORDER_ + timestamp + 隨機數
    return 'ORDER_' . time() . '_' . rand(1000, 9999);
}

// 驗證訂單編號
function validate_order_no($order_no) {
    // 1. 長度檢查
    if (strlen($order_no) > 30) {
        return false;
    }
    
    // 2. 格式檢查 (只允許英數字和底線)
    if (!preg_match('/^[A-Za-z0-9_]+$/', $order_no)) {
        return false;
    }
    
    // 3. 唯一性檢查 (查詢資料庫)
    // ...
    
    return true;
}
```

### Q4: MPG01029 - 訂單金額與細項總和不符

**症狀**: 使用 OrderDetail 參數時交易失敗

**解決方案**:
```php
$items = [
    ['ItemName' => '商品A', 'ItemAmt' => 500, ...],
    ['ItemName' => '商品B', 'ItemAmt' => 300, ...],
    ['ItemName' => '運費', 'ItemAmt' => 100, ...],
];

// 計算總金額
$total = array_sum(array_column($items, 'ItemAmt'));

$order_data = [
    'Amt' => $total,  // 必須等於所有品項總和
    'OrderDetail' => json_encode($items),
];
```

### Q5: MPG01014 - 網址設定錯誤

**症狀**: ReturnURL 或 NotifyURL 被拒絕

**原因**:
- URL 不是完整的 http:// 或 https://
- Port 不是 80 或 443
- URL 格式錯誤

**解決方案**:
```php
// 正確的 URL 格式
$return_url = 'https://yourdomain.com/payment/return';  // ✓
$notify_url = 'https://yourdomain.com:443/notify';      // ✓

// 錯誤示範
$wrong_url1 = 'yourdomain.com/return';              // ✗ 缺少 https://
$wrong_url2 = 'https://yourdomain.com:8080/notify'; // ✗ port 不是 80/443
$wrong_url3 = 'localhost/test';                     // ✗ 本地開發無法使用

// 驗證 URL
function validate_callback_url($url) {
    // 1. 必須是完整 URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // 2. 必須是 http 或 https
    $parsed = parse_url($url);
    if (!in_array($parsed['scheme'], ['http', 'https'])) {
        return false;
    }
    
    // 3. Port 必須是 80 或 443 (或不指定)
    if (isset($parsed['port']) && !in_array($parsed['port'], [80, 443])) {
        return false;
    }
    
    return true;
}
```

## 支付方式問題

### Q6: MPG02003 - 支付方式未啟用

**症狀**: 啟用某個支付方式後仍無法使用

**原因**:
- 商店後台未啟用該支付方式
- 該支付方式需要額外申請

**解決方案**:
1. 登入藍新金流會員專區
2. 進入「商店管理」
3. 檢查「支付方式設定」
4. 確認需要的支付方式已啟用
5. 特殊支付方式 (如 LINE Pay) 需聯繫客服申請

### Q7: 超商取貨付款無法使用

**症狀**: CVSCOM 參數設定後交易失敗

**檢查清單**:
```php
// 1. 金額限制
if ($amount > 20000) {
    // 超商取貨付款限制 20,000 元以下
}

// 2. 物流設定
$order_data = [
    'CVSCOM' => '2',       // 2=取貨付款
    'LgsType' => 'C2C',    // 必須設定物流型態
    'CustomerURL' => '...' // 必須設定取號通知 URL
];

// 3. 商店設定
// 需在藍新金流後台啟用物流服務
// 並設定退貨門市資訊
```

### Q8: ATM 轉帳顯示金額限制

**症狀**: 訂單金額在限制範圍內但仍無法使用 ATM

**原因**: ATM 轉帳和 WebATM 都有 49,999 元上限

**解決方案**:
```php
// 1. 檢查金額
if ($amount >= 50000) {
    // 不要啟用 VACC 和 WEBATM
    $order_data['CREDIT'] = '1';  // 改用信用卡
} else {
    $order_data['VACC'] = '1';
}

// 2. 高金額訂單的處理方式
if ($amount >= 50000) {
    // 建議使用:
    // - 信用卡
    // - 分期付款
    // - 拆分訂單
}
```

## 回傳處理問題

### Q9: NotifyURL 沒有收到通知

**症狀**: 付款完成但 NotifyURL 未被呼叫

**檢查清單**:
1. **URL 是否可公開存取**
   ```bash
   # 測試 NotifyURL 是否可以從外部存取
   curl https://yourdomain.com/notify
   ```

2. **防火牆/WAF 設定**
   ```php
   // 確認沒有封鎖藍新金流的 IP
   // 藍新金流的 IP 範圍可聯繫客服取得
   ```

3. **SSL 憑證**
   ```
   使用 https:// 時,確認 SSL 憑證有效
   ```

4. **回應格式**
   ```php
   // NotifyURL 必須回應 "SUCCESS"
   if ($payment_success) {
       echo "SUCCESS";
       exit;
   }
   ```

5. **網域要填 canonical(www/非 www 要注意)**
   ```
   NotifyURL 填 https://example.com 但主機把它 307/308 轉址到
   https://www.example.com 時,金流商的 POST 不會跟著轉址 — 通知直接丟失。
   NotifyURL/ReturnURL 一律填「不會被轉址」的最終網域。
   ```

### Q10: ReturnURL 和 NotifyURL 收到不同結果

**症狀**: 前端顯示成功但後端顯示失敗 (或反之)

**原因**:
- ReturnURL 可能因使用者關閉瀏覽器而未執行
- 應以 NotifyURL 的結果為準

**解決方案**:
```php
// return.php (前端頁面)
<?php
$order_no = $_POST['...'];  // 從回傳取得訂單號

// 不要直接更新訂單狀態!
// 只顯示"處理中"訊息
?>
<h1>付款處理中...</h1>
<p>請稍候,系統正在確認您的付款</p>
<script>
// 輪詢檢查訂單狀態
setInterval(function() {
    check_order_status('<?= $order_no ?>');
}, 2000);
</script>

// notify.php (後端處理)
<?php
// 這裡才更新訂單狀態
update_order_status($order_no, 'paid');
echo "SUCCESS";
```

### Q11: 收到重複的 NotifyURL 通知

**症狀**: 同一筆訂單收到多次付款通知

**原因**:
- 未正確回應 "SUCCESS"
- 處理時間過長導致藍新重送

**解決方案**:
```php
// 實作冪等性機制
function process_payment_notify($order_no, $trade_no) {
    $db = new PDO(/* ... */);
    
    // 1. 使用交易鎖定
    $db->beginTransaction();
    
    // 2. 檢查訂單狀態
    $stmt = $db->prepare("
        SELECT status FROM orders 
        WHERE order_no = ? 
        FOR UPDATE
    ");
    $stmt->execute([$order_no]);
    $order = $stmt->fetch();
    
    // 3. 如果已經處理過,直接返回成功
    if ($order['status'] === 'paid') {
        $db->commit();
        return "SUCCESS";
    }
    
    // 4. 更新訂單
    $stmt = $db->prepare("
        UPDATE orders 
        SET status = 'paid', trade_no = ?
        WHERE order_no = ?
    ");
    $stmt->execute([$trade_no, $order_no]);
    
    $db->commit();
    
    // 5. 快速回應 SUCCESS
    echo "SUCCESS";
    fastcgi_finish_request();  // 立即返回,後續處理在背景執行
    
    // 6. 後續處理 (發信、更新庫存等)
    send_confirmation_email($order_no);
    update_inventory($order_no);
}
```

## 測試環境問題

### Q12: 測試卡號無法使用

**症狀**: 使用測試卡號但交易失敗

**測試卡號資訊**:
```
一般信用卡:
- 卡號: 4000-2211-1111-1111
- 到期日: 任意未來日期 (如 12/28)
- CVV: 任意三碼 (如 123)

美國運通卡:
- 卡號: 3700-000000-00002
- 到期日: 任意未來日期
- CVV: 任意四碼

銀聯卡:
- 卡號: 6200-0000-0000-0005
- 到期日: 任意未來日期
- CVV: 任意三碼
```

**注意事項**:
- 測試卡號只能在測試環境使用
- 正式環境必須使用真實卡號
- 測試環境不會真正扣款

### Q13: 超商代碼測試問題

**症狀**: 測試環境取得超商代碼後無法繳費

**說明**:
- 測試環境的超商代碼是模擬產生的
- 無法實際到超商繳費
- 要測試付款完成流程,需要使用特殊測試代碼

**解決方案**:
```php
// 測試環境取號後
// 可以使用藍新提供的測試工具模擬付款
// 或聯繫客服取得測試方法
```

## 進階問題

### Q14: 大量訂單處理效能問題

**症狀**: 高峰時段訂單處理緩慢

**解決方案**:
```php
// 1. 使用佇列系統處理 NotifyURL
// notify.php
require 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

// 快速接收並放入佇列
$message = json_encode($_POST);
$channel->basic_publish($msg, '', 'payment_notifications');

echo "SUCCESS";  // 立即回應
fastcgi_finish_request();

// 2. Worker 處理實際業務邏輯
// worker.php
while(true) {
    $channel->basic_consume(
        'payment_notifications',
        '',
        false,
        true,
        false,
        false,
        function($msg) {
            process_payment(json_decode($msg->body));
        }
    );
    $channel->wait();
}
```

### Q15: 異常訂單對帳

**症狀**: 某些訂單在藍新後台顯示成功,但商店系統未更新

**解決方案**:
```php
// 定時對帳腳本
function reconcile_orders($date) {
    // 1. 取得當日所有"處理中"訂單
    $pending_orders = get_pending_orders($date);
    
    foreach ($pending_orders as $order) {
        // 2. 使用"單筆交易查詢" API 確認狀態
        $result = query_transaction($order['order_no']);
        
        if ($result['status'] === 'SUCCESS') {
            // 3. 補更新訂單狀態
            update_order_status($order['order_no'], 'paid');
            log_info("補單: {$order['order_no']}");
        }
    }
}

// 每天凌晨執行
// 0 2 * * * /usr/bin/php /path/to/reconcile.php
```

### Q16: NotifyURL 回 4xx/5xx 之後,藍新的重試行為與自救

**症狀**: NotifyURL 收到了通知但處理失敗(回 400/500),訂單卡在未付款

**藍新的行為(正式環境實測)**:
1. 失敗後數秒內會連續重試約 3 次
2. 全部失敗後,寄「API 串接 Notify URL 觸發失敗通知信」到商店聯絡信箱
3. **之後不再重送** — 這筆通知永久丟失,只能靠對帳補單

**自救方案(層層保險)**:
1. **ReturnURL 當場對帳**: 消費者付款完導回時,若訂單仍是「處理中」,立即呼叫「單筆交易查詢」API 確認並補單 — 這是最即時的補單時機,不用等排程
2. **定時對帳收尾**(見 Q15): 處理消費者付完款直接關閉視窗、沒有走 ReturnURL 的情況
3. **對帳要查所有發出過的訂單編號**: 若付款頁每次開啟都產生新的 MerchantOrderNo(藍新拒收重複單號的常見做法),消費者「刷卡成功後又重開付款頁」會讓最新單號不是實際付款那筆 — 單號要全部留底,對帳時逐一查詢
4. **Notify 處理失敗時留下完整現場**: 至少記錄錯誤訊息、收到的欄位名、TradeInfo 長度 — 通知不會重送,沒有 log 就永遠查不到死因

## 常見錯誤訊息對照

| 錯誤訊息 | 可能原因 | 解決方案 |
|---------|---------|---------|
| "時間戳記不可空白" | 未提供 TimeStamp | 加入 `time()` |
| "交易加密SHA資料不可空白" | 未提供 TradeSha | 產生 SHA256 簽章 |
| "檢查碼錯誤" | CheckValue 不正確 | 重新計算 CheckValue |
| "查無商店開啟任何金流服務" | 商店未啟用支付方式 | 至後台啟用 |
| "已存在相同的商店訂單編號" | 訂單編號重複 | 確保訂單編號唯一 |

## 取得協助

遇到無法解決的問題時:

1. **檢查官方文檔**
   - https://www.newebpay.com
   - 會員專區的技術文件

2. **聯繫客服**
   - 客服電話: (02) 2656-0773
   - 客服信箱: service@newebpay.com
   - 服務時間: 週一至週五 9:00-18:00

3. **提供資訊**
   - 商店代號
   - 測試/正式環境
   - 錯誤代碼
   - 完整的錯誤訊息
   - 訂單編號 (如果有)

4. **測試資源**
   - 測試環境 URL
   - 測試商店代號
   - 測試卡號
