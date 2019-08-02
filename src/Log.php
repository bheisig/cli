<?php

/**
 * Copyright (C) 2018-19 Benjamin Heisig
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Benjamin Heisig <https://benjamin.heisig.name/>
 * @copyright Copyright (C) 2018-19 Benjamin Heisig
 * @license http://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License (AGPL)
 * @link https://github.com/bheisig/cli
 */

declare(strict_types=1);

namespace bheisig\cli;

use \RuntimeException;

/**
 * Logger
 */
class Log {

    /**
     * Log level: fatal error
     *
     * @var int
     */
    const FATAL = 1;

    /**
     * Log level: error
     *
     * @var int
     */
    const ERROR = 2;

    /**
     * Log level: warning
     *
     * @var int
     */
    const WARNING = 4;

    /**
     * Log level: notice
     *
     * @var int
     */
    const NOTICE = 8;

    /**
     * Log level: info
     *
     * @var int
     */
    const INFO = 16;

    /**
     * Log level: debug
     *
     * @var int
     */
    const DEBUG = 32;

    /**
     * Log level: everything
     *
     * @var int
     */
    const ALL = 63;

    /**
     * Print as output
     *
     * @var string
     */
    const PRINT_AS_OUTPUT = 'STDOUT';

    /**
     * Print as message
     *
     * @var string
     */
    const PRINT_AS_MESSAGE = 'STDERR';

    /**
     * Log Levels
     *
     * @var array
     */
    protected static $levels = [
        self::FATAL => 'fatal',
        self::ERROR => 'error',
        self::WARNING => 'warning',
        self::NOTICE => 'notice',
        self::INFO => 'info',
        self::DEBUG => 'debug'
    ];

    /**
     * Current verbosity
     *
     * @var int
     */
    protected $verbosity;

    /**
     * Colorize output?
     *
     * @var bool Defaults to true
     */
    protected $colorize = true;

    /**
     * Print to standard output or error?
     *
     * @var string Default to standard output
     */
    protected $output = self::PRINT_AS_OUTPUT;

    /**
     * ANSI color codes
     *
     * @var array
     */
    protected $colors = [
        self::FATAL => '0;31',
        self::ERROR => '0;31',
        self::WARNING => '1;33',
        self::NOTICE => '1;33',
        self::DEBUG => '2'
    ];

    /**
     * Constructor
     *
     * @param int $verbosity Verbosity; defaults to everything except debug messages
     * @param bool $colorize Colorize output? Defaults to true
     */
    public function __construct(int $verbosity = self::ALL & ~self::DEBUG, bool $colorize = true) {
        $this->setVerbosity($verbosity);
        $this->printColors($colorize);
    }

    /**
     * Set verbosity
     *
     * @param int $verbosity Verbosity
     *
     * @return self Returns itself
     */
    public function setVerbosity(int $verbosity): self {
        $this->verbosity = $verbosity;
        return $this;
    }

    /**
     * Colorize output?
     *
     * @param bool $colorize Decision
     *
     * @return self Returns itself
     */
    public function printColors(bool $colorize): self {
        $this->colorize = $colorize;
        return $this;
    }

    /**
     * Print the following events as output to STDOUT
     *
     * @return self Returns itself
     */
    public function printAsOutput(): self {
        $this->output = self::PRINT_AS_OUTPUT;
        return $this;
    }

    /**
     * Print the following events as messages to STDERR
     *
     * @return self Returns itself
     */
    public function printAsMessage(): self {
        $this->output = self::PRINT_AS_MESSAGE;
        return $this;
    }

