# php-grip Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [unreleased]

### Added
- Support for `verify_iss` and `verify_key` GRIP configurations and parsing them from GRIP_URLs.
- Support for Bearer tokens, using the new `Fanout\Grip\Auth\BearerAuth` class.
  - Use a Bearer token by using a GRIP configuration with `key`, but without a `control_iss`. This can also be parsed
    from `GRIP_URL` that have a `key` without an `iss`.
- Updated with full support for Fastly Fanout.

## [1.0.1] - 2021-08-30

### Changed
- Renamed `WebSocketDecodeEventException` to `WebSocketDecodeEventError`

### Fixed
- Allow setting `GripInstruct` metas using `set_meta`.

## [1.0.0] - 2021-08-07

- Major update with great improvements in usability
- Uses Guzzle (and its Promises library) for HTTP fetching and asynchronous functionality.
- Collapsed `php-pubcontrol` and `php-gripcontrol` into a single library,
  simplifying use and deployment.
- Reorganized utility functions into categorized files.
- Install using Composer.  Classes loaded using PSR-4.

[unreleased]: https://github.com/fanout/php-grip/v1.0.1...HEAD
[1.0.1]: https://github.com/fanout/php-grip/compare/1.0...v1.0.1
[1.0]: https://github.com/fanout/php-grip/releases/tag/1.0
