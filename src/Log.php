<?php

/**
 * Copyright (C) 2018 Benjamin Heisig
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
 * @copyright Copyright (C) 2018 Benjamin Heisig
 * @license http://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License (AGPL)
 * @link https://github.com/bheisig/cli
 */

namespace bheisig\cli;

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
     * Log Levels
     *
     * @var array
     */
    static protected $levels = [
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
    protected $output = 'STDOUT';

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
        self::DEBUG => '0;37'
    ];

    /**
     * Constructor
     *
     * @param int $verbosity Verbosity; defaults to everything except debug messages
     * @param bool $colorize Colorize output? Defaults to true
     */
    public function __construct($verbosity = self::ALL & ~self::DEBUG, $colorize = true) {
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
    public function setVerbosity($verbosity) {
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
    public function printColors($colorize) {
        $this->colorize = $colorize;
        return $this;
    }

    /**
     * Print the following events as output to STDOUT
     *
     * @return self Returns itself
     */
    public function printAsOutput() {
        $this->output = 'STDOUT';
        return $this;
    }

    /**
     * Print the following events as messages to STDERR
     *
     * @return self Returns itself
     */
    public function printAsMessage() {
        $this->output = 'STDERR';
        return $this;
    }

    /**
     * Logs event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param int $level Event level. One of the following class constants: DEBUG, INFO, WARNING, ERROR or FATAL.
     * @param string $value What to be formatted
     * @param mixed $args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    protected function event($level, $value, $args = null) {
        if ($level & $this->verbosity) {
            $argList = func_get_args();

            $message = $value;

            if (count($argList) >= 3) {
                array_shift($argList);
                $message = call_user_func_array('sprintf', $argList);
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

            switch ($this->output) {
                case 'STDOUT':
                    IO::out($message);
                    break;
                case 'STDERR':
                    IO::err($message);
                    break;
            }
        }

        return $this;
    }

    /**
     * Logs fatal event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param mixed $args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function fatal($value, $args = null) {
        $argList = array_merge([self::FATAL], func_get_args());
        return call_user_func_array([__CLASS__, 'event'], $argList);
    }

    /**
     * Logs error event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param mixed $args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function error($value, $args = null) {
        $argList = array_merge([self::ERROR], func_get_args());
        return call_user_func_array([__CLASS__, 'event'], $argList);
    }

    /**
     * Logs warning event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param mixed $args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function warning($value, $args = null) {
        $argList = array_merge([self::WARNING], func_get_args());
        return call_user_func_array([__CLASS__, 'event'], $argList);
    }

    /**
     * Logs warning event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param mixed $args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function notice($value, $args = null) {
        $argList = array_merge([self::NOTICE], func_get_args());
        return call_user_func_array([__CLASS__, 'event'], $argList);
    }

    /**
     * Logs info event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param mixed $args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function info($value, $args = null) {
        $argList = array_merge([self::INFO], func_get_args());
        return call_user_func_array([__CLASS__, 'event'], $argList);
    }

    /**
     * Logs debug event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param mixed $args (Optional) One or more arguments
     *
     * @return self Returns itself
     *
     * @see sprintf()
     */
    public function debug($value, $args = null) {
        $argList = array_merge([self::DEBUG], func_get_args());
        return call_user_func_array([__CLASS__, 'event'], $argList);
    }

    /**
     * Print empty line
     *
     * @return self Returns itself
     */
    public function printEmptyLine() {
        IO::out('');
        return $this;
    }

    /**
     * Format text
     *
     * @param string $text Unformatted text
     * @return string Formatted text
     * @throws \RuntimeException on error
     */
    protected function formatText($text) {
        $syntax = [
            '/\<strong\>(.*)\<\/strong\>/' => "\033[1m$1\033[0m",
            '/\<u\>(.*)\<\/u\>/' => "\033[4m$1\033[0m",
            '/\<dim\>(.*)\<\/dim\>/' => "\033[2m$1\033[0m",
            '/\<fatal\>(.*)\<\/fatal\>/' => "\033[" . $this->colors[self::FATAL] . "m$1\033[0m",
            '/\<error\>(.*)\<\/error\>/' => "\033[" . $this->colors[self::ERROR] . "m$1\033[0m",
            '/\<warning\>(.*)\<\/warning\>/' => "\033[" . $this->colors[self::WARNING] . "m$1\033[0m",
            '/\<notice\>(.*)\<\/notice\>/' => "\033[" . $this->colors[self::NOTICE] . "m$1\033[0m",
            '/\<debug\>(.*)\<\/debug\>/' => "\033[" . $this->colors[self::DEBUG] . "m$1\033[0m",
            '/\<red\>(.*)\<\/red\>/' => "\033[31m$1\033[0m",
            '/\<yellow\>(.*)\<\/yellow\>/' => "\033[33m$1\033[0m",
            '/\<green\>(.*)\<\/green\>/' => "\033[32m$1\033[0m",
            '/\<grey\>(.*)\<\/grey\>/' => "\033[37m$1\033[0m",
        ];

        $replacements = '$1';

        if ($this->colorize) {
            $replacements = $syntax;
        }

        $result = preg_replace(
            array_keys($syntax),
            $replacements,
            $text
        );

        if (!is_string($result)) {
            throw new \RuntimeException('Unable to format text');
        }

        return $result;
    }

}
