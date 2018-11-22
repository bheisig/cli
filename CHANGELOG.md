#   Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).


##  [Unreleased]


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


### Fixed

-   Let user overwrite a configuration setting if it is an indexed array


##  0.1 – 2018-04-24

Initial release


[Unreleased]: https://github.com/bheisig/cli/compare/0.1...HEAD
