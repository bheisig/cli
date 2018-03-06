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

use bheisig\cli\Command\Executes;

/**
 * CLI application
 */
class App {

    /**
     * Options: Option has no value.
     *
     * @var int
     */
    const NO_VALUE = 0;

    /**
     * Options: Option is required.
     *
     * @var int
     */
    const OPTION_REQUIRED = 1;

    /**
     * Options: Option is optional.
     *
     * @var int
     */
    const OPTION_NOT_REQUIRED = 2;

    /**
     * Configuration settings as key-value store
     *
     * @var array Associative array
     */
    protected $config = [];

    /**
     * Supported options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Logger
     *
     * @var \bheisig\cli\Log
     */
    protected $log;

    /**
     * Constructor
     *
     * Add pre-defined commands "help" and "list" and pre-defined options "-c"/"--config", "-h"/"--help", "--no-colors",
     * "-q"/"--quiet", "-v"/"--verbose", "version" and "-s/--setting"
     *
     * Initialize logger with default values
     *
     * @throws \Exception on error
     */
    public function __construct() {
        $this
            ->addCommand('help', '\\bheisig\\cli\\Command\\Help', 'Show this help')
            ->addCommand('list', '\\bheisig\\cli\\Command\\ListCommands', 'List all commands')
            ->addCommand('configtest', '\\bheisig\\cli\\Command\\ConfigTest', 'Validate configuration settings')
            ->addOption('c', 'config', self::OPTION_NOT_REQUIRED)
            ->addOption('h', 'help', self::NO_VALUE)
            ->addOption(null, 'no-colors', self::NO_VALUE)
            ->addOption('q', 'quiet', self::NO_VALUE)
            ->addOption('v', 'verbose', self::NO_VALUE)
            ->addOption(null, 'version', self::NO_VALUE)
            ->addOption('s', 'setting', self::OPTION_NOT_REQUIRED);

        $this->config['log'] = [
            'colorize' => true,
            'verbosity' => Log::ALL | ~Log::DEBUG
        ];

        $this->log = new Log();
    }

    /**
     * Parse a JSON file and add its content to the configuration settings
     *
     * @param string $file File path
     * @param bool $force If "true" and file is not readable ignore it, otherwise throw an exception. Defaults to
     * "false".
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    public function addConfigFile($file, $force = false) {
        $settings = JSONFile::read($file, $force);

        if (is_array($settings)) {
            $this->addConfigSettings($settings);
        } else {
            throw new \Exception(sprintf(
                'Unable to parse configuration file "%s" because content is of type "%s"',
                $file,
                gettype($settings)
            ));
        }

        return $this;
    }

    /**
     * Add configuration settings
     *
     * @param array $settings Configuration settings
     *
     * @return self Returns itself
     */
    public function addConfigSettings(array $settings) {
        $this->config = $this->array_merge_recursive_overwrite(
            $this->config,
            $settings
        );

        return $this;
    }

    /**
     * Get configuration settings
     *
     * @return array
     */
    public function getConfigSettings() {
        return $this->config;
    }

    /**
     * Add a command
     *
     * @param string $title
     * @param string $class Class name incl. namespace
     * @param string $description Description
     *
     * @return self Returns itself
     */
    public function addCommand($title, $class, $description) {
        $this->config['commands'][$title] = [
            'class' => $class,
            'description' => $description
        ];

        return $this;
    }

    /**
     * Add an option
     *
     * @param string $short One optional character
     * @param string $long Two or more optional characters
     * @param int $value Has this option a value? Is it required?
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    public function addOption($short = null, $long = null, $value = self::NO_VALUE) {
        switch ($value) {
            case self::NO_VALUE:
            case self::OPTION_REQUIRED:
            case self::OPTION_NOT_REQUIRED:
                break;
            default:
                throw new \Exception(
                    'Invalid value'
                );
        }

        $option = [
            'value' => $value
        ];

        if (isset($short)) {
            if (!is_string($short) || strlen($short) !== 1) {
                throw new \Exception(sprintf(
                    'Bad short option "%s"',
                    $short
                ));
            }

            $option['short'] = $short;
        }

        if (isset($long)) {
            if (!is_string($long) || strlen($long) < 2) {
                throw new \Exception(sprintf(
                    'Bad long option "%s"',
                    $long
                ));
            }

            $option['long'] = $long;
        }

        $this->options[] = $option;

        return $this;
    }

    /**
     * Run the application
     *
     * @throws \Exception on error
     */
    public function run() {
        try {
            $this
                ->loadComposerFile()
                ->loadArgs()
                ->parseOptions()
                ->loadAdditionalConfigFiles()
                ->addRuntimeSettings()
                ->configureLogger();

            /**
             * Try to find out what the user wants:
             */

            $this->printVersion();

            $this->runCommand();

            $this->callHelp();

            /**
             * Ooops, went to far…
             */

            throw new \Exception('Bad request', 400);
        } catch (\Exception $e) {
            $this->log->fatal($e->getMessage());

            if ($e->getCode() === 400) {
                $class = $this->config['commands']['help']['class'];
                /** @var Executes $help */
                $help = new $class($this->config, $this->log);
                $help->setup()->execute()->tearDown();
            }

            exit(1);
        }
    }

