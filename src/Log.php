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

    static protected $levels = [
        self::FATAL => 'fatal',
        self::ERROR => 'error',
        self::WARNING => 'warning',
        self::NOTICE => 'notice',
        self::INFO => 'info',
        self::DEBUG => 'debug'
    ];

    protected $verbosity;

    protected $colorize = true;

    protected $colors = [
        self::FATAL => '0;31',
        self::ERROR => '0;31',
        self::WARNING => '1;33',
        self::NOTICE => '1;33',
        self::INFO => '0;32',
        self::DEBUG => '0;37'
    ];

    public function __construct($verbosity = self::FATAL & ~self::DEBUG, $colorize = true) {
        $this->setVerbosity($verbosity);
        $this->printColors($colorize);
    }

    public function setVerbosity($verbosity) {
        $this->verbosity = $verbosity;
    }

    public function printColors($colorize) {
        $this->colorize = $colorize;
    }

    /**
     * Logs event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param int $level Event level. One of the following class constants: DEBUG, INFO, WARNING, ERROR or FATAL.
     * @param string $value What to be formatted
     * @param string $args (Optional) One or more strings
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
                $message = "\e[" . $this->colors[$level] . 'm' . $message . "\033[0m";
            }

            IO::out($message);
        }
    }

    /**
     * Logs fatal event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param string $args (Optional) One or more strings
     *
     * @see sprintf()
     */
    public function fatal($value, $args = null) {
        $argList = array_merge([self::FATAL], func_get_args());
        call_user_func_array([__CLASS__, 'event'], $argList);
    }

    /**
     * Logs error event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param string $args (Optional) One or more strings
     *
     * @see sprintf()
     */
    public function error($value, $args = null) {
        $argList = array_merge([self::ERROR], func_get_args());
        call_user_func_array([__CLASS__, 'event'], $argList);
    }

    /**
     * Logs warning event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param string $args (Optional) One or more strings
     *
     * @see sprintf()
     */
    public function warning($value, $args = null) {
        $argList = array_merge([self::WARNING], func_get_args());
        call_user_func_array([__CLASS__, 'event'], $argList);
    }

    /**
     * Logs warning event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param string $args (Optional) One or more strings
     *
     * @see sprintf()
     */
    public function notice($value, $args = null) {
        $argList = array_merge([self::NOTICE], func_get_args());
        call_user_func_array([__CLASS__, 'event'], $argList);
    }

    /**
     * Logs info event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param string $args (Optional) One or more strings
     *
     * @see sprintf()
     */
    public function info($value, $args = null) {
        $argList = array_merge([self::INFO], func_get_args());
        call_user_func_array([__CLASS__, 'event'], $argList);
    }

    /**
     * Logs debug event. It provides the same functionality as sprintf() by passing two or more arguments.
     *
     * @param string $value What to be formatted
     * @param string $args (Optional) One or more strings
     *
     * @see sprintf()
     */
    public function debug($value, $args = null) {
        $argList = array_merge([self::DEBUG], func_get_args());
        call_user_func_array([__CLASS__, 'event'], $argList);
    }

}