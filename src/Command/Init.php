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

namespace bheisig\cli\Command;

use bheisig\cli\IO;
use bheisig\cli\JSONFile;

/**
 * Command "init"
 */
class Init extends Command {

    /**
     * Execute command
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    public function execute() {
        $this->log->info($this->getDescription());

        $appName = $this->config['composer']['extra']['name'];

        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            $configDir = sprintf('%s\\%s', $_SERVER['LOCALAPPDATA'], $appName);
        } elseif ($_SERVER['USER'] === 'root') {
            $configDir = sprintf('/etc/%s', $appName);
        } else {
            $configDir = sprintf('%s/.%s', $_SERVER['HOME'], $appName);
        }

        $configFile = $configDir . DIRECTORY_SEPARATOR . 'config.json';

        $schemaFile = $this->config['appDir'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'schema.json';

        $this->log->notice('This command will ask you several questions.');
        $this->log->notice(
            'After that a configuration file will be written to %s.',
            $configFile
        );
        $this->log->notice('If you are unsure what to do please refer to the documentation.');

        if (file_exists($configFile)) {
            $this->log->notice('Configuration file already exists.');

            if (!$this->askForPermission('Do you want to overwrite it?')) {
                $this->log->notice('Skipping');
                return $this;
            }

            $this->log->debug('Create backup of old configuration file…');
        }

        $rules = JSONFile::read($schemaFile);

        $config = $this->walk($rules, $this->config);

        $this->createDir($configDir);

        if (file_exists($configFile)) {
            $backupFile = sprintf(
                '%s%sconfig_%s.json',
                DIRECTORY_SEPARATOR,
                $configDir,
                date('Y-m-d_His')
            );

            $this->log->info(
                'Create backup of old configuration file and write it to "%s"…',
                $backupFile
            );

            $this->copyFile($configFile, $backupFile);
        }

        $this->log->info('Write configuration settings to file…');

        JSONFile::write($configFile, $config);

        $this->log->info(
            'All configuration steps completed. Your settings are stored to:

    %1$s

Validate your configuration settings with:

    %2$s configtest',
            $configFile,
            $this->config['args'][0]
        );

        return $this;
    }

    protected function walk(array $rules, array $defaults, $prefix = '') {
        $config = [];

        foreach ($rules as $rule) {
            if (strlen($prefix) > 0) {
                $key = $prefix . '.' . $rule['key'];
            } else {
                $key = $rule['key'];
            }

            $default = null;

            if (is_array($defaults) &&
                array_key_exists($rule['key'], $defaults)) {
                $default = $defaults[$rule['key']];
            }

            $value = null;

            switch ($rule['type']) {
                case 'string':
                    $value = $this->askForString(
                        $key,
                        $rule['required'],
                        $default,
                        (array_key_exists('values', $rule)) ? $rule['values'] : [],
                        (array_key_exists('minLength', $rule)) ? $rule['minLength'] : 0
                    );
                    break;
                case 'integer':
                    $value = $this->askForInteger(
                        $key,
                        $rule['required'],
                        $default,
                        (array_key_exists('gt', $rule)) ? $rule['gt'] : null,
                        (array_key_exists('gt', $rule)) ? $rule['ge'] : null,
                        (array_key_exists('lt', $rule)) ? $rule['lt'] : null,
                        (array_key_exists('le', $rule)) ? $rule['le'] : null
                    );
                    break;
                case 'boolean':
                    $value = $this->askForBoolean(
                        $key,
                        $rule['required'],
                        $default
                    );
                    break;
                case 'array':
                    $value = $this->askForArray(
                        $key,
                        $rule['required'],
                        $default,
                        (array_key_exists('values', $rule)) ? $rule['values'] : [],
                        (array_key_exists('minCount', $rule)) ? $rule['minCount'] : 0,
                        $rule['items'],
                        (array_key_exists('minLength', $rule)) ? $rule['minLength'] : 0
                    );
                    break;
                case 'mixed':
                    $value = $this->askForMixed(
                        $key,
                        $rule['required'],
                        $default,
                        $rule['values']
                    );
                    break;
                case 'object':
                    $this->log->info('');
                    $this->log->info($key);
                    $this->log->info(str_repeat('-', strlen($key)));
                    $this->log->info('');

                    if ($rule['required'] === true || $this->askToSkip() === false) {
                        $value = $this->walk(
                            $rule['nodes'],
                            (isset($default)) ? $default : [],
                            $key
                        );
                    }
                    break;
            }

            if (isset($value)) {
                $config[$rule['key']] = $value;
            }
        }

        return $config;
    }

    protected function askToSkip() {
        $question = 'This part is optional. Do you like to configure it? [y|N]:';
        $answer = strtolower(IO::in($question));

        switch ($answer) {
            case 'yes':
            case 'y':
            case '1':
            case 'true':
                return false;
            case 'no':
            case 'n':
            case '0':
            case 'false':
            case '':
                return true;
            default:
                $this->log->warning('Excuse me, what do you mean?');

                return $this->askToSkip();
        }
    }

    protected function askForString($key, $required = false, $default = null, array $values = [], $minLength = 0) {
        $question = sprintf(
            'What is the value for configuration setting "%s"',
            $key
        );

        if (count($values) > 0) {
            $question .= sprintf(
                ' (%s)',
                implode(', ', $values)
            );
        }

        $question .= '?';

        // Reset default:
        if (strlen($default) === 0) {
            $default = null;
        }

        if (isset($default)) {
            $question .= sprintf(' [%s]:', $default);
        }

        $answer = IO::in($question);

        if (strlen($answer) === 0 && $required && !isset($default)) {
            $this->log->warning('This setting is required. You need to set a value.');
            return $this->askForString($key, $required, $default, $values, $minLength);
        } elseif (strlen($answer) === 0 && isset($default)) {
            return $default;
        } elseif (count($values) && !in_array($answer, $values)) {
            $this->log->warning(
                'Wrong answer. Here are your options: %s',
                implode(', ', $values)
            );
            return $this->askForString($key, $required, $default, $values, $minLength);
        } elseif (strlen($answer) > 0 && strlen($answer) < $minLength) {
            $this->log->warning(
                'Value must have at least %s character(s)',
                $minLength
            );
            return $this->askForString($key, $required, $default, $values, $minLength);
        } elseif (strlen($answer) > 0) {
            return $answer;
        }

        return null;
    }

    protected function askForInteger(
        $key, $required = false, $default = null, $gt = null, $ge = null, $lt = null, $le = null
    ) {
        $question = sprintf(
            'What is the value for configuration setting "%s"?',
            $key
        );

        if (isset($default)) {
            $question .= sprintf(' [%s]:', $default);
        }

        $answer = IO::in($question);

        $int = (int) $answer;

        if (strlen($answer) === 0 && $required && !isset($default)) {
            $this->log->warning('This setting is required. You need to set a value.');
            return $this->askForInteger($key, $required, $default, $gt, $ge, $lt, $le);
        } elseif (strlen($answer) === 0 && isset($default)) {
            return $default;
        } elseif (strlen($answer) > 0 && !is_numeric($answer)) {
            $this->log->warning('This setting must be an integer value.');
            return $this->askForInteger($key, $required, $default, $gt, $ge, $lt, $le);
        } elseif (strlen($answer) > 0 && isset($gt) && $int <= $gt) {
            $this->log->warning('This setting must be greater than %s.', $gt);
            return $this->askForInteger($key, $required, $default, $gt, $ge, $lt, $le);
        } elseif (strlen($answer) > 0 && isset($ge) && $int < $ge) {
            $this->log->warning('This setting must be greater equal %s.', $ge);
            return $this->askForInteger($key, $required, $default, $gt, $ge, $lt, $le);
        } elseif (strlen($answer) > 0 && isset($lt) && $int >= $lt) {
            $this->log->warning('This setting must be less than %s.', $lt);
            return $this->askForInteger($key, $required, $default, $gt, $ge, $lt, $le);
        } elseif (strlen($answer) > 0 && isset($le) && $int > $le) {
            $this->log->warning('This setting must be less equal %s.', $le);
            return $this->askForInteger($key, $required, $default, $gt, $ge, $lt, $le);
        } elseif (strlen($answer) > 0) {
            return $int;
        }

        return null;
    }

    protected function askForBoolean($key, $required = false, $default = null) {
        $question = sprintf(
            'Enable configuration setting "%s"?',
            $key
        );

        if (isset($default)) {
            switch ($default) {
                case true:
                    $question .= ' [Y|n]:';
                    break;
                case false:
                    $question .= ' [y|N]:';
                    break;
            }
        }

        $answer = strtolower(IO::in($question));

        switch ($answer) {
            case 'yes':
            case 'y':
            case '1':
            case 'true':
                return true;
            case 'no':
            case 'n':
            case '0':
            case 'false':
                return false;
            case '':
                if (isset($default)) {
                    return $default;
                } elseif ($required) {
                    $this->log->warning('Excuse me, what do you mean?');

                    return $this->askForBoolean($key, $required, $default);
                }
                break;
            default:
                if ($required) {
                    $this->log->warning('Excuse me, what do you mean?');

                    return $this->askForBoolean($key, $required, $default);
                }
                break;
        }

        return null;
    }

    protected function askForArray(
        $key, $required = false, $default = null, array $values = [], $minCount = 0, $items = 'string', $minLength = 0
    ) {
        $question = sprintf(
            'What is the value for configuration setting "%s"',
            $key
        );

        if (count($values) > 0) {
            $question .= sprintf(
                ' (%s)',
                implode(', ', $values)
            );
        }

        $question .= '?';

        if (isset($default)) {
            $question .= sprintf(' [%s]:', implode(', ', $default));
        }

        $answer = IO::in($question);

        if (strlen($answer) === 0 && $required && !isset($default)) {
            $this->log->warning('This setting is required. You need to set a value.');
            return $this->askForArray($key, $required, $default, $values, $minCount, $items, $minLength);
        } elseif (strlen($answer) === 0 && isset($default)) {
            return $default;
        }

        $userArray = explode(',', $answer);

        if (count($userArray) > 0) {
            if (count($userArray) < $minCount) {
                $this->log->warning(
                    'You need to specify at least %s options. Here are your options: %s',
                    $minCount,
                    implode(', ', $values)
                );
                return $this->askForArray($key, $required, $default, $values, $minCount, $items, $minLength);
            }

            $result = [];

            foreach ($userArray as $item) {
                $value = trim($item);

                if (count($values) > 0 && !in_array($value, $values)) {
                    $this->log->warning(
                        'Wrong answer. Here are your options: %s',
                        implode(', ', $values)
                    );
                    return $this->askForArray($key, $required, $default, $values, $minCount, $items, $minLength);
                }

                switch ($items) {
                    case 'string':
                        if (!is_string($value)) {
                            $this->log->warning(
                                'Values must be %ss. Here are your options: %s',
                                $items,
                                implode(', ', $values)
                            );
                            return $this->askForArray(
                                $key,
                                $required,
                                $default,
                                $values,
                                $minCount,
                                $items,
                                $minLength
                            );
                        }
                        break;
                }

                $result[] = $value;
            }

            return $result;
        }

        return null;
    }

    protected function askForMixed($key, $required = false, $default = null, array $values = []) {
        $question = sprintf(
            'What is the value for configuration setting "%s"',
            $key
        );

        $options = [];

        if (count($values) > 0) {
            foreach ($values as $value) {
                switch (gettype($value)) {
                    case 'boolean':
                        $options[] = ($value) ? 'yes' : 'no';
                        break;
                    default:
                        $options[] = $value;
                        break;
                }
            }

            $question .= sprintf(
                ' (%s)',
                implode(', ', $options)
            );
        }

        $question .= '?';

        if (isset($default)) {
            switch (gettype($default)) {
                case 'boolean':
                    $question .= sprintf(' [%s]:', ($default) ? 'yes' : 'no');
                    break;
                default:
                    $question .= sprintf(' [%s]:', $default);
                    break;
            }
        }

        $answer = trim(IO::in($question));

        switch ($answer) {
            case 'yes':
            case 'y':
            case '1':
            case 'true':
                return true;
            case 'no':
            case 'n':
            case '0':
            case 'false':
                return false;
            case '':
                if (isset($default)) {
                    return $default;
                } elseif ($required) {
                    $this->log->warning('Excuse me, what do you mean?');

                    return $this->askForMixed($key, $required, $default, $values);
                }
                break;
            default:
                if (count($values) && !in_array($answer, $values)) {
                    $this->log->warning(
                        'Wrong answer. Here are your options: %s',
                        implode(', ', $options)
                    );

                    return $this->askForMixed($key, $required, $default, $values);
                }

                return $answer;
        }

        return null;
    }

    /**
     * Create a directory if necessary
     *
     * @param string $path Path
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    protected function createDir($path) {
        if (!is_dir($path)) {
            $this->log->info('Create directory %s', $path);

            $status = mkdir($path, 0775, true);

            if ($status === false) {
                throw new \Exception(sprintf(
                    'Unable to create directory %s',
                    $path
                ));
            }
        }

        return $this;
    }

    /**
     * Copy file
     *
     * @param string $sourceFile Path
     * @param string $destFile Path
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    protected function copyFile($sourceFile, $destFile) {
        $status = copy($sourceFile, $destFile);

        if ($status === false) {
            throw new \Exception(sprintf(
                'Unable to copy file "%s" to "%s"',
                $sourceFile,
                $destFile
            ));
        }

        return $this;
    }

    /**
     * Print usage of command
     *
     * @return self Returns itself
     */
    public function printUsage() {
        $this->log->info(
            'Usage: %1$s %2$s

%3$s',
            $this->config['args'][0],
            $this->getName(),
            $this->getDescription()
        );

        return $this;
    }

}
