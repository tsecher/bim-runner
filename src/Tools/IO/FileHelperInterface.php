<?php

namespace BimRunner\Tools\IO;

interface FileHelperInterface {

    /**
     * Retourne le path du repertoire de l'application
     * @return string
     */
    public function getAppDir(): string;

    /**
     * Retourne le path du répertoire d'éxecution.
     *
     * @return string
     */
    public function getExecutionDir(): string;
}
