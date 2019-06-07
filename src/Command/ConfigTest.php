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
use bheisig\cli\Config;
use bheisig\cli\JSONFile;

/**
 * Command "configtest"
 */
class ConfigTest extends Command {

    /**
     * Execute command
     *
     * @throws Exception on error
     */
    public function execute() {
        $this->log
            ->printAsMessage()
            ->info($this->getDescription())
            ->printEmptyLine();

        $file = $this->config['appDir'] . '/config/schema.json';

        $rules = JSONFile::read($file);

        $config = new Config();
        $errors = $config->validate($this->config, $rules);

        if (count($errors) === 0) {
            $this->log
                ->info('<green>✔</green> Configuration settings are OK.');
        } else {
            $this->log->warning('One or more errors found in configuration settings:');

            foreach ($errors as $error) {
                $this->log->warning('✘ %s', $error);
            }
        }
    }

}
