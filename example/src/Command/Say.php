<?php

namespace hello\world\Command;

use \Exception;
use bheisig\cli\Command\Command;

/**
 * Command "say"
 */
class Say extends Command {

    /**
     * Execute command
     *
     * @return self Returns itself
     *
     * @throws Exception on error
     */
    public function execute() {
        $name = null;
        $reverse = $this->config['reverse'];

        if (array_key_exists('name', $this->config['options'])) {
            $name = $this->config['options']['name'];
        }

        if (array_key_exists('n', $this->config['options'])) {
            $name = $this->config['options']['n'];
        }

        if (array_key_exists('reverse', $this->config['options']) ||
            array_key_exists('r', $this->config['options'])) {
            $reverse = true;
        }

        if (isset($name)) {
            $message = "Hello, $name";
        } else {
            $message = 'Hello, World!';
        }

        if ($reverse) {
            $message = strrev($message);
        }

        $this->log->notice($message);

        return $this;
    }

    /**
     * Print usage of command
     *
     * @return self Returns itself
     */
    public function printUsage() {
        $this->log->info(
            'Usage: %1$s %2$s [OPTIONS]

%3$s

Options:

    -n NAME, --name NAME    Add personal greetings
    - r, --reverse          Print reversed greetings

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
