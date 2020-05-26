<?php

namespace BimRunner\Actions\Manager;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\DocParser;

class ActionsManager {

    /**
     * @var \BimRunner\Actions\Manager\ActionsDiscovery
     */
    private $discovery;

    /**
     * ActionsManager constructor.
     *
     * @param string $baseNamespace
     *     Le namespace de base de l'app où se trouve les actions.
     * @param string $directory
     *     Le répertoire où se trouve les actions (optionnel)
     * @param string $rootDir
     *     Le répertoire de l'application.
     *
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function __construct($baseNamespace = '', $directory = '', $rootDir = '') {
        $this->discovery = new ActionsDiscovery($baseNamespace, $directory, $rootDir, new AnnotationReader(new DocParser()));
    }

    /**
     * Returns a list of available workers.
     *
     * @return array
     */
    public function getActions() {
        return $this->discovery->getActions();
    }

    /**
     * Returns one worker by name
     *
     * @param $name
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getAction($name) {
        $actions = $this->discovery->getActions();
        if (isset($actions[$name])) {
            return $actions[$name];
        }

        throw new \Exception('Worker not found.');
    }

    /**
     * Creates a worker
     *
     * @param $name
     *
     * @return \BimRunner\Actions\Base\ActionInterface
     *
     * @throws \Exception
     */
    public function createAction($name) {
        $actions = $this->discovery->getActions();
        if (array_key_exists($name, $actions)) {
            $class = $actions[$name]['class'];
            if (!class_exists($class)) {
                throw new \Exception('Action class does not exist.');
            }

            return new $class();
        }

        throw new \Exception('Action does not exist.');
    }

}
