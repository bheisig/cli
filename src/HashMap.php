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

namespace bheisig\cli;

/**
 * Manipulate arrays
 */
class HashMap {

    /**
     * Has a multi-dimensional array all needed keys?
     *
     * @param string $needle List of key, for example "inventory.software.os";
     * works with indexed arrays as well, for example "0.components.1.title"
     * @param array $haystack Array
     *
     * @return bool
     */
    public static function hasValue(string $needle, array $haystack): bool {
        $items = explode('.', $needle);
        $key = $items[0];

        if (is_numeric($key)) {
            $key = (int) $key;
        }

        if (count($items) === 1) {
            return array_key_exists($key, $haystack);
        } else {
            if (!array_key_exists($key, $haystack) ||
                !is_array($haystack[$key])) {
                return false;
            }

            $newNeedle = substr($needle, strlen($items[0]) + 1);

            if (!is_string($newNeedle)) {
                return false;
            }

            return self::hasValue(
                $newNeedle,
                $haystack[$key]
            );
        }
    }

    /**
     * get a value from a multi-dimensional
     *
     * @param string $needle List of key, for example "inventory.hardware.nwadapter"; works with indexed arrays as well
     * @param array $haystack Array
     *
     * @return mixed|false Returns value, otherwise false
     */
    public static function getValue(string $needle, array $haystack) {
        $items = explode('.', $needle);
        $key = $items[0];

        if (is_numeric($key)) {
            $key = (int) $key;
        }

        if (count($items) === 1 && array_key_exists($key, $haystack)) {
            return $haystack[$key];
        } else {
            if (!array_key_exists($key, $haystack) ||
                !is_array($haystack[$key])) {
                return false;
            }

            $newNeedle = substr($needle, strlen($items[0]) + 1);

            if (!is_string($newNeedle)) {
                return false;
            }

            return self::getValue(
                $newNeedle,
                $haystack[$key]
            );
        }
    }

}
