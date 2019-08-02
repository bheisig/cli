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

use \Exception;
use \RuntimeException;
use \Throwable;
use bheisig\cli\Command\Executes;

/**
 * CLI application
 */
class App implements ExitApp {

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
     * @var Log
     */
    protected $log;

    /**
     * Constructor
     *
     * @throws Exception on error
     */
    public function __construct() {
        $this
            ->checkEnvironment()
            ->invokeStandardCommands()
            ->invokeStandardOptions()
            ->invokeLogging();
    }

    /**
     * @return self Returns itself
     *
     * @throws Exception on error
     */
    protected function checkEnvironment(): self {
        if (PHP_SAPI !== 'cli') {
            throw new Exception(sprintf(
                'This application must be invoked by the CLI interpreter of PHP, not the %s SAPI',
                PHP_SAPI
            ), ExitApp::BAD_USER_INTERACTION);
        }

        return $this;
    }

    /**
     * Invoke standard commands
     *
     * @return self Returns itself
     */
    protected function invokeStandardCommands(): self {
        return $this
            ->addCommand(
                'help',
                __NAMESPACE__ . '\\Command\\Help',
                'Show this help'
            )
            ->addCommand(
                'list',
                __NAMESPACE__ . '\\Command\\ListCommands',
                'List all commands'
            )
            ->addCommand(
                'init',
                __NAMESPACE__ . '\\Command\\Init',
                'Create/update user-defined or system-wide configuration settings'
            )
            ->addCommand(
                'configtest',
                __NAMESPACE__ . '\\Command\\ConfigTest',
                'Validate configuration settings'
            )
            ->addCommand(
                'print-config',
                __NAMESPACE__ . '\\Command\\PrintConfig',
                'Print current configuration settings'
            )
            ->addCommand(
                'version',
                __NAMESPACE__ . '\\Command\\Version',
                'Print version information'
            );
    }

    /**
     * Invoke standard options
     *
     * @return self Returns itself
     *
     * @throws Exception on error
     */
    protected function invokeStandardOptions(): self {
        return $this
            ->addOption('c', 'config', self::OPTION_NOT_REQUIRED)
            ->addOption('h', 'help', self::NO_VALUE)
            ->addOption(null, 'no-colors', self::NO_VALUE)
            ->addOption('q', 'quiet', self::NO_VALUE)
            ->addOption('v', 'verbose', self::NO_VALUE)
            ->addOption(null, 'version', self::NO_VALUE)
            ->addOption('s', 'setting', self::OPTION_NOT_REQUIRED);
    }

