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
 * Handling JSON-formatted files
 */
class JSONFile {

    /**
     * Parse a JSON file
     *
     * @param string $file File path
     * @param bool $force If "true" and file is not readable ignore it, otherwise throw an exception. Defaults to
     * "false".
     *
     * @return array Return content as an array
     *
     * @throws \Exception on error
     */
    public static function read($file, $force = false) {
        if (!is_readable($file)) {
            if ($force === true) {
                return [];
            } else {
                throw new \Exception(sprintf(
                    'Unable to read file "%s"',
                    $file
                ), ExitApp::RUNTIME_ERROR);
            }
        }

        $result = json_decode(
            trim(
                file_get_contents(
                    $file
                )
            ),
            true
        );

        if ($result === false) {
            throw new \Exception(sprintf(
                'File "%s" contains invalid JSON data.',
                ExitApp::RUNTIME_ERROR
            ));
        }

        return $result;
    }

    /**
     * Write JSON-formatted content to file
     *
     * @param string $file File path
     * @param array $content Content
     *
     * @throws \Exception on error
     */
    public static function write($file, array $content) {
        $jsonString = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($jsonString === false) {
            throw new \Exception('Unable to convert array to JSON string', ExitApp::RUNTIME_ERROR);
        }

        $status = file_put_contents(
            $file,
            $jsonString . PHP_EOL
        );

        if ($status === false) {
            throw new \Exception(sprintf(
                'Unable to write JSON-formatted content to file %s',
                $file
            ), ExitApp::RUNTIME_ERROR);
        }
    }

}
