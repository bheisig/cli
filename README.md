#   CLI

PHP Framework for command line interfaces

[![Latest Stable Version](https://img.shields.io/packagist/v/bheisig/cli.svg)](https://packagist.org/packages/bheisig/cli)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![Build Status](https://travis-ci.org/bheisig/cli.svg?branch=master)](https://travis-ci.org/bheisig/cli)


##  About

You want an easy way to build a command line tool written in PHP? You are bored of big, over-engineered frameworks? And last but not least, you do not want to reinvent the wheel? -- Great, this could be the (almost) perfect solution for your next project.

The goal is to build intuitive CLI tools. Nobody likes to read documentation, so make your application self-descriptive!


##  Features

*   Easy-to-install via [Composer](https://getcomposer.org/)
*   Perfectly suited to create a single binary file from your application
*   Simple interface
*   Separate your features by commands
*   Error/exception handling
*   Optional colored output based on log level
*   Log to standard or error output
*   Pre-defined usage output
*   JSON-based configuration with defaults, system-wide, user defined and runtime settings
*   Support for long and short options with optional or required values
*   Pre-defined options for verbosity/quietness, usage, version information, additional configuration files, runtime settings, configuration test
*   Verbosity mode outputs human-readable runtime in seconds and peak memory usage


##  Example

For a simple application look at the [`example`](example/) folder.


##  Requirements

*   PHP, version 5.6 or higher (7.1 is recommended)
*   PHP modules `cli` and `json`


##  Installation

Go to your project folder and require this framework via Composer:

~~~ {.bash}
composer require "bheisig/cli=@DEV"
~~~


##  Update

Composer is the way to go:

~~~ {.bash}
composer update
~~~


##  Copyright & License

Copyright (C) 2018 [Benjamin Heisig](https://benjamin.heisig.name/)

Licensed under the [GNU Affero GPL version 3 or later (AGPLv3+)](https://gnu.org/licenses/agpl.html). This is free software: you are free to change and redistribute it. There is NO WARRANTY, to the extent permitted by law.
