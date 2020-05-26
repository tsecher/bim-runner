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
     * @var \BimRunner\Actions\Base\ActionInterface[]
     */
    protected $availableActions;

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
     * Retourne la liste des actions disponibles.
     */
    public function getActions() {
        if( is_null($this->availableActions) ){
            $this->availableActions = [];
            foreach ($this->discovery->getActions() as $id => $actionData){
                $this->createAction($id);
            }            
        }
        return $this->availableActions;
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

        throw new \Exception('Action not found.');
    }

    /**
     * Crée une action depuis son identifiant (classname)
     *
     * @param $id
     *
     * @return \BimRunner\Actions\Base\ActionInterface
     *
     * @throws \Exception
     */
    public function createAction($id) {
        if( !array_key_exists($id, $this->availableActions) ){
            $actions = $this->discovery->getActions();
            if (array_key_exists($id, $actions)) {
                $class = $actions[$id]['class'];
                /** @var \BimRunner\Actions\Manager\Annotation\Action $annotation */
                $annotation = $actions[$id]['annotation'];
                if (!class_exists($class)) {
                    throw new \Exception('Action class does not exist.');
                }


                // Création de l'action
                /** @var \BimRunner\Actions\Base\ActionInterface $action */
                $this->availableActions[$id] = new $class();
                $this->availableActions[$id]->setName($annotation->getName());
                $this->availableActions[$id]->setWeight($annotation->getWeight());
            }
            else{
                throw new \Exception('Action does not exist.');
            }
        }
        return $this->availableActions[$id];

    }

}
