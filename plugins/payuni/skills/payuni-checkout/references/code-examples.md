# PAYUNi 程式碼範例

## Node.js / TypeScript

### 完整支付模組

```typescript
// lib/payuni.ts
import crypto from 'crypto';

interface PayuniConfig {
  merchantId: string;
  hashKey: string;
  hashIV: string;
  isTest: boolean;
}

interface CreatePaymentParams {
  orderId: string;
  amount: number;
  productName: string;
  returnUrl: string;
  notifyUrl: string;
  paymentMethods?: string[];
}

const config: PayuniConfig = {
  merchantId: process.env.PAYUNI_MERCHANT_ID!,
  hashKey: process.env.PAYUNI_HASH_KEY!,
  hashIV: process.env.PAYUNI_HASH_IV!,
  isTest: process.env.PAYUNI_TEST_MODE === 'true',
};

// AES-256-CBC 加密
function encrypt(data: string): string {
  const key = Buffer.from(config.hashKey.padEnd(32, '\0').slice(0, 32), 'utf8');
  const iv = Buffer.from(config.hashIV.padEnd(16, '\0').slice(0, 16), 'utf8');
  
  const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
  let encrypted = cipher.update(data, 'utf8', 'hex');
  encrypted += cipher.final('hex');
  return encrypted;
}

// AES-256-CBC 解密
function decrypt(encryptedData: string): string {
  const key = Buffer.from(config.hashKey.padEnd(32, '\0').slice(0, 32), 'utf8');
  const iv = Buffer.from(config.hashIV.padEnd(16, '\0').slice(0, 16), 'utf8');
  
  const decipher = crypto.createDecipheriv('aes-256-cbc', key, iv);
  let decrypted = decipher.update(encryptedData, 'hex', 'utf8');
  decrypted += decipher.final('utf8');
  return decrypted;
}

// SHA256 雜湊
function generateHashInfo(encryptInfo: string): string {
  return crypto
    .createHash('sha256')
    .update(encryptInfo)
    .digest('hex')
    .toUpperCase();
}

// 取得 API 端點
function getEndpoint(): string {
  return config.isTest
    ? 'https://sandbox-api.payuni.com.tw/api/upp'
    : 'https://api.payuni.com.tw/api/upp';
}

// 建立付款表單資料
export function createPayment(params: CreatePaymentParams) {
  const tradeInfo: Record<string, string | number> = {
    MerID: config.merchantId,
    MerTradeNo: params.orderId,
    TradeAmt: params.amount,
    ProdDesc: params.productName,
    ReturnURL: params.returnUrl,
    NotifyURL: params.notifyUrl,
  };

  const queryString = new URLSearchParams(tradeInfo as any).toString();
  const encryptInfo = encrypt(queryString);
  const hashInfo = generateHashInfo(encryptInfo);

  return {
    MerID: config.merchantId,
    EncryptInfo: encryptInfo,
    HashInfo: hashInfo,
    actionUrl: getEndpoint(),
  };
}

// 驗證 Webhook 簽名
export function verifyCheckCode(params: Record<string, string>): boolean {
  const { CheckCode, ...otherParams } = params;
  if (!CheckCode) return false;

  const sortedKeys = Object.keys(otherParams).sort();
  const paramStr = sortedKeys.map(k => \`\${k}=\${otherParams[k]}\`).join('&');
  const signStr = \`HashKey=\${config.hashKey}&\${paramStr}&HashIV=\${config.hashIV}\`;
  
  const calculated = crypto
    .createHash('sha256')
    .update(signStr)
    .digest('hex')
    .toUpperCase();

  try {
    return crypto.timingSafeEqual(
      Buffer.from(calculated),
      Buffer.from(CheckCode)
    );
  } catch {
    return false;
  }
}
```

---

## PHP (Laravel)

### 支付 Service

```php
<?php
// app/Services/PayuniService.php

namespace App\Services;

class PayuniService
{
    private string $merchantId;
    private string $hashKey;
    private string $hashIV;
    private bool $isTest;

    public function __construct()
    {
        $this->merchantId = config('services.payuni.merchant_id');
        $this->hashKey = config('services.payuni.hash_key');
        $this->hashIV = config('services.payuni.hash_iv');
        $this->isTest = config('services.payuni.test_mode', true);
    }

    private function encrypt(string $data): string
    {
        $key = str_pad($this->hashKey, 32, "\0");
        $iv = str_pad($this->hashIV, 16, "\0");
        
        return bin2hex(openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv));
    }

    private function generateHashInfo(string $encryptInfo): string
    {
        return strtoupper(hash('sha256', $encryptInfo));
    }

    public function createPayment(array $params): array
    {
        $tradeInfo = [
            'MerID' => $this->merchantId,
            'MerTradeNo' => $params['order_id'],
            'TradeAmt' => $params['amount'],
            'ProdDesc' => $params['product_name'],
            'ReturnURL' => $params['return_url'],
            'NotifyURL' => $params['notify_url'],
        ];

        $queryString = http_build_query($tradeInfo);
        $encryptInfo = $this->encrypt($queryString);
        $hashInfo = $this->generateHashInfo($encryptInfo);

        return [
            'MerID' => $this->merchantId,
            'EncryptInfo' => $encryptInfo,
            'HashInfo' => $hashInfo,
            'action_url' => $this->getEndpoint(),
        ];
    }

    public function verifyCheckCode(array $params): bool
    {
        $checkCode = $params['CheckCode'] ?? null;
        if (!$checkCode) return false;

        unset($params['CheckCode']);
        ksort($params);

        $paramStr = http_build_query($params);
        $signStr = "HashKey={$this->hashKey}&{$paramStr}&HashIV={$this->hashIV}";
        $calculated = strtoupper(hash('sha256', $signStr));

        return hash_equals($calculated, $checkCode);
    }
}
```
