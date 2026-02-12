#!/bin/bash

# KryptoGO Payment Environment Check Script
# This script runs during plugin setup to verify required environment variables

echo "🔍 檢查 KryptoGO Payment 環境變數..."
echo ""

MISSING=0

if [ -z "$KRYPTOGO_CLIENT_ID" ]; then
  echo "⚠️  KRYPTOGO_CLIENT_ID 未設定"
  MISSING=1
else
  echo "✅ KRYPTOGO_CLIENT_ID 已設定"
fi

if [ -z "$KRYPTOGO_STUDIO_API_KEY" ]; then
  echo "⚠️  KRYPTOGO_STUDIO_API_KEY 未設定"
  MISSING=1
else
  echo "✅ KRYPTOGO_STUDIO_API_KEY 已設定"
fi

if [ -z "$KRYPTOGO_ORIGIN" ]; then
  echo "⚠️  KRYPTOGO_ORIGIN 未設定"
  MISSING=1
else
  echo "✅ KRYPTOGO_ORIGIN 已設定"
fi

echo ""

if [ $MISSING -eq 1 ]; then
  echo "📝 請在 .env 檔案中設定以下環境變數："
  echo ""
  echo "   KRYPTOGO_CLIENT_ID=你的ClientID"
  echo "   KRYPTOGO_STUDIO_API_KEY=你的StudioAPIKey"
  echo "   KRYPTOGO_ORIGIN=你的網域"
  echo ""
  echo "📖 前往 KryptoGO Studio 取得金鑰: https://studio.kryptogo.com"
else
  echo "✅ 所有必要環境變數已設定完成"
fi

echo ""
echo "環境檢查完成"