    /**
     * Log event. It provides the same functionality as sprintf() by passing three or more arguments.
     *
     * @param int $level Event level. One of the following class constants:
     * DEBUG, INFO, NOTICE, WARNING, ERROR or FATAL.
     * @param string $value What to be formatted
     * @param mixed ...$args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function event(int $level, string $value, ...$args): self {
        if ($level & $this->verbosity) {
            $message = $value;

            if (count($args) > 0) {
                $message = call_user_func_array(
                    'sprintf',
                    array_merge([$value], $args)
                );
            }

            if ($this->colorize) {
                switch ($level) {
                    case self::FATAL:
                        $message = "<fatal>$message</fatal>";
                        break;
                    case self::ERROR:
                        $message = "<error>$message</error>";
                        break;
                    case self::WARNING:
                        $message = "<warning>$message</warning>";
                        break;
                    case self::NOTICE:
                        $message = "<notice>$message</notice>";
                        break;
                    case self::DEBUG:
                        $message = "<debug>$message</debug>";
                        break;
                }
            }

            $message = $this->formatText($message);

            $this->flush($message);
        }

        return $this;
    }

    /**
     * Log fatal event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param mixed ...$args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function fatal(string $value, ...$args): self {
        return call_user_func_array(
            [__CLASS__, 'event'],
            array_merge([self::FATAL, $value], $args)
        );
    }

    /**
     * Log error event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param mixed ...$args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function error(string $value, ...$args): self {
        return call_user_func_array(
            [__CLASS__, 'event'],
            array_merge([self::ERROR, $value], $args)
        );
    }

    /**
     * Log warning event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param mixed ...$args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function warning(string $value, ...$args): self {
        return call_user_func_array(
            [__CLASS__, 'event'],
            array_merge([self::WARNING, $value], $args)
        );
    }

    /**
     * Log warning event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param mixed ...$args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function notice(string $value, ...$args): self {
        return call_user_func_array(
            [__CLASS__, 'event'],
            array_merge([self::NOTICE, $value], $args)
        );
    }

    /**
     * Log info event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param mixed ...$args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function info(string $value, ...$args): self {
        return call_user_func_array(
            [__CLASS__, 'event'],
            array_merge([self::INFO, $value], $args)
        );
    }

    /**
     * Log debug event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param mixed ...$args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function debug(string $value, ...$args): self {
        return call_user_func_array(
            [__CLASS__, 'event'],
            array_merge([self::DEBUG, $value], $args)
        );
    }

    /**
     * Print empty line
     *
     * @return self Returns itself
     */
    public function printEmptyLine(): self {
        $this->flush('');

        return $this;
    }

    /**
     * Format text
     *
     * @param string $text Unformatted text
     * @return string Formatted text
     * @throws RuntimeException on error
     */
    protected function formatText(string $text): string {
        $syntax = [
            '/\<strong\>/m' => "\033[1m",
            '/\<u\>/m' => "\033[4m",
            '/\<dim\>/m' => "\033[2m",
            '/\<fatal\>/m' => "\033[" . $this->colors[self::FATAL] . "m",
            '/\<error\>/m' => "\033[" . $this->colors[self::ERROR] . "m",
            '/\<warning\>/m' => "\033[" . $this->colors[self::WARNING] . "m",
            '/\<notice\>/m' => "\033[" . $this->colors[self::NOTICE] . "m",
            '/\<debug\>/m' => "\033[" . $this->colors[self::DEBUG] . "m",
            '/\<red\>/m' => "\033[31m",
            '/\<yellow\>/m' => "\033[33m",
            '/\<green\>/m' => "\033[32m",
            '/\<grey\>/m' => "\033[37m",
            '/\<\/([a-z]+)\>/m' => "\033[0m",
        ];

        $replacements = '';

        if ($this->colorize) {
            $replacements = $syntax;
        }

        $result = preg_replace(
            array_keys($syntax),
            $replacements,
            $text
        );

        if (!is_string($result)) {
            throw new RuntimeException('Unable to format text', ExitApp::RUNTIME_ERROR);
        }

        return $result;
    }

    /**
     * Print message to STDOUT or STDERR
     *
     * @param string $message Message
     */
    protected function flush(string $message) {
        switch ($this->output) {
            case self::PRINT_AS_OUTPUT:
                IO::out($message);
                break;
            case self::PRINT_AS_MESSAGE:
                IO::err($message);
                break;
        }
    }

}
