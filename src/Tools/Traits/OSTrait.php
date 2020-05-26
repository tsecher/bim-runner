<?php

namespace BimRunner\Tools\Traits;

use BimRunner\Tools\IO\IOHelper;

trait OSTrait {

    /**
     * Execute a command.
     *
     * @param $command
     * @param null $dir
     *
     * @return string|array
     */
    public function command($command, $dir = NULL) {
        $command = ($dir ? 'cd ' . $dir . ' && ' : '') . $command;

        // Affichage de la commande.
        try {
            $io = IOHelper::me();
            $io->command($command);
        }
        catch (\Exception $e) {
            $io = FALSE;
        }

        $output = [];
        exec($command, $output);

        if ($io) {
            foreach ($output as $line) {
                $io->info($line);
            }
        }

        return $output;
    }

    /**
     * Lance une commande composer.
     *
     * @param $command
     * @param null $dir
     *
     * @return string
     * @throws \Exception
     */
    public function composer($command, $dir = NULL) {
        $command = 'COMPOSER_MEMORY_LIMIT=-1 composer ' . $command;
        IOHelper::me()->command($command);
        $command = ($dir ? 'cd ' . $dir . ' && ' : '') . $command;

        $output = '';
        exec($command, $output);

        return $output;
    }

}
