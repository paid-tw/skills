#!/bin/bash

# PAYUNi 環境變數檢查腳本

check_env() {
    local var_name=$1
    local var_value="${!var_name}"

    if [ -z "$var_value" ]; then
        echo "WARNING: $var_name is not set"
        return 1
    else
        echo "OK: $var_name is configured"
        return 0
    fi
}

echo "=== PAYUNi Environment Check ==="

errors=0

check_env "PAYUNI_MERCHANT_ID" || ((errors++))
check_env "PAYUNI_HASH_KEY" || ((errors++))
check_env "PAYUNI_HASH_IV" || ((errors++))

echo "================================"

if [ $errors -gt 0 ]; then
    echo "Missing $errors environment variable(s)"
    echo "Please configure them in your .env file"
    exit 1
else
    echo "All PAYUNi environment variables are configured!"
    exit 0
fi
