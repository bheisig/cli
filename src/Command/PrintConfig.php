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

namespace bheisig\cli\Command;

use bheisig\cli\IO;

/**
 * Command "print-config"
 */
class PrintConfig extends Command {

    /**
     * Execute command
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    public function execute() {
        $config = $this->config;

        // Strip internal settings:
        unset(
            $config['options'],
            $config['args'],
            $config['command'],
            $config['commands'],
            $config['appDir'],
            $config['composer']
        );

        IO::out(json_encode($config, JSON_PRETTY_PRINT));

        return $this;
    }

    /**
     * Print usage of command
     *
     * @return self Returns itself
     */
    public function printUsage() {
        $this->log->info('Usage: %1$s %2$s [OPTIONS]

%3$s

Common options:

    -c FILE,                Include settings stored in a JSON-formatted
    --config FILE           configuration file FILE; repeat option for more
                            than one FILE
    -s KEY=VALUE,           Add runtime setting KEY with its VALUE; separate
    --setting KEY=VALUE     nested keys with ".", for example "key1.key2=123";
                            repeat option for more than one KEY

    --no-colors             Do not print colored messages
    -q, --quiet             Do not output messages, only errors
    -v, --verbose           Be more verbose

    -h, --help              Print this help or information about a
                            specific command
    --version               Print version information',
            $this->config['args'][0],
            $this->getName(),
            $this->getDescription()
        );

        return $this;
    }

}
