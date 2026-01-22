# Taiwan Payment Gateway Help

Lists all available Taiwan payment integration skills and provides recommendations.

## Usage

```
/payment-help
```

## Available Skills

### NewebPay (藍新金流)

| Skill | Description | Usage |
|-------|-------------|-------|
| `newebpay` | Overview and environment setup | `/newebpay` |
| `newebpay-checkout` | MPG checkout integration | `/newebpay-checkout [支付方式]` |
| `newebpay-query` | Transaction status query | `/newebpay-query` |
| `newebpay-refund` | Refund processing | `/newebpay-refund [類型]` |

**Supports**: 信用卡, LINE Pay, Apple Pay, Google Pay, ATM, 超商代碼/條碼

### ECPay (綠界科技)

| Skill | Description | Usage |
|-------|-------------|-------|
| `ecpay` | ECPay integration guide | `/ecpay` |

**Supports**: 信用卡, ATM, 超商, 電子發票

### PAYUNi (統一金流)

| Skill | Description | Usage |
|-------|-------------|-------|
| `payuni` | PAYUNi integration guide | `/payuni` |

**Supports**: 信用卡, ATM, 超商, 行動支付

## Gateway Comparison

| Feature | NewebPay | ECPay | PAYUNi |
|---------|----------|-------|--------|
| Market Position | 中大型電商 | 台灣最大 | 統一集團 |
| Credit Card | ✅ | ✅ | ✅ |
| LINE Pay | ✅ | ✅ | ✅ |
| Apple Pay | ✅ | ✅ | ✅ |
| E-Invoice | ❌ | ✅ | ❌ |
| CVS Pickup | ✅ | ✅ | ✅ |

## Recommendations

Choose based on your needs:

- **Need E-Invoice** → ECPay (built-in invoice functionality)
- **Large E-commerce/High Volume** → NewebPay (high stability)
- **Uni-President Group Related** → PAYUNi
- **Quick Launch** → Any of them, choose the docs you prefer

## Examples

```bash
# Get started with NewebPay
/payment-help

# Integrate NewebPay credit card payment
/newebpay-checkout 信用卡

# Query transaction status
/newebpay-query

# Process refund
/newebpay-refund 信用卡
```

## Related

- **Agent**: `payment-integrator` - Specialist for Taiwan payment integration
