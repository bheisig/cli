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

namespace bheisig\cli;

use \Exception;

/**
 * Input/output
 */
class IO {

    /**
     * Write line to STDOUT
     *
     * @param string $line Line
     * @param mixed ...$args (Optional) One or more arguments
     */
    public static function out($line, ...$args) {
        if (count($args) > 0) {
            $line = call_user_func_array(
                'sprintf',
                array_merge([$line], $args)
            );
        }

        fwrite(STDOUT, $line . PHP_EOL);
    }

    /**
     * Write message to STDERR
     *
     * @param string $message Message
     * @param mixed ...$args (Optional) One or more arguments
     */
    public static function err($message, ...$args) {
        if (count($args) > 0) {
            $message = call_user_func_array(
                'sprintf',
                array_merge([$message], $args)
            );
        }

        fwrite(STDERR, $message . PHP_EOL);
    }

    /**
     * Read from STDIN
     *
     * @param string $message Message
     * @param mixed ...$args (Optional) One or more arguments
     *
     * @return string User input
     *
     * @throws Exception on error
     */
    public static function in($message, ...$args) {
        if ($message !== '') {
            if (count($args) > 0) {
                $message = call_user_func_array(
                    'sprintf',
                    array_merge([$message], $args)
                );
            }

            $message .= ' ';
        }

        fwrite(STDERR, $message);

        $input = fgets(STDIN);

        if ($input === false) {
            throw new Exception('Unable to read from STDIN');
        }

        return trim($input);
    }

}
