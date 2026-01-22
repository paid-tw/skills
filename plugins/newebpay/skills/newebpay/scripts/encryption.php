<?php
/**
 * 藍新金流加解密工具類別
 * 
 * 提供 AES256 加解密、SHA256 簽章驗證等功能
 * 版本: NDNF-1.1.9 compatible
 */

class NewebPayCrypto {
    private $key;
    private $iv;
    
    /**
     * 建構函式
     * 
     * @param string $key HashKey from NewebPay
     * @param string $iv HashIV from NewebPay
     */
    public function __construct($key, $iv) {
        $this->key = $key;
        $this->iv = $iv;
    }
    
    /**
     * AES256-CBC 加密
     * 
     * @param string $data 要加密的資料 (通常是 http_build_query 的結果)
     * @return string 加密後的十六進位字串
     */
    public function encrypt($data) {
        $encrypted = openssl_encrypt(
            $data, 
            "AES-256-CBC", 
            $this->key, 
            OPENSSL_RAW_DATA, 
            $this->iv
        );
        
        return bin2hex($encrypted);
    }
    
    /**
     * AES256-CBC 解密
     * 
     * @param string $encrypted_hex 加密的十六進位字串
     * @return string 解密後的原始資料
     */
    public function decrypt($encrypted_hex) {
        $encrypted = hex2bin($encrypted_hex);
        
        $decrypted = openssl_decrypt(
            $encrypted, 
            "AES-256-CBC", 
            $this->key, 
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, 
            $this->iv
        );
        
        // 移除 PKCS7 padding
        return $this->stripPadding($decrypted);
    }
    
    /**
     * 移除 PKCS7 padding
     * 
     * @param string $string 需要移除 padding 的字串
     * @return string 移除 padding 後的字串
     */
    private function stripPadding($string) {
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        $pcheck = substr($string, -$slast);
        
        if (preg_match("/$slastc{" . $slast . "}/", $string)) {
            return substr($string, 0, strlen($string) - $slast);
        }
        
        return $string;
    }
    
    /**
     * 產生 SHA256 簽章 (TradeSha)
     * 
     * @param string $trade_info 已加密的 TradeInfo
     * @return string SHA256 簽章 (大寫)
     */
    public function generateTradeSha($trade_info) {
        $hash_string = "HashKey={$this->key}&{$trade_info}&HashIV={$this->iv}";
        return strtoupper(hash("sha256", $hash_string));
    }
    
    /**
     * 驗證 SHA256 簽章
     * 
     * @param string $trade_info 加密的 TradeInfo
     * @param string $trade_sha 要驗證的 TradeSha
     * @return bool 驗證結果
     */
    public function verifyTradeSha($trade_info, $trade_sha) {
        $calculated = $this->generateTradeSha($trade_info);
        return $calculated === $trade_sha;
    }
    
    /**
     * 產生 CheckCode (用於交易查詢等 API)
     * 
     * @param array $params 包含 Amt, MerchantID, MerchantOrderNo 的陣列
     * @return string CheckCode (大寫)
     */
    public function generateCheckCode($params) {
        // 必要參數
        $required = ['Amt', 'MerchantID', 'MerchantOrderNo'];
        foreach ($required as $key) {
            if (!isset($params[$key])) {
                throw new Exception("Missing required parameter: {$key}");
            }
        }
        
        // 按字母順序排序
        ksort($params);
        $query_string = http_build_query($params);
        
        $check_string = "HashIV={$this->iv}&{$query_string}&HashKey={$this->key}";
        return strtoupper(hash("sha256", $check_string));
    }
    
    /**
     * 產生 CheckValue (用於單筆交易查詢)
     * 
     * @param array $params 包含 Amt, MerchantID, MerchantOrderNo 的陣列
     * @return string CheckValue (大寫)
     */
    public function generateCheckValue($params) {
        // 必要參數
        $required = ['Amt', 'MerchantID', 'MerchantOrderNo'];
        foreach ($required as $key) {
            if (!isset($params[$key])) {
                throw new Exception("Missing required parameter: {$key}");
            }
        }
        
        // 按字母順序排序
        ksort($params);
        $query_string = http_build_query($params);
        
        $check_string = "IV={$this->iv}&{$query_string}&Key={$this->key}";
        return strtoupper(hash("sha256", $check_string));
    }
}

