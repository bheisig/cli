# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

-   `HashMap`: Add methods to manipulate arrays
-   `Service\Service`: Add base service with access to configuration and logging
-   `Service\UserInteraction`: Add service to handle user interaction
-   `Service\Shell`: Add service to execute shell commands
-   `Service\YAML`: Add service to decode/encode YAML

### Changed

-   Drop PHP 5.6 support; minimum required version is 7.0
-   Declare strict types
-   `Command\Executes::setup()`: Switch return statement from self to void
-   `Command\Executes::execute()`: Switch return statement from self to void
-   `Command\Executes::tearDown()`: Switch return statement from self to void

### Fixed

-   `Log`: Remove unwanted format tags from non-colored output
-   `App::invokeLogging()`: Fix default log level from invalid value `-1` to `31` (everything but debug)
-   `App::parseArguments()`: Do not miss arguments in combination with options
-   `Command\Command::tearDown()`: Print last debug message (spent time, consumed memory etc.) to STDERR
-   `composer`: Clarify which PHP extensions are required and which ones are suggested

## [0.3] – 2019-05-08

### Added

-   Provide standardized exit codes to close application
-   Print stack trace in debug mode after something went wrong
-   Allow multi-line log events in combination with format tags
-   Allow repeating format tags
-   Allow format tags in other log levels than `info`

### Fixed

-   Check environment variables for `HOME`, `USER` and `LOCALAPPDATA` first before trying to load configuration files

## [0.2] – 2018-12-17

Happy holidays 🎄

### Added

-   Print version information with `APP -v` and `APP version`
-   Support on Windows operating systems
-   Add PHP extension `calendar` as requirement
-   Log: Do not colorize "info" events
-   Log: Print the following events as output to STDOUT with `printAsOutput()`
-   Log: Print the following events as messages to STDERR with `printAsMessage()`
-   Log: Print empty line with `printEmptyLine()`
-   Log: Allow method chaining
-   Log: Control text formatting with tags: `<strong>`, `<u>`, `<dim>`, `<fatal>`, `<error>`, `<warning>`, `<notice>`, `<debug>`, `<red>`, `<yellow>`, `<green>`, `<grey>`
-   Make arguments available in configuration settings
-   `IO::in()`: Print message/question to STDERR instead of STDOUT
-   Respect environment variables to disable colored output
-   Also, disable colors if there is no TTY available

### Fixed

-   Let user overwrite a configuration setting if it is an indexed array

## 0.1 – 2018-04-24

Initial release

[Unreleased]: https://github.com/bheisig/cli/compare/0.3...HEAD
[0.3]: https://github.com/bheisig/cli/compare/0.2...0.3
[0.2]: https://github.com/bheisig/cli/compare/0.1...0.2
