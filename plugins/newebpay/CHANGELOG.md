# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
