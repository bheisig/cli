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

namespace bheisig\cli\Command;

use \Exception;
use \RuntimeException;
use bheisig\cli\ExitApp;

/**
 * Command "help"
 */
class Help extends Command {

    /**
     * Execute command
     *
     * @throws Exception on error
     */
    public function execute() {
        if (count($this->config['arguments']) === 1) {
            if (array_key_exists($this->config['arguments'][0], $this->config['commands'])) {
                // APP help COMMAND:
                $class = $this->config['commands'][$this->config['arguments'][0]]['class'];

                if (!class_exists($class) ||
                    !is_subclass_of($class, __NAMESPACE__ . '\\Executes')
                ) {
                    throw new RuntimeException(sprintf(
                        'Command "%s" not found',
                        $this->config['arguments'][0]
                    ), ExitApp::BAD_USER_INTERACTION);
                }

                /** @var Executes $command */
                $command = new $class($this->config, $this->log);
                $command->printUsage();
            } else {
                // APP help unknown:
                throw new RuntimeException(sprintf(
                    'Command "%s" not found',
                    $this->config['arguments'][0]
                ), ExitApp::BAD_USER_INTERACTION);
            }
        } else {
            // APP help:
            $this->printUsage();
        }
    }

    /**
     * Print usage of command
     */
    public function printUsage() {
        $commandList = [];

        foreach ($this->config['commands'] as $command => $commandOptions) {
            $separator = 20 - strlen($command);

            $commandList[] = '    ' . $command . str_pad(' ', $separator) .
                '<dim>' . $commandOptions['description'] . '</dim>';
        }

        sort($commandList);

        $this->log->info(
            <<< EOF
%1\$s: %3\$s

<strong>VERSION</strong>
    %4\$s

<strong>USAGE</strong>
    \$ %1\$s [COMMAND] [OPTIONS]

<strong>COMMANDS</strong>
%2\$s

    <dim># Print usage of a command:</dim>
    \$ %1\$s help COMMAND
    <dim># or:</dim>
    \$ %1\$s COMMAND --help
    <dim># List all commands:</dim>
    \$ %1\$s list

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

<strong>FIRST STEPS</strong>
    <dim># %5\$s:</dim>
    %1\$s init
    <dim># %6\$s:</dim>
    %1\$s configtest
EOF
            ,
            $this->config['composer']['extra']['name'],
            implode(PHP_EOL, $commandList),
            $this->config['composer']['description'],
            $this->config['composer']['extra']['version'],
            $this->config['commands']['init']['description'],
            $this->config['commands']['configtest']['description']
        );
    }

}
