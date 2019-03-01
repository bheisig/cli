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

/**
 * Command "list"
 */
class ListCommands extends Command {

    /**
     * Execute command
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    public function execute() {
        $this->log
            ->printAsMessage()
            ->info($this->getDescription())
            ->printEmptyLine()
            ->printAsOutput();

        ksort($this->config['commands']);

        $maxCommandLength = 0;

        foreach (array_keys($this->config['commands']) as $command) {
            if (strlen($command) > $maxCommandLength) {
                $maxCommandLength = strlen($command);
            }
        }

        $tab = 4;
        $minLength = $tab * (int) ($maxCommandLength / $tab) + $tab;

        foreach ($this->config['commands'] as $command => $details) {
            $this->log->info(
                str_pad($command, $minLength) . '<dim>' . $details['description'] . '</dim>'
            );
        }

        return $this;
    }

}
