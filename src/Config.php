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
 * Configuration settings
 */
class Config {

    /**
     * Validate configuration settings
     *
     * @param array $settings Settings
     * @param array $rules Rules
     *
     * @return string[] List of validation errors
     *
     * @throws \Exception on error
     */
    public function validate(array $settings, array $rules) {
        return $this->validateTopic($settings, $rules);
    }

    /**
     * Validate topics recursively
     *
     * @param array $settings Settings
     * @param array $rules Rules
     * @param string $prefix Current prefix for settings; defaults to empty string (no prefix)
     *
     * @return string[] List of validation errors
     *
     * @throws \Exception on error
     */
    protected function validateTopic(array $settings, array $rules, $prefix = '') {
        $errors = [];

        foreach ($rules as $rule) {
            if (strlen($prefix) > 0) {
                $key = $prefix . '.' . $rule['key'];
            } else {
                $key = $rule['key'];
            }

            if ($rule['required'] &&
                !array_key_exists($rule['key'], $settings)) {
                $errors[] = sprintf(
                    'Missing configuration setting "%s"',
                    $key
                );
                continue;
            } elseif ($rule['required'] === false &&
                !array_key_exists($rule['key'], $settings)) {
                continue;
            }

            $value = $settings[$rule['key']];

            switch ($rule['type']) {
                case 'object':
                    if (!is_array($value)) {
                        $errors[] = sprintf(
                            'Configuration setting "%s" is not an object',
                            $key
                        );
                    }

                    $errors = $errors + $this->validateTopic($value, $rule['nodes'], $key);
                    break;
                case 'array':
                    if (!is_array($value)) {
                        $errors[] = sprintf(
                            'Configuration setting "%s" is not an array',
                            $key
                        );
                    }

                    if (array_key_exists('minCount', $rule)) {
                        if (count($value) < $rule['minCount']) {
                            $errors[] = sprintf(
                                'Configuration setting "%s" has too few elements',
                                $key
                            );
                        }
                    }

                    if (array_key_exists('items', $rule)) {
                        foreach ($value as $item) {
                            switch ($rule['items']) {
                                case 'string':
                                    if (!is_string($item)) {
                                        $errors[] = sprintf(
                                            'Configuration setting "%s" contains a non string value',
                                            $key
                                        );
                                    }

                                    if (array_key_exists('minLength', $rule)) {
                                        if (strlen($item) < $rule['minLength']) {
                                            $errors[] = sprintf(
                                                'Configuration setting "%s" has a too short string "%s". ' .
                                                'Minimum length is %s character(s).',
                                                $key,
                                                $item,
                                                $rule['minLength']
                                            );
                                        }
                                    }
                                    break;
                                case 'boolean':
                                    if (!is_bool($item)) {
                                        $errors[] = sprintf(
                                            'Configuration setting "%s" contains a non boolean value',
                                            $key
                                        );
                                    }
                                    break;
                                case 'integer':
                                    if (!is_int($item)) {
                                        $errors[] = sprintf(
                                            'Configuration setting "%s" contains a non integer value',
                                            $key
                                        );
                                    }
                                    break;
                                default:
                                    throw new \Exception(sprintf(
                                        'Unknown value "%s" for "items"',
                                        $rule['items']
                                    ));
                            }
                        }
                    }

                    if (array_key_exists('values', $rule)) {
                        foreach ($value as $item) {
                            if (!in_array($item, $rule['values'])) {
                                $errors[] = sprintf(
                                    'Configuration setting "%s" has an unknown value',
                                    $key
                                );
                            }
                        }
                    }
                    break;
                case 'string':
                    if (!is_string($value)) {
                        $errors[] = sprintf(
                            'Configuration setting "%s" is not a string',
                            $key
                        );
                    }

                    if (array_key_exists('minLength', $rule)) {
                        if (strlen($value) < $rule['minLength']) {
                            $errors[] = sprintf(
                                'Configuration setting "%s" is too short. Minimum length is %s character(s).',
                                $key,
                                $rule['minLength']
                            );
                        }
                    }

                    if (array_key_exists('values', $rule)) {
                        if (!in_array($value, $rule['values'])) {
                            $errors[] = sprintf(
                                'Configuration setting "%s" is unknown',
                                $key
                            );
                        }
                    }
                    break;
                case 'integer':
                    if (!is_int($value)) {
                        $errors[] = sprintf(
                            'Configuration setting "%s" is not an integer',
                            $key
                        );
                    }

                    if (array_key_exists('gt', $rule)) {
                        if ($value <= $rule['gt']) {
                            $errors[] = sprintf(
                                'Configuration setting "%s" is not greater than %s',
                                $key,
                                $rule['gt']
                            );
                        }
                    }

                    if (array_key_exists('ge', $rule)) {
                        if ($value < $rule['ge']) {
                            $errors[] = sprintf(
                                'Configuration setting "%s" is not greater or equal %s',
                                $key,
                                $rule['ge']
                            );
                        }
                    }

                    if (array_key_exists('lt', $rule)) {
                        if ($value >= $rule['lt']) {
                            $errors[] = sprintf(
                                'Configuration setting "%s" is not less than %s',
                                $key,
                                $rule['lt']
                            );
                        }
                    }

                    if (array_key_exists('le', $rule)) {
                        if ($value > $rule['le']) {
                            $errors[] = sprintf(
                                'Configuration setting "%s" is not less or equal %s',
                                $key,
                                $rule['le']
                            );
                        }
                    }
                    break;
                case 'boolean':
                    if (!is_bool($value)) {
                        $errors[] = sprintf(
                            'Configuration setting "%s" is not a boolean',
                            $key
                        );
                    }
                    break;
                case 'mixed':
                    if (array_key_exists('values', $rule)) {
                        if (!in_array($value, $rule['values'])) {
                            $errors[] = sprintf(
                                'Configuration setting "%s" is unknown',
                                $key
                            );
                        }
                    }
                    break;
                default:
                    throw new \Exception(sprintf(
                        'Unknown type "%s" in schema',
                        $rule['type']
                    ));
            }
        }

        return $errors;
    }

}
