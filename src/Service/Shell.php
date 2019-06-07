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

namespace bheisig\cli\Service;

use \Exception;

/**
 * Execute shell commands
 */
class Shell extends Service {

    const STDOUT = 1;

    const STDERR = 2;

    const OK = 0;

    /**
     * @var int Something between 0 and 255
     */
    protected $exitCode = 0;

    /**
     * @var array Indexed array of strings
     */
    protected $output = [];

    /**
     * @var array Indexed array of strings
     */
    protected $messages = [];

    /**
     * Execute shell command
     *
     * @param string $command Command
     * @return self Returns itself
     *
     * @throws Exception on error
     */
    public function execute(string $command): self {
        $descriptorspec = array(
            self::STDOUT => array('pipe', 'w'),
            self::STDERR => array('pipe', 'w')
        );

        $pipes = [];

        $process = proc_open($command, $descriptorspec, $pipes, $this->config['appDir']);

        if (!is_resource($process)) {
            throw new Exception('Unable to execute command');
        }

        $output = stream_get_contents($pipes[self::STDOUT]);

        if (!is_string($output)) {
            throw new Exception(sprintf('Broken STDOUT'));
        }

        $this->output = explode('\n', $output);

        if (fclose($pipes[self::STDOUT]) === false) {
            throw new Exception('Unable to close STDOUT');
        }

        $messages = stream_get_contents($pipes[self::STDERR]);

        if (!is_string($messages)) {
            throw new Exception(sprintf('Broken STDERR'));
        }

        $this->messages = explode('\n', $messages);

        if (fclose($pipes[self::STDERR]) === false) {
            throw new Exception('Unable to close STDERR');
        }

        $this->exitCode = proc_close($process);

        return $this;
    }

    /**
     * Get STDOUT from last shell execution
     *
     * @return array Indexed array of strings
     */
    public function getOutput(): array {
        return $this->output;
    }

    /**
     * Get STDERR from last shell execution
     *
     * @return array Indexed array of strings
     */
    public function getMessages(): array {
        return $this->messages;
    }

    /**
     * Get exit code from last shell execution
     *
     * @return int Something between 0 and 255
     */
    public function getExitCode(): int {
        return $this->exitCode;
    }

    /**
     * Was last shell execution successful?
     *
     * @return bool
     */
    public function hasSucceeded(): bool {
        return $this->exitCode === self::OK;
    }

    /**
     * Has last shell execution failed?
     *
     * @return bool
     */
    public function hasFailed(): bool {
        return $this->exitCode !== self::OK;
    }

}
