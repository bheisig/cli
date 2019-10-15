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
 * Value
 */
class Value {

    /**
     * Is value a non-empty string?
     * @param mixed $value Value
     * @return bool
     */
    public static function isNonEmptyString($value): bool {
        if (!is_string($value)) {
            return false;
        }

        $value = trim($value);

        if (strlen($value) === 0) {
            return false;
        }

        return true;
    }

    /**
     * Is value an empty string?
     * @param mixed $value Value
     * @return bool
     */
    public static function isEmptyString($value): bool {
        if (!is_string($value)) {
            return false;
        }

        $value = trim($value);

        if (strlen($value) !== 0) {
            return false;
        }

        return true;
    }

}
