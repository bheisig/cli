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
 * Command "help"
 */
class Help extends Command {

    /**
     * Execute command
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    public function execute() {
        if (count($this->config['args']) > 2 &&
            array_key_exists($this->config['args'][2], $this->config['commands'])) {
            $command = $this->config['args'][2];
        }

        if (!array_key_exists('command', $this->config)) {
            $this->printUsage();
        } else if ($this->config['command'] === 'help' &&
            count($this->config['args']) === 2) {
            $this->printUsage();
        } else if ($this->config['command'] === 'help' &&
            isset($command)) {
            $class = $this->config['commands'][$command]['class'];

            /** @var Executes $instance */
            $instance = new $class($this->config, $this->log);

            $instance->printUsage();
        } else {
            $this->log->fatal('Unknown command');

            $this->printUsage();
        }

        return $this;
    }

    /**
     * Print usage of command
     *
     * @return self Returns itself
     */
    public function printUsage() {
        $commandList = '';

        foreach ($this->config['commands'] as $command => $commandOptions) {
            if ($command === 'help' || $command === 'list') {
                continue;
            }

            $separator = 24 - strlen($command);

            $commandList .= PHP_EOL . '    ' . $command . str_pad(' ', $separator) . $commandOptions['description'];
        }

        $this->log->info('%3$s
        
Usage: %1$s [COMMAND] [OPTIONS]

Commands:
%2$s

For more information about a specific command use

    %1$s help COMMAND

or

    %1$s COMMAND --help

List all commands with

    %1$s list

Common options:

    -c FILE,                Include settings stored in a JSON-formatted
    --config FILE           configuration file FILE; you may use this
                            option multiple times
    -h, --help              Print this help or information about a
                            specific command
    --no-colors             Do not print colored messages
    -q, --quiet             Do not output messages, only errors
    -v, --verbose           Be more verbose
    --version               Print version information',
            $this->config['args'][0],
            $commandList,
            $this->config['composer']['description']);

        return $this;
    }

}