    /**
     * Initialize logger with default values
     *
     * @return self Returns itself
     */
    protected function invokeLogging(): self {
        $this->config['log'] = [
            'colorize' => true,
            'verbosity' => Log::ALL & ~Log::DEBUG
        ];

        $this->log = new Log();

        return $this;
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
     * @throws Exception on error
     */
    public function addConfigFile(string $file, $force = false): self {
        $settings = JSONFile::read($file, $force);

        $this->addConfigSettings($settings);

        return $this;
    }

    /**
     * Add configuration settings
     *
     * @param array $settings Configuration settings
     *
     * @return self Returns itself
     */
    public function addConfigSettings(array $settings): self {
        $this->config = $this->arrayMergeRecursiveOverwrite(
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
    public function getConfigSettings(): array {
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
    public function addCommand(string $title, string $class, string $description): self {
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
     * @throws Exception on error
     */
    public function addOption(string $short = null, string $long = null, int $value = self::NO_VALUE): self {
        switch ($value) {
            case self::NO_VALUE:
            case self::OPTION_REQUIRED:
            case self::OPTION_NOT_REQUIRED:
                break;
            default:
                throw new Exception(
                    'Invalid value',
                    ExitApp::BAD_USER_INTERACTION
                );
        }

        $option = [
            'value' => $value
        ];

        if (isset($short)) {
            if (!is_string($short) || strlen($short) !== 1) {
                throw new Exception(sprintf(
                    'Bad short option "%s"',
                    $short
                ), ExitApp::BAD_USER_INTERACTION);
            }

            $option['short'] = $short;
        }

        if (isset($long)) {
            if (!is_string($long) || strlen($long) <= 1) {
                throw new Exception(sprintf(
                    'Bad long option "%s"',
                    $long
                ), ExitApp::BAD_USER_INTERACTION);
            }

            $option['long'] = $long;
        }

        $this->options[] = $option;

        return $this;
    }

    /**
     * Run the application
     *
     * @return self Returns itself
     *
     * @throws Exception on error
     */
    public function run(): self {
        try {
            $this
                ->loadComposerFile()
                ->loadArgs()
                ->parseOptions()
                ->parseArguments()
                ->loadOptionalConfigFiles()
                ->loadAdditionalConfigFiles()
                ->addRuntimeSettings()
                ->configureLogger()
                ->satisfyUserChoice();
        } catch (Exception $exception) {
            $this->abort($exception);
        }

        return $this;
    }

    /**
     * Print exception message, stack trace (only in debug mode) and exit application with standardized exit code
     *
     * @param Throwable $exception Exception
     */
    protected function abort(Throwable $exception) {
        $messageParts = explode(PHP_EOL, $exception->getMessage());

        foreach ($messageParts as $messagePart) {
            $this->log->printAsMessage()->fatal(trim($messagePart));
        }

        if ($this->config['log']['verbosity'] & Log::DEBUG) {
            $stackTrace = explode("\n", $exception->getTraceAsString());

            if (count($stackTrace) > 0) {
                $this->log->debug('Stack trace:');
            }

            foreach ($stackTrace as $line) {
                $this->log->debug('    ' . $line);
            }
        }

        switch ($exception->getCode()) {
            case ExitApp::NOTHING_TO_DO:
                $this->close(ExitApp::NOTHING_TO_DO);
                break;
            case ExitApp::RUNTIME_ERROR:
                $this->close(ExitApp::RUNTIME_ERROR);
                break;
            case ExitApp::BAD_USER_INTERACTION:
                $this->close(ExitApp::BAD_USER_INTERACTION);
                break;
            case ExitApp::COMMON_ERROR:
            default:
                $this->close(ExitApp::COMMON_ERROR);
                break;
        }
    }

    /**
     * Try to find out what the user wants
     *
     * @throws Exception on error
     */
    protected function satisfyUserChoice() {
        // <APP NAME> --version:
        if (array_key_exists('version', $this->config['options'])) {
            $this->executeCommand('version');
            $this->close();
        }

        // <APP NAME> -v:
        if (count($this->config['args']) === 2 &&
            array_key_exists('v', $this->config['options'])) {
            $this->executeCommand('version');
            $this->close();
        }

        // <APP NAME> <COMMAND>:
        foreach ($this->config['args'] as $arg) {
            if (array_key_exists($arg, $this->config['commands'])) {
                $this->executeCommand($arg);
                $this->close();
            }
        }

        switch (count($this->config['arguments'])) {
            // <APP NAME> [<COMMAND>] [<OPTION>] --help:
            // <APP NAME> [<COMMAND>] [<OPTION>] -h:
            case 0:
                $this->executeCommand('help');
                $this->close();
                break;
            // <APP NAME> <UNKNOWN COMMAND>:
            default:
                throw new RuntimeException(sprintf(
                    'Command "%s" not found',
                    $this->config['arguments'][0]
                ), ExitApp::BAD_USER_INTERACTION);
                break;
        }
    }

    /**
     * Parse given options
     *
     * @return self Returns itself
     *
     * @throws Exception on error
     */
    protected function parseOptions(): self {
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
                                } elseif (array_key_exists(($i + 1), $this->config['args']) &&
                                    substr($this->config['args'][$i + 1], 0, strlen($prefix)) !== $prefix) {
                                    $value = $this->config['args'][$i + 1];
                                } else {
                                    throw new Exception(sprintf(
                                        'Option "%s" needs a value',
                                        $key
                                    ), ExitApp::BAD_USER_INTERACTION);
                                }
                                break;
                        }

                        if (array_key_exists($name, $this->config['options']) &&
                            !is_array($this->config['options'][$name])) {
                            $this->config['options'][$name] = [
                                $this->config['options'][$name],
                                $value
                            ];
                        } elseif (array_key_exists($name, $this->config['options']) &&
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
                } elseif (array_key_exists('short', $option) && !array_key_exists('long', $option)) {
                    $message = sprintf(
                        'Required option "-%s" is missing',
                        $option['short']
                    );
                } elseif (!array_key_exists('short', $option) && array_key_exists('long', $option)) {
                    $message = sprintf(
                        'Required option "--%s" is missing',
                        $option['long']
                    );
                }

                throw new Exception($message, ExitApp::BAD_USER_INTERACTION);
            }
        }

        return $this;
    }

    /**
     * Parse given arguments
     *
     * @return self Returns itself
     */
    protected function parseArguments(): self {
        $this->config['arguments'] = [];

        $commandFound =false;

        for ($index = 0; $index < count($this->config['args']); $index++) {
            // Ignore binary name:
            if ($index === 0) {
                continue;
            }

            $arg = $this->config['args'][$index];

            // Ignore command:
            if (array_key_exists($arg, $this->config['commands']) &&
                $commandFound === false) {
                $commandFound = true;
                continue;
            }

            // Ignore options:
            if (strpos($arg, '--') === 0) {
                foreach ($this->options as $option) {
                    if (!array_key_exists('long', $option)) {
                        continue;
                    }

                    if ('--' . $option['long'] !== $arg) {
                        continue;
                    }

                    if ($option['value'] !== self::NO_VALUE &&
                        strpos($arg, '--' . $option['long'] . '=') === 0) {
                        break;
                    } elseif ($option['value'] === self::NO_VALUE) {
                        break;
                    } else {
                        $index += 1;
                        break;
                    }
                }
            } elseif (strpos($arg, '-') === 0) {
                foreach ($this->options as $option) {
                    if (!array_key_exists('short', $option)) {
                        continue;
                    }

                    if ('-' . $option['short'] !== $arg) {
                        continue;
                    }

                    switch ($option['value']) {
                        case self::NO_VALUE:
                            break;
                        case self::OPTION_REQUIRED:
                        case self::OPTION_NOT_REQUIRED:
                            $index += 1;
                            break;
                    }
                }
            } else {
                $this->config['arguments'][] = $arg;
            }
        }

        return $this;
    }

    /**
     * Get all arguments from command-line
     *
     * @return self Returns itself
     *
     * @throws Exception on error
     */
    protected function loadArgs(): self {
        $this->config['args'] = $GLOBALS['argv'];

        return $this;
    }

    /**
     * Try to load optional configuration files (app defaults, system-wide, user-specific)
     *
     * Default settings <APP DIR>/config/default.json are overwritten by…
     *
     * On Linux/BDS/MacOS:
     * …system-wide settings /etc/<APP NAME>/config.json are overwritten by…
     * …user settings ~/.<APP NAME>/config.json
     *
     * On Windows:
     * …system-wide settings C:\tools\<APP NAME>\config.json are overwritten by…
     * …user settings <LOCALAPPDATA>\<APP NAME>\config.json
     *
     * These files needn't to exist.
     *
     * @return self Returns itself
     *
     * @throws Exception on error
     */
    protected function loadOptionalConfigFiles(): self {
        $appName = $this->config['composer']['extra']['name'];

        // Load default settings…
        $this->addConfigFile($this->config['appDir'] . '/config/default.json', true);

        switch (strtolower(substr(PHP_OS, 0, 3))) {
            case 'win':
                // …overwritten by system-wide settings…
                $this->addConfigFile(sprintf('C:\\tools\\%s\\config.json', $appName), true);

                // …overwritten by user settings:
                if (array_key_exists('LOCALAPPDATA', $_SERVER) &&
                    is_string($_SERVER['LOCALAPPDATA']) &&
                    strlen($_SERVER['LOCALAPPDATA']) > 0) {
                    $this->addConfigFile(sprintf('%s\\%s\\config.json', $_SERVER['LOCALAPPDATA'], $appName), true);
                }
                break;
            default:
                // …overwritten by system-wide settings…
                $this->addConfigFile(sprintf('/etc/%s/config.json', $appName), true);

                // …overwritten by user settings:
                if (array_key_exists('USER', $_SERVER) &&
                    $_SERVER['USER'] !== 'root' &&
                    array_key_exists('HOME', $_SERVER) &&
                    is_string($_SERVER['HOME']) &&
                    strlen($_SERVER['HOME']) > 0) {
                    $this->addConfigFile(sprintf('%s/.%s/config.json', $_SERVER['HOME'], $appName), true);
                }
                break;
        }

        return $this;
    }

    /**
     * Parse additional configuration files given as options
     *
     * @return self Returns itself
     *
     * @throws Exception on error
     */
    protected function loadAdditionalConfigFiles(): self {
        $additionalConfigFiles = [];

        foreach ($this->config['options'] as $option => $value) {
            if (in_array($option, ['c', 'config'])) {
                switch (gettype($value)) {
                    case 'string':
                        $additionalConfigFiles[] = $value;
                        break;
                    case 'array':
                        foreach ($value as $item) {
                            if (!is_string($item)) {
                                throw new Exception(sprintf(
                                    'Unknown value "%s" for option "%s"',
                                    $item,
                                    $option
                                ), ExitApp::BAD_USER_INTERACTION);
                            }

                            $additionalConfigFiles[] = $item;
                        }
                        break;
                    default:
                        throw new Exception(sprintf(
                            'Unknown value "%s" for option "%s"',
                            $value,
                            $option
                        ), ExitApp::BAD_USER_INTERACTION);
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
     * @throws Exception on error
     */
    protected function addRuntimeSettings(): self {
        $newSettings = [];

        foreach ($this->config['options'] as $option => $value) {
            if (in_array($option, ['s', 'setting'])) {
                switch (gettype($value)) {
                    case 'string':
                        $newSettings[] = $value;
                        break;
                    case 'array':
                        foreach ($value as $item) {
                            if (!is_string($item)) {
                                throw new Exception(sprintf(
                                    'Unknown value "%s" for option "%s"',
                                    $item,
                                    $option
                                ));
                            }

                            $newSettings[] = $item;
                        }
                        break;
                    default:
                        throw new Exception(sprintf(
                            'Unknown value "%s" for option "%s"',
                            $value,
                            $option
                        ), ExitApp::BAD_USER_INTERACTION);
                }
            }
        }

        foreach ($newSettings as $newSetting) {
            $key = strstr($newSetting, '=', true);
            $value = strstr($newSetting, '=');

            if ($key === false || $value === false ||
                strlen($key) === 0 || strlen($value) <= 1) {
                throw new Exception('Invalid runtime settings', ExitApp::BAD_USER_INTERACTION);
            }

            // Crop "=":
            $value = substr($value, 1);

            // Type casting:
            if (is_numeric($value) && (int) $value == $value) {
                $value = (int) $value;
            } elseif (is_numeric($value) && (float) $value == $value) {
                $value = (float) $value;
            } elseif (strtolower($value) === 'true') {
                $value = true;
            } elseif (strtolower($value) === 'false') {
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
    protected function buildSettings(array $keys, $value): array {
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
     *
     * Respect environment variables
     * - "NO_COLOR" (@see https://no-color.org/) and
     * - "<App name in upper case>_NOCOLOR"
     * regardless of their values
     *
     * Also, disable colors if there is no TTY available
     * (for example, then output is piped to another app)
     *
     * @return self Returns itself
     */
    protected function configureLogger(): self {
        $appNoColor = strtoupper($this->config['composer']['extra']['name']) . '_NOCOLOR';

        if (array_key_exists('no-colors', $this->config['options']) ||
            getenv('NO_COLOR') !== false ||
            getenv($appNoColor) !== false ||
            (function_exists('posix_isatty') && posix_isatty(STDOUT) === false)) {
            $this->config['log']['colorize'] = false;
        }

        $this->log->printColors($this->config['log']['colorize']);

        if (array_key_exists('q', $this->config['options']) ||
            array_key_exists('quiet', $this->config['options'])) {
            $this->config['log']['verbosity'] = Log::FATAL | Log::ERROR;
        } elseif (array_key_exists('v', $this->config['options']) ||
            array_key_exists('verbose', $this->config['options'])) {
            $this->config['log']['verbosity'] = Log::ALL;
        }

        $this->log->setVerbosity($this->config['log']['verbosity']);

        return $this;
    }

    /**
     * Parse composer.json
     *
     * @return self Returns itself
     *
     * @throws Exception on error
     */
    protected function loadComposerFile() {
        $composerFile = $this->config['appDir'] . '/composer.json';

        if (!is_readable($composerFile)) {
            throw new Exception(sprintf(
                'Composer file "%s" is missing or not readable',
                $composerFile
            ), ExitApp::RUNTIME_ERROR);
        }

        $this->config['composer'] = JSONFile::read($composerFile);

        if (!array_key_exists('extra', $this->config['composer']) ||
            !is_array($this->config['composer']['extra'])) {
            throw new Exception(sprintf(
                'Missing "extra" in composer file "%s"',
                $composerFile
            ), ExitApp::RUNTIME_ERROR);
        }

        $keys = [
            'name',
            'version'
        ];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->config['composer']['extra'])) {
                throw new Exception(sprintf(
                    'Missing "extra.%s" in composer file "%s"',
                    $key,
                    $composerFile
                ), ExitApp::RUNTIME_ERROR);
            }
        }

        return $this;
    }

    /**
     * Execute command
     *
     * @param string $command Command name
     *
     * @throws Exception on error
     */
    protected function executeCommand($command) {
        $class = $this->config['commands'][$command]['class'];

        if (!class_exists($class) ||
            !is_subclass_of($class, __NAMESPACE__ . '\\Command\\Executes')
        ) {
            throw new RuntimeException(sprintf(
                'Command "%s" not found',
                $command
            ), ExitApp::BAD_USER_INTERACTION);
        }

        $this->config['command'] = $command;

        /** @var Executes $command */
        $command = new $class($this->config, $this->log);

        foreach ($this->config['args'] as $help) {
            if (in_array($help, ['-h', '--help'])) {
                $command->printUsage();
                return;
            }
        }

        $command->setup();
        $command->execute();
        $command->tearDown();
    }

    /**
     * Close application
     *
     * @param int $exitCode Defaults to 0
     */
    public function close(int $exitCode = ExitApp::WELL_DONE) {
        exit($exitCode);
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
    protected function arrayMergeRecursiveOverwrite(array $array1, array $array2, array $array = []) {
        $arrays = func_get_args();
        $merged = [];

        while ($arrays) {
            $array = array_shift($arrays);

            if (!is_array($array)) {
                continue;
            }

            foreach ($array as $key => $value) {
                if (is_string($key)) {
                    // Merged keys are both arrays, last one has values:
                    if (is_array($value) &&
                        array_key_exists($key, $merged) &&
                        is_array($merged[$key]) &&
                        count($value) > 0) {
                        if (array_keys($value) !== range(0, count($value) - 1)) {
                            // Associative array or zero-indexed, but not sequential array:
                            $merged[$key] = call_user_func([$this, __FUNCTION__], $merged[$key], $value);
                        } else {
                            // Zero-indexed and sequential array:
                            $merged[$key] = $value;
                        }
                    } else {
                        // Value either not an array or an empty array or original is not an array:
                        $merged[$key] = $value;
                    }
                } elseif (!in_array($value, $merged)) {
                    $merged[] = $value;
                }
            }
        }

        return $merged;
    }

}