    /**
     * Parse given options
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    protected function parseOptions() {
        $this->config['options'] = [];

        foreach ($this->options as $option) {
            $found = false;

            $types = [];

            if (array_key_exists('short', $option)) {
                $types['-'] = $option['short'];
            }

            if (array_key_exists('long', $option)) {
                $types['--'] = $option['long'];
            }

            foreach ($types as $prefix => $name) {
                $key = $prefix . $name;
                $value = null;

                for ($i = 1; $i < count($this->config['args']); $i++) {
                    if (strpos($this->config['args'][$i], $key, 0) === 0) {
                        switch ($option['value']) {
                            case self::NO_VALUE:
                                $value = true;
                                break;
                            case self::OPTION_REQUIRED:
                            case self::OPTION_NOT_REQUIRED:
                                if (strpos($this->config['args'][$i], $key . '=', 0) === 0) {
                                    $value = str_replace(
                                        $key . '=',
                                        '',
                                        $this->config['args'][$i]
                                    );
                                } else if (array_key_exists(($i + 1), $this->config['args']) &&
                                    substr($this->config['args'][$i + 1], 0, strlen($prefix)) !== $prefix) {
                                    $value = $this->config['args'][$i + 1];
                                } else {
                                    throw new \Exception(sprintf(
                                        'Option "%s" needs a value',
                                        $key
                                    ));
                                }
                                break;
                        }

                        if (array_key_exists($name, $this->config['options']) &&
                            !is_array($this->config['options'][$name])) {
                            $this->config['options'][$name] = [
                                $this->config['options'][$name],
                                $value
                            ];
                        } else if (array_key_exists($name, $this->config['options']) &&
                            is_array($this->config['options'][$name])) {
                            $this->config['options'][$name][] = $value;
                        } else {
                            $this->config['options'][$name] = $value;
                        }

                        $found = true;
                    }
                }
            }

            if ($found === false && $option['value'] === self::OPTION_REQUIRED) {
                $message = '';

                if (array_key_exists('short', $option) && array_key_exists('long', $option)) {
                    $message = sprintf(
                        'Required option "-%s" or "--%s" is missing',
                        $option['short'],
                        $option['long']
                    );
                } else if (array_key_exists('short', $option) && !array_key_exists('long', $option)) {
                    $message = sprintf(
                        'Required option "-%s" is missing',
                        $option['short']
                    );
                } else if (!array_key_exists('short', $option) && array_key_exists('long', $option)) {
                    $message = sprintf(
                        'Required option "--%s" is missing',
                        $option['long']
                    );
                }

                throw new \Exception($message);
            }
        }

        return $this;
    }

    /**
     * Get all arguments from command-line
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    protected function loadArgs() {
        $this->config['args'] = $GLOBALS['argv'];

        if (count($this->config['args']) < 2) {
            throw new \Exception('Too few arguments', 400);
        }

        return $this;
    }

    /**
     * Parse additional configuration files given as options
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    protected function loadAdditionalConfigFiles() {
        $additionalConfigFiles = [];

        foreach ($this->config['options'] as $option => $value) {
            if (in_array($option, ['c', 'config'])) {
                switch(gettype($value)) {
                    case 'string':
                        $additionalConfigFiles[] = $value;
                        break;
                    case 'array':
                        foreach ($value as $item) {
                            if (!is_string($item)) {
                                throw new \Exception(sprintf(
                                    'Unknown value "%s" for option "%s"',
                                    $item,
                                    $option
                                ));
                            }

                            $additionalConfigFiles[] = $item;
                        }
                        break;
                    default:
                        throw new \Exception(sprintf(
                            'Unknown value "%s" for option "%s"',
                            $value,
                            $option
                        ));
                }
            }
        }

        foreach ($additionalConfigFiles as $additionalConfigFile) {
            $this->addConfigFile($additionalConfigFile);
        }

        return $this;
    }

    /**
     * Look for additional configuration settings given as options
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    protected function addRuntimeSettings() {
        $newSettings = [];

        foreach ($this->config['options'] as $option => $value) {
            if (in_array($option, ['s', 'setting'])) {
                switch(gettype($value)) {
                    case 'string':
                        $newSettings[] = $value;
                        break;
                    case 'array':
                        foreach ($value as $item) {
                            if (!is_string($item)) {
                                throw new \Exception(sprintf(
                                    'Unknown value "%s" for option "%s"',
                                    $item,
                                    $option
                                ));
                            }

                            $newSettings[] = $item;
                        }
                        break;
                    default:
                        throw new \Exception(sprintf(
                            'Unknown value "%s" for option "%s"',
                            $value,
                            $option
                        ));
                }
            }
        }

        foreach ($newSettings as $newSetting) {
            $key = strstr($newSetting, '=', true);
            $value = strstr($newSetting, '=');

            if ($key === false || $value === false ||
                strlen($key) === 0 || strlen($value) <= 1) {
                throw new \Exception('Invalid runtime settings');
            }

            // Crop "=":
            $value = substr($value, 1);

            // Type casting:
            if (is_numeric($value)) {
                // Returns int or float:
                $value = $value + 0;
            } else if (strtolower($value) === 'true') {
                $value = true;
            } else if (strtolower($value) === 'false') {
                $value = false;
            }

            $keys = explode('.', $key);

            $settings = $this->buildSettings($keys, $value);

            $this->addConfigSettings($settings);
        }

        return $this;
    }

    /**
     * Create associative array for settings
     *
     * @param string[] $keys Setting path
     * @param mixed $value Value
     *
     * @return array
     */
    protected function buildSettings($keys, $value) {
        $result = [];

        $index = array_shift($keys);

        if (!isset($keys[0])) {
            $result[$index] = $value;
        } else {
            $result[$index] = $this->buildSettings($keys, $value);
        }

        return $result;
    }

