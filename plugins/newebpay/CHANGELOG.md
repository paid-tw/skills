# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.3.0] - 2026-11-06

### Fixed
- **Node.js example `decrypt()` failed on ~half of real production notifies**: NewebPay pads
  TradeInfo to 32-byte blocks (official PHP sample's `addpadding()` uses `$block = 32`), so the
  pad byte can be 0x11–0x20. Node's default PKCS7 autoPadding (pad ≤ 0x10) throws `bad decrypt`
  on those. Now disables autoPadding and strips/validates pad 1–32 manually. (Verified against
  real production notifies; self-encrypted test data never triggers this.)
- Padding-strip ranges in PHP/Node examples missed `\x20` (the pad == 32 case)
- `handleNotify` examples (PHP & Node) decrypted without verifying TradeSha — forged notify
  would be accepted; both now verify the signature before decrypting

### Added
- Troubleshooting: Node.js 32-byte padding pitfall with working decrypt snippet
- Troubleshooting Q16: NewebPay's notify retry behavior (a few retries within seconds, then a
  failure email, then **no more redelivery**) and the recovery ladder (reconcile on ReturnURL
  via QueryTradeInfo, scheduled reconcile, keep ALL issued MerchantOrderNos, log the failure
  scene)
- Troubleshooting Q9: canonical-domain note (payment gateways don't follow 307/308 redirects)
- Overview SKILL.md: notes on 32-byte padding and notify no-redelivery behavior

## [2.2.0] - 2026-01-22

### Added
- Re-added Setup hook for environment variable checking (confirmed valid per official docs)
- Agent "Context and examples" section per official structure

### Changed
- Renamed Agent "Expertise" section to "Capabilities" for consistency

## [2.1.0] - 2026-01-22

### Fixed
- Changed `skills` and `agents` fields from array to path string format per official spec
- Temporarily removed Setup hook (validator issue, now resolved)

## [2.0.0] - 2026-01-22

### Added
- Task-type skills with `context: fork` for isolated execution
- `disable-model-invocation: true` for user-only invocation
- Complete plugin.json metadata (author, homepage, repository)

### Changed
- Converted skills to task-type format with step-by-step instructions
- Updated Agent frontmatter to official format (description + capabilities)
- Improved skill descriptions for better discoverability

## [1.0.0] - 2026-01-20

### Added
- Initial release
- `/newebpay` - Environment setup and overview
- `/newebpay-checkout` - MPG payment integration
- `/newebpay-query` - Transaction query
- `/newebpay-refund` - Refund processing
- Payment Integrator Agent
- Reference documentation for API, error codes, and troubleshooting
