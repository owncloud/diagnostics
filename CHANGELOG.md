# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [0.2.0] - 2023-07-10

### Changed

- (98)[https://github.com/owncloud/diagnostics/pull/98] - Always return an int from Symfony Command execute method
- (72)[https://github.com/owncloud/diagnostics/pull/72] - Conditionally use locks for the diagnostic.log file
- Minimum core version 10.11, minimum php version 7.4
- Dependencies updated

### Fixed
- (85)[https://github.com/owncloud/diagnostics/pull/85] - Only inc if countable (Parameter must be an array or an object that implements Countable)

## [0.1.3] - 2018-12-11

### Changed

- Set max version to 10 because core platform is switching to Semver
- Change debug message in settings page - [#43](https://github.com/owncloud/diagnostics/issues/43)

## [0.1.2] - 2017-09-14

 - Fix deactivation of diagnostics app when occ is executed

## 0.1.1 - 2017-09-14

 - Initial version

[Unreleased]: https://github.com/owncloud/diagnostics/compare/v0.2.0...master
[0.2.0]: https://github.com/owncloud/diagnostics/compare/v0.1.3...v0.2.0
[0.1.3]: https://github.com/owncloud/diagnostics/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/owncloud/diagnostics/compare/v0.1.1...v0.1.2

