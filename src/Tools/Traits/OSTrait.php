<?php

namespace BimRunner\Tools\Traits;

use BimRunner\Tools\IO\FileHelper;
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

    /**
     * Retourne les données de composer si on est dans un contexte composer.
     *
     * @return array
     */
    public function getComposerContext($dir) {
        $data = NULL;

        // On parcourt les reps parents pour vérifier le composer.json.
        $dirsData = explode('/', $dir);
        $dirsData = array_merge($dirsData, ['composer.json']);
        $composerPath = implode('/', $dirsData);
        while (!file_exists($composerPath) && count($dirsData) > 2) {
            array_splice($dirsData, -2, 1);
            $composerPath = implode('/', $dirsData);
        }
        if (!file_exists($composerPath)) {
            $data = [
              'dir'  => NULL,
              'data' => NULL
            ];
        }
        else {
            $data = [
              'dir'  => implode('/', array_slice($dirsData, 0, -1)),
              'data' => \json_decode(file_get_contents($composerPath), TRUE)
            ];
        }

        return $data;
    }

}
