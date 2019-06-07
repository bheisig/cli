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
use bheisig\cli\ExitApp;

/**
 * Handle YAML
 */
class YAML extends Service {

    /**
     * Decode
     *
     * @param string $yaml YAML
     *
     * @return mixed Anything
     *
     * @throws Exception on error
     */
    public function decode(string $yaml) {
        $result = yaml_parse($yaml);

        if ($result === false) {
            throw new Exception(
                'Invalid YAML data',
                ExitApp::RUNTIME_ERROR
            );
        }

        return $result;
    }

    /**
     * Encode
     *
     * @param mixed $value Anything but a resource
     *
     * @return string YAML
     *
     * @throws Exception on error
     */
    public function encode($value): string {
        $result = yaml_emit(
            $value,
            YAML_UTF8_ENCODING,
            YAML_CRLN_BREAK
        );

        if (!is_string($result)) {
            throw new Exception(
                'Unable to convert value into a valid YAML string',
                ExitApp::RUNTIME_ERROR
            );
        }

        return $result;
    }

    /**
     * Parse a YAML file
     *
     * @param string $file File path
     *
     * @return mixed Anything
     *
     * @throws Exception on error
     */
    public function read(string $file) {
        // Prevent security problems.
        // See https://www.php.net/manual/en/function.yaml-parse-file.php for more details.
        ini_set('yaml.decode_php', 0);

        $result = yaml_parse_file($file);

        if ($result === false) {
            throw new Exception(sprintf(
                'File "%s" contains invalid YAML data.',
                $file
            ), ExitApp::RUNTIME_ERROR);
        }

        return $result;
    }

    /**
     * Write YAML-formatted content to file
     *
     * @param string $file File path
     * @param array $content Content
     *
     * @return self Returns itself
     *
     * @throws Exception on error
     */
    public function write(string $file, array $content): self {
        $result = yaml_emit_file($file, $content, YAML_UTF8_ENCODING, YAML_CRLN_BREAK);

        if ($result === false) {
            throw new Exception(sprintf(
                'File "%s" contains invalid YAML data.',
                ExitApp::RUNTIME_ERROR
            ));
        }

        return $this;
    }

}
