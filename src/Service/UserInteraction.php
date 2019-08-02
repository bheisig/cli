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
use bheisig\cli\IO;

/**
 * User interaction
 */
class UserInteraction extends Service {

    /**
     * May we interact with the user?
     *
     * @return bool
     */
    public function isInteractive(): bool {
        if (array_key_exists('yes', $this->config['options']) ||
            array_key_exists('y', $this->config['options'])) {
            return false;
        }

        if (function_exists('posix_isatty') && posix_isatty(STDOUT) === false) {
            return false;
        }

        return true;
    }

    /**
     * Ask a question
     *
     * @param string $question Question
     *
     * @return string Answer
     *
     * @throws Exception on error
     */
    public function askQuestion(string $question): string {
        return IO::in($question);
    }

    /**
     * Ask user for permission
     *
     * Defaults to yes
     *
     * @param string $question Question to ask
     *
     * @return bool True if user answers with yes, otherwise false
     *
     * @throws Exception on error
     */
    public function askYesNo($question): bool {
        $answer = strtolower(
            IO::in($question  . ' [Y|n]:')
        );

        switch ($answer) {
            case 'yes':
            case 'y':
            case 'true':
            case '1':
            case '':
                return true;
            case 'no':
            case 'n':
            case 'false':
            case '0':
                return false;
            default:
                $this->log->warning('Excuse me, what do you mean?');
                return $this->askYesNo($question);
        }
    }

    /**
     * Ask user for permission
     *
     * Defaults to no
     *
     * @param string $question Question to ask
     *
     * @return bool True if user answers with no, otherwise false
     *
     * @throws Exception on error
     */
    public function askNoYes($question): bool {
        $answer = strtolower(
            IO::in($question  . ' [y|N]:')
        );

        switch ($answer) {
            case 'yes':
            case 'y':
            case 'true':
            case '1':
                return false;
            case 'no':
            case 'n':
            case 'false':
            case '0':
            case '':
                return true;
            default:
                $this->log->warning('Excuse me, what do you mean?');
                return $this->askNoYes($question);
        }
    }

    /**
     * @retun string
     * @throws Exception on error
     */
    public function readPipedInput(): string {
        $input = '';

        while (true) {
            $line = IO::in('');

            if ($line === '') {
                break;
            }

            $input .= $line;
        }

        return $input;
    }

}