    /**
     * Set color handling and verbosity for used logger
     */
    protected function configureLogger() {
        if (array_key_exists('no-colors', $this->config['options'])) {
            $this->config['log']['colorize'] = false;
        }

        $this->log->printColors($this->config['log']['colorize']);

        if (array_key_exists('q', $this->config['options']) ||
            array_key_exists('quiet', $this->config['options'])) {
            $this->config['log']['verbosity'] = Log::FATAL | Log::ERROR;
        } else if (array_key_exists('v', $this->config['options']) ||
            array_key_exists('verbose', $this->config['options'])) {
            $this->config['log']['verbosity'] = Log::ALL;
        }

        $this->log->setVerbosity($this->config['log']['verbosity']);
    }

    /**
     * Parse composer.json
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    protected function loadComposerFile() {
        $composerFile = $this->config['appDir'] . '/composer.json';

        if (!is_readable($composerFile)) {
            throw new \Exception(sprintf(
                'Composer file "%s" is missing or not readable',
                $composerFile
            ));
        }

        $this->config['composer'] = JSONFile::read($composerFile);

        $keys = [
            'name',
            'version'
        ];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->config['composer'])) {
                throw new \Exception(sprintf(
                    'Missing "%s" in composer file "%s"',
                    $key,
                    $composerFile
                ));
            }
        }

        return $this;
    }

    /**
     * Print version information
     */
    protected function printVersion() {
        if (array_key_exists('version', $this->config['options'])) {
            $this->log->info('%s %s', $this->config['composer']['name'], $this->config['composer']['version']);
            exit(0);
        }
    }

    /**
     * Call command "help"
     *
     * @throws \Exception on error
     */
    protected function callHelp() {
        if (array_key_exists('h', $this->config['options']) ||
            array_key_exists('help', $this->config['options'])) {
            $class = $this->config['commands']['help']['class'];
            /** @var Executes $help */
            $help = new $class($this->config, $this->log);
            $help->setup()->execute()->tearDown();
            exit(0);
        }
    }

    /**
     * Execute command given in the arguments and exit application
     *
     * @throws \Exception on error
     */
    protected function runCommand() {
        foreach ($this->config['args'] as $arg) {
            if (array_key_exists($arg, $this->config['commands'])) {
                $class = $this->config['commands'][$arg]['class'];

                $this->config['command'] = $arg;

                if (class_exists($class) &&
                    is_subclass_of($class, __NAMESPACE__ . '\Executes')
                ) {
                    /** @var Executes $command */
                    $command = new $class($this->config, $this->log);

                    foreach ($this->config['args'] as $help) {
                        if (in_array($help, ['-h', '--help'])) {
                            $command->printUsage();
                            exit(0);
                        }
                    }

                    $command->setup()->execute()->tearDown();

                    exit(0);
                }
            }
        }
    }

    /**
     * The real(tm) recursive array merge.
     *
     * @param array $array1 First array
     * @param array $array2 Second array
     * @param array $array (Optional) more arrays
     *
     * @return array Combined array
     */
    protected function array_merge_recursive_overwrite(array $array1, array $array2, array $array = []) {
        $arrays = func_get_args();
        $merged = array();
        while ($arrays) {
            $array = array_shift($arrays);
            assert('is_array($array)');
            if (!$array) {
                continue;
            }
            foreach ($array as $key => $value) {
                if (is_string($key)) {
                    if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
                        $merged[$key] = call_user_func([$this, __FUNCTION__], $merged[$key], $value);
                    } else {
                        $merged[$key] = $value;
                    }
                } else {
                    $merged[] = $value;
                }
            }
        }
        return $merged;
    }

}