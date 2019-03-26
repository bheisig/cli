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

namespace bheisig\cli\Command;

use bheisig\cli\IO;
use bheisig\cli\Log;

/**
 * Base command
 */
abstract class Command implements Executes {

    /**
     * Configuration settings as key-value store
     *
     * @var array Associative array
     */
    protected $config = [];

    /**
     * Logger
     *
     * @var \bheisig\cli\Log
     */
    protected $log;

    /**
     * UNIX timestamp when execution starts
     *
     * @var int
     */
    protected $start = 0;

    /**
     * Constructor
     *
     * @param array $config Configuration settings
     * @param Log $log Logger
     */
    public function __construct(array $config, Log $log) {
        $this->config = $config;
        $this->log = $log;
    }

    /**
     * Process some routines before executing command
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    public function setup() {
        $this->start = time();

        return $this;
    }

    /**
     * Process some routines after executing command
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    public function tearDown() {
        $seconds = time() - $this->start;

        switch ($seconds) {
            case 1:
                $this->log->debug('This took 1 second.');
                break;
            default:
                $this->log->debug('This took %s seconds.', $seconds);
                break;
        }

        $prettifyUnit = function ($bytes) {
            $unit = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];

            if ($bytes === 0) {
                return '0 ' . $unit[0];
            }

            return @round(
                $bytes / pow(
                    1024,
                    ($i = (int) floor(log($bytes, 1024)))
                ),
                2
            ) . ' ' . (isset($unit[$i]) ? $unit[$i] : 'B');
        };

        $this->log->debug(
            'Memory peak usage: %s',
            $prettifyUnit(memory_get_peak_usage(true))
        );

        if (time() >= mktime(0, 0, 0, 12, 24) &&
            time() <= mktime(23, 59, 59, 12, 26)) {
            $this->log->debug('Merry christmas!');
        } elseif (time() >= mktime(0, 0, 0, 12, 31) &&
            time() <= mktime(23, 59, 59, 1, 1)) {
            $this->log->debug('Happy new year!');
        } elseif (time() >= mktime(0, 0, 0, (int) date('n', easter_date()), (int) date('j', easter_date()) - 2) &&
            time() <= mktime(23, 59, 59, (int) date('n', easter_date()), (int) date('j', easter_date()) + 1)) {
            $this->log->debug('Happy easter!');
        } else {
            $this->log->debug('Have fun :)');
        }

        return $this;
    }

    /**
     * Looks for a query from given arguments
     *
     * @return string
     *
     * @deprecated Use $this->config['arguments'][0] instead!
     */
    protected function getQuery() {
        $query = '';

        foreach ($this->config['args'] as $index => $arg) {
            if (array_key_exists('command', $this->config) &&
                $arg === $this->config['command'] &&
                array_key_exists(($index + 1), $this->config['args'])) {
                $query = $this->config['args'][$index + 1];
                break;
            }
        }

        return $query;
    }

    /**
     * Print usage of command
     *
     * @return self Returns itself
     */
    public function printUsage() {
        $this->log->info(
            <<< EOF
%3\$s

<strong>USAGE</strong>
    \$ %1\$s %2\$s [OPTIONS]

<strong>COMMON OPTIONS</strong>
    -c <u>FILE</u>,            <dim>Include settings stored in a JSON-formatted</dim>
    --config=<u>FILE</u>       <dim>configuration file FILE; repeat option for more</dim>
                        <dim>than one FILE</dim>
    -s <u>KEY=VALUE</u>,       <dim>Add runtime setting KEY with its VALUE; separate</dim>
    --setting=<u>KEY=VALUE</u> <dim>nested keys with ".", for example "key1.key2=123";</dim>
                        <dim>repeat option for more than one KEY</dim>

    --no-colors         <dim>Do not print colored messages</dim>
    -q, --quiet         <dim>Do not output messages, only errors</dim>
    -v, --verbose       <dim>Be more verbose</dim>

    -h, --help          <dim>Print this help or information about a</dim>
                        <dim>specific command</dim>
    --version           <dim>Print version information</dim>
EOF
            ,
            $this->config['composer']['extra']['name'],
            $this->getName(),
            $this->getDescription()
        );

        return $this;
    }

    /**
     * Get command name
     *
     * @return string
     */
    public function getName() {
        foreach ($this->config['commands'] as $command => $details) {
            if (strpos($details['class'], get_class($this)) !== false) {
                return $command;
            }
        }

        return '';
    }

    /**
     * Get command description
     *
     * @return string
     */
    public function getDescription() {
        foreach ($this->config['commands'] as $command => $details) {
            if (strpos($details['class'], get_class($this)) !== false) {
                return $details['description'];
            }
        }

        return '';
    }

    protected function askForPermission($question) {
        $answer = strtolower(
            IO::in($question  . ' [Y|n]:')
        );

        switch ($answer) {
            case 'yes':
            case 'y':
            case '':
                return true;
            case 'no':
            case 'n':
                return false;
            default:
                $this->log->warning('Excuse me, what do you mean?');
                return $this->askForPermission($question);
        }
    }

}
