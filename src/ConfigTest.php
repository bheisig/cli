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
 * Command "configtest"
 */
class ConfigTest extends Command {

    protected $errors = [];

    /**
     * Execute command
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    public function execute() {
        $file = $this->config['appDir'] . '/config/schema.json';

        $rules = JSONFile::read($file);

        $this->validate($this->config, $rules);

        if (count($this->errors) === 0) {
            $this->log->info('Configuration settings are OK.');
        } else {
            $this->log->warning('One or more errors found in configuration settings:');

            foreach ($this->errors as $error) {
                $this->log->warning($error);
            }
        }

        return $this;
    }

    /**
     * @param array $content
     * @param array $rules
     * @param string $prefix
     *
     * @throws \Exception on error
     */
    protected function validate(array $content, array $rules, $prefix = '') {
        foreach ($rules as $rule) {
            if (strlen($prefix) > 0) {
                $key = $prefix . '.' . $rule['key'];
            } else {
                $key = $rule['key'];
            }

            if ($rule['required'] &&
                !array_key_exists($rule['key'], $content)) {
                $this->errors[] = sprintf(
                    'Missing configuration setting "%s"',
                    $key
                );
            } else if ($rule['required'] === false &&
                !array_key_exists($rule['key'], $content)) {
                continue;
            }

            $value = $content[$rule['key']];

            switch ($rule['type']) {
                case 'object':
                    if (!is_array($value)) {
                        $this->errors[] = sprintf(
                            'Configuration setting "%s" is not an object',
                            $key
                        );
                    }

                    $this->validate($value, $rule['nodes'], $key);
                    break;
                case 'array':
                    if (!is_array($value)) {
                        $this->errors[] = sprintf(
                            'Configuration setting "%s" is not an array',
                            $key
                        );
                    }

                    if (array_key_exists('minCount', $rule)) {
                        if (count($value) < $rule['minCount']) {
                            $this->errors[] = sprintf(
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
                                        $this->errors[] = sprintf(
                                            'Configuration setting "%s" contains a non string value',
                                            $key
                                        );
                                    }

                                    if (array_key_exists('minLength', $rule)) {
                                        if (strlen($item) < $rule['minLength']) {
                                            $this->errors[] = sprintf(
                                                'Configuration setting "%s" has a too short string "%s". Minimum length is %s character(s).',
                                                $key,
                                                $item,
                                                $rule['minLength']
                                            );
                                        }
                                    }
                                    break;
                                case 'boolean':
                                    if (!is_bool($item)) {
                                        $this->errors[] = sprintf(
                                            'Configuration setting "%s" contains a non boolean value',
                                            $key
                                        );
                                    }
                                    break;
                                case 'integer':
                                    if (!is_int($item)) {
                                        $this->errors[] = sprintf(
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
                                $this->errors[] = sprintf(
                                    'Configuration setting "%s" has an unknown value',
                                    $key
                                );
                            }
                        }
                    }
                    break;
                case 'string':
                    if (!is_string($value)) {
                        $this->errors[] = sprintf(
                            'Configuration setting "%s" is not a string',
                            $key
                        );
                    }

                    if (array_key_exists('minLength', $rule)) {
                        if (strlen($value) < $rule['minLength']) {
                            $this->errors[] = sprintf(
                                'Configuration setting "%s" is too short. Minimum length is %s character(s).',
                                $key,
                                $rule['minLength']
                            );
                        }
                    }

                    if (array_key_exists('values', $rule)) {
                        if (!in_array($value, $rule['values'])) {
                            $this->errors[] = sprintf(
                                'Configuration setting "%s" is unknown',
                                $key
                            );
                        }
                    }
                    break;
                case 'integer':
                    if (!is_int($value)) {
                        $this->errors[] = sprintf(
                            'Configuration setting "%s" is not an integer',
                            $key
                        );
                    }

                    if (array_key_exists('gt', $rule)) {
                        if ($value <= $rule['gt']) {
                            $this->errors[] = sprintf(
                                'Configuration setting "%s" is not greater than %s',
                                $key,
                                $rule['gt']
                            );
                        }
                    }

                    if (array_key_exists('ge', $rule)) {
                        if ($value < $rule['ge']) {
                            $this->errors[] = sprintf(
                                'Configuration setting "%s" is not greater equal %s',
                                $key,
                                $rule['ge']
                            );
                        }
                    }

                    if (array_key_exists('lt', $rule)) {
                        if ($value >= $rule['lt']) {
                            $this->errors[] = sprintf(
                                'Configuration setting "%s" is not less than %s',
                                $key,
                                $rule['lt']
                            );
                        }
                    }

                    if (array_key_exists('le', $rule)) {
                        if ($value > $rule['le']) {
                            $this->errors[] = sprintf(
                                'Configuration setting "%s" is not less equal %s',
                                $key,
                                $rule['le']
                            );
                        }
                    }
                    break;
                case 'boolean':
                    if (!is_bool($value)) {
                        $this->errors[] = sprintf(
                            'Configuration setting "%s" is not a boolean',
                            $key
                        );
                    }
                    break;
                case 'mixed':
                    if (array_key_exists('values', $rule)) {
                        if (!in_array($value, $rule['values'])) {
                            $this->errors[] = sprintf(
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
    }

    /**
     * Print usage of command
     *
     * @return self Returns itself
     */
    public function printUsage() {
        $this->log->info('Usage: %1$s %2$s [OPTIONS]

%3$s

Common options:

    -c FILE,                Include settings stored in a JSON-formatted
    --config FILE           configuration file FILE; repeat option for more
                            than one FILE
    -s KEY=VALUE,           Add runtime setting KEY with its VALUE; separate
    --setting KEY=VALUE     nested keys with ".", for example "key1.key2=123";
                            repeat option for more than one KEY

    --no-colors             Do not print colored messages
    -q, --quiet             Do not output messages, only errors
    -v, --verbose           Be more verbose

    -h, --help              Print this help or information about a
                            specific command
    --version               Print version information',
            $this->config['args'][0],
            $this->getName(),
            $this->getDescription()
        );

        return $this;
    }

}
