# KryptoGO Payment 轉帳/提領程式碼範例

## 目錄

- [Node.js 範例](#nodejs-範例)
- [Python 範例](#python-範例)
- [cURL 範例](#curl-範例)

---

## Node.js 範例

### 基本轉帳

```javascript
const axios = require('axios');

async function transferTokens(chainId, contractAddress, amount, walletAddress) {
  const response = await axios.post(
    'https://wallet.kryptogo.app/v1/studio/api/asset_pro/transfer',
    {
      chain_id: chainId,
      contract_address: contractAddress,
      amount: amount,
      wallet_address: walletAddress,
    },
    {
      headers: {
        'X-STUDIO-API-KEY': process.env.KRYPTOGO_STUDIO_API_KEY,
        'Content-Type': 'application/json',
      },
    }
  );
  return response.data;
}

// 轉帳 USDT on Arbitrum
const result = await transferTokens(
  'arb',
  '0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9', // USDT
  '10.0',
  '0x3fE5aC82B997255b8226abb6aEfd91f405fe2e8e'
);

if (result.code === 0) {
  console.log('轉帳成功', {
    id: result.data.id,
    txHash: result.data.tx_hash,
    time: result.data.transfer_time,
  });
}
```

### Express 提領端點（含安全驗證）

```javascript
const express = require('express');
const router = express.Router();

// Arbitrum 代幣合約地址
const TOKENS = {
  USDT: '0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9',
  USDC: '0xff970a61a04b1ca14834a43f5de4533ebddb5cc8',
};

router.post('/api/withdraw', authMiddleware, async (req, res) => {
  const { token, amount, wallet_address } = req.body;

  // 驗證參數
  if (!TOKENS[token]) {
    return res.status(400).json({ error: '不支援的代幣' });
  }

  if (!wallet_address || !wallet_address.startsWith('0x')) {
    return res.status(400).json({ error: '錢包地址格式不正確' });
  }

  if (parseFloat(amount) <= 0) {
    return res.status(400).json({ error: '金額必須大於 0' });
  }

  try {
    const result = await transferTokens(
      'arb',
      TOKENS[token],
      amount,
      wallet_address
    );

    if (result.code === 0) {
      // 記錄提領紀錄
      await WithdrawalLog.create({
        user_id: req.user.id,
        token,
        amount,
        wallet_address,
        tx_hash: result.data.tx_hash,
        transfer_id: result.data.id,
      });

      res.json({
        success: true,
        tx_hash: result.data.tx_hash,
      });
    } else if (result.code === 4015) {
      res.status(400).json({
        error: '餘額不足',
        insufficient_amount: result.data.insufficient_amount,
      });
    } else {
      res.status(500).json({ error: result.message });
    }
  } catch (error) {
    res.status(500).json({ error: '轉帳失敗，請稍後重試' });
  }
});

module.exports = router;
```

### 批次轉帳

```javascript
async function batchTransfer(transfers) {
  const results = [];

  for (const transfer of transfers) {
    try {
      const result = await transferTokens(
        transfer.chainId,
        transfer.contractAddress,
        transfer.amount,
        transfer.walletAddress
      );
      results.push({
        ...transfer,
        success: result.code === 0,
        txHash: result.data?.tx_hash,
        error: result.code !== 0 ? result.message : null,
      });
    } catch (error) {
      results.push({
        ...transfer,
        success: false,
        error: error.message,
      });
    }

    // 避免超過 API rate limit
    await new Promise(resolve => setTimeout(resolve, 1000));
  }

  return results;
}

// 使用範例
const transfers = [
  { chainId: 'arb', contractAddress: TOKENS.USDT, amount: '5.0', walletAddress: '0xabc...' },
  { chainId: 'arb', contractAddress: TOKENS.USDT, amount: '10.0', walletAddress: '0xdef...' },
];

const results = await batchTransfer(transfers);
console.log('批次轉帳結果:', results);
```

---

## Python 範例

### 基本轉帳

```python
import os
import requests

def transfer_tokens(chain_id, contract_address, amount, wallet_address):
    response = requests.post(
        'https://wallet.kryptogo.app/v1/studio/api/asset_pro/transfer',
        json={
            'chain_id': chain_id,
            'contract_address': contract_address,
            'amount': amount,
            'wallet_address': wallet_address,
        },
        headers={
            'X-STUDIO-API-KEY': os.getenv('KRYPTOGO_STUDIO_API_KEY'),
            'Content-Type': 'application/json',
        }
    )
    return response.json()

# 轉帳 USDT on Arbitrum
result = transfer_tokens(
    'arb',
    '0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9',  # USDT
    '10.0',
    '0x3fE5aC82B997255b8226abb6aEfd91f405fe2e8e'
)

if result.get('code') == 0:
    print('轉帳成功', {
        'id': result['data']['id'],
        'txHash': result['data']['tx_hash'],
        'time': result['data']['transfer_time'],
    })
elif result.get('code') == 4015:
    print('餘額不足', result['data']['insufficient_amount'])
```

### Flask 提領端點

```python
from flask import Flask, request, jsonify

app = Flask(__name__)

TOKENS = {
    'USDT': '0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9',
    'USDC': '0xff970a61a04b1ca14834a43f5de4533ebddb5cc8',
}

@app.route('/api/withdraw', methods=['POST'])
@auth_required
def withdraw():
    data = request.json
    token = data.get('token')
    amount = data.get('amount')
    wallet_address = data.get('wallet_address')

    if token not in TOKENS:
        return jsonify({'error': '不支援的代幣'}), 400

    if not wallet_address or not wallet_address.startswith('0x'):
        return jsonify({'error': '錢包地址格式不正確'}), 400

    result = transfer_tokens('arb', TOKENS[token], amount, wallet_address)

    if result.get('code') == 0:
        return jsonify({
            'success': True,
            'tx_hash': result['data']['tx_hash'],
        })
    elif result.get('code') == 4015:
        return jsonify({
            'error': '餘額不足',
            'insufficient_amount': result['data']['insufficient_amount'],
        }), 400
    else:
        return jsonify({'error': result.get('message', '轉帳失敗')}), 500
```

---

## cURL 範例

### 轉帳 USDT

```bash
curl -X POST \
  https://wallet.kryptogo.app/v1/studio/api/asset_pro/transfer \
  -H 'Content-Type: application/json' \
  -H 'X-STUDIO-API-KEY: YOUR_API_KEY' \
  -d '{
    "chain_id": "arb",
    "contract_address": "0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9",
    "amount": "10.0",
    "wallet_address": "0x3fE5aC82B997255b8226abb6aEfd91f405fe2e8e"
  }'
```

### 轉帳 USDC

```bash
curl -X POST \
  https://wallet.kryptogo.app/v1/studio/api/asset_pro/transfer \
  -H 'Content-Type: application/json' \
  -H 'X-STUDIO-API-KEY: YOUR_API_KEY' \
  -d '{
    "chain_id": "arb",
    "contract_address": "0xff970a61a04b1ca14834a43f5de4533ebddb5cc8",
    "amount": "10.0",
    "wallet_address": "0x3fE5aC82B997255b8226abb6aEfd91f405fe2e8e"
  }'
```
