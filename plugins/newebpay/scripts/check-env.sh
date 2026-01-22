#!/bin/bash

# NewebPay Environment Check Script
# This script runs during plugin setup to verify required environment variables

echo "🔍 檢查 NewebPay 環境變數..."
echo ""

MISSING=0

if [ -z "$NEWEBPAY_MERCHANT_ID" ]; then
  echo "⚠️  NEWEBPAY_MERCHANT_ID 未設定"
  MISSING=1
else
  echo "✅ NEWEBPAY_MERCHANT_ID 已設定"
fi

if [ -z "$NEWEBPAY_HASH_KEY" ]; then
  echo "⚠️  NEWEBPAY_HASH_KEY 未設定"
  MISSING=1
else
  echo "✅ NEWEBPAY_HASH_KEY 已設定"
fi

if [ -z "$NEWEBPAY_HASH_IV" ]; then
  echo "⚠️  NEWEBPAY_HASH_IV 未設定"
  MISSING=1
else
  echo "✅ NEWEBPAY_HASH_IV 已設定"
fi

echo ""

if [ $MISSING -eq 1 ]; then
  echo "📝 請在 .env 檔案中設定以下環境變數："
  echo ""
  echo "   NEWEBPAY_MERCHANT_ID=你的商店代號"
  echo "   NEWEBPAY_HASH_KEY=你的HashKey"
  echo "   NEWEBPAY_HASH_IV=你的HashIV"
  echo "   NEWEBPAY_ENV=test  # 或 production"
  echo ""
  echo "📖 測試環境申請: https://cwww.newebpay.com/"
else
  echo "✅ 所有必要環境變數已設定完成"
fi

echo ""
echo "環境檢查完成"