// ============================================
// 使用範例
// ============================================

// 初始化
$crypto = new NewebPayCrypto(
    "Fs5cX1TGqYM2PpdbE14a9H83YQSQF5jn",  // 你的 HashKey
    "C6AcmfqJILwgnhIP"                    // 你的 HashIV
);

// ============================================
// 範例 1: MPG 交易加密
// ============================================
$trade_data = [
    'MerchantID' => 'MS12345678',
    'TimeStamp' => time(),
    'Version' => '2.3',
    'RespondType' => 'JSON',
    'MerchantOrderNo' => 'ORDER_' . time(),
    'Amt' => '1000',
    'ItemDesc' => '測試商品',
    'Email' => 'test@example.com',
    'CREDIT' => '1',
    'ReturnURL' => 'https://yourdomain.com/return',
    'NotifyURL' => 'https://yourdomain.com/notify',
];

$trade_info_raw = http_build_query($trade_data);
$trade_info_encrypted = $crypto->encrypt($trade_info_raw);
$trade_sha = $crypto->generateTradeSha($trade_info_encrypted);

echo "TradeInfo (加密): {$trade_info_encrypted}\n";
echo "TradeSha: {$trade_sha}\n\n";

// ============================================
// 範例 2: 解密回傳資料
// ============================================
// 假設從 POST 接收到加密資料
// $received_trade_info = $_POST['TradeInfo'];
// $received_trade_sha = $_POST['TradeSha'];

// 1. 先驗證簽章
if (isset($received_trade_info) && isset($received_trade_sha)) {
    if ($crypto->verifyTradeSha($received_trade_info, $received_trade_sha)) {
        // 2. 驗證通過後解密
        $decrypted = $crypto->decrypt($received_trade_info);
        parse_str($decrypted, $result);
        
        // 3. 處理結果
        if ($result['Status'] === 'SUCCESS') {
            echo "交易成功!\n";
            echo "訂單編號: {$result['MerchantOrderNo']}\n";
            echo "藍新交易序號: {$result['TradeNo']}\n";
            echo "金額: {$result['Amt']}\n";
        }
    } else {
        echo "簽章驗證失敗!\n";
    }
}

// ============================================
// 範例 3: 產生交易查詢的 CheckValue
// ============================================
$query_params = [
    'MerchantID' => 'MS12345678',
    'Amt' => '1000',
    'MerchantOrderNo' => 'ORDER_1234567890'
];

$check_value = $crypto->generateCheckValue($query_params);
echo "CheckValue: {$check_value}\n\n";

// ============================================
// 範例 4: 完整的 MPG 表單產生
// ============================================
function generateMPGForm($crypto, $merchant_id, $order_data, $action_url) {
    // 準備交易資料
    $trade_data = array_merge([
        'MerchantID' => $merchant_id,
        'TimeStamp' => time(),
        'Version' => '2.3',
        'RespondType' => 'JSON',
    ], $order_data);
    
    // 加密
    $trade_info_raw = http_build_query($trade_data);
    $trade_info = $crypto->encrypt($trade_info_raw);
    $trade_sha = $crypto->generateTradeSha($trade_info);
    
    // 產生 HTML 表單
    $html = <<<HTML
<form method="post" action="{$action_url}" id="newebpay-form">
    <input type="hidden" name="MerchantID" value="{$merchant_id}">
    <input type="hidden" name="Version" value="2.3">
    <input type="hidden" name="TradeInfo" value="{$trade_info}">
    <input type="hidden" name="TradeSha" value="{$trade_sha}">
    <button type="submit">前往付款</button>
</form>
HTML;
    
    return $html;
}

// 使用範例
$order_data = [
    'MerchantOrderNo' => 'ORDER_' . time(),
    'Amt' => '1000',
    'ItemDesc' => '測試商品',
    'Email' => 'buyer@example.com',
    'CREDIT' => '1',
    'VACC' => '1',
    'ReturnURL' => 'https://yourdomain.com/return',
    'NotifyURL' => 'https://yourdomain.com/notify',
];

$form_html = generateMPGForm(
    $crypto,
    'MS12345678',
    $order_data,
    'https://ccore.newebpay.com/MPG/mpg_gateway'  // 測試環境
);

echo $form_html;
?>
