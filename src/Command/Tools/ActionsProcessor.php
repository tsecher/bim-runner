<?php

namespace BimRunner\Command\Tools;

use BimRunner\Actions\Base\ActionInterface;
use BimRunner\Command\Traits\ActionRetrieverTrait;
use BimRunner\Tools\Traits\StringTrait;

class ActionsProcessor {
    use StringTrait, ActionRetrieverTrait;

    /**
     * Properties
     *
     * @var \BimRunner\Tools\IO\PropertiesHelperInterface
     */
    protected $propertiesHelper;

    /**
     * IO Helper
     *
     * @var \BimRunner\Tools\IO\IOHelperInterface
     */
    protected $io;

    /**
     * ActionsProcessor constructor.
     *
     * @param \BimRunner\Actions\Base\ActionInterface[] $actions
     * @param \BimRunner\Tools\IO\PropertiesHelperInterface $propertyHelper
     * @param \BimRunner\Tools\IO\IOHelperInterface $io
     */
    public function __construct(array $actions, \BimRunner\Tools\IO\PropertiesHelperInterface $propertiesHelper, \BimRunner\Tools\IO\IOHelperInterface $io) {
        $this->availableActions = $actions;
        $this->propertiesHelper = $propertiesHelper;
        $this->io = $io;
    }

    /**
     * Execute la liste de step.
     */
    public function processSteps($onlySteps) {
        $this->beforeProcess();
        // Récupération des actions en fonction des steps passés
        $actionsData = [];
        foreach ($onlySteps as $stepId) {
            list($actionId, $taskId) = explode('.', $stepId);
            $taskId = $taskId ?: '1';
            if (!array_key_exists($actionId, $actionsData)) {
                if ($action = $this->getActionById($actionId)) {
                    $actionsData[$actionId] = [
                      'action' => $action,
                      'tasks'  => [],
                    ];
                }
                else {
                    throw new \Exception('L\'action avec id ' . $actionId . ' n\'existe pas');
                }
            }

            $actionsData[$actionId]['tasks'][] = $taskId;
        }

        // On parcourt les actions dédiés, en inidiquant les tâches à executer.
        foreach ($actionsData as $actionData) {
            /** @var ActionInterface $action */
            $action = $actionData['action'];
            $action->beforeExecute($this->propertiesHelper);
            $action->execute($this->propertiesHelper, $actionData['tasks']);
            $action->afterExecute($this->propertiesHelper);
        }
    }

    /**
     * Execute tout depuis un step particulier
     */
    public function processFromStep($step) {
        $this->beforeProcess();
        // Définitions de la tache de départ.
        list($actionId, $taskId) = explode('.', $step);
        $taskId = $taskId ?: '1';

        if ($startAction = $this->getActionById($actionId)) {
            // Récupération de la liste de taches.
            $tasks = $startAction->getTasksQueue();
            $taskKeys = array_keys($tasks);
            $tasksToExecute = array_splice($taskKeys, $taskId);
            $startAction->execute($this->propertiesHelper, $tasksToExecute);

            // Récupération des actions qui suivent l'action de départ.
            $nextActions = array_filter($this->availableActions, function (ActionInterface $action) use ($actionId) {
                return $action->getId() > $actionId;
            }, ARRAY_FILTER_USE_BOTH);
            foreach ($nextActions as $action) {
                $action->execute($this->propertiesHelper);
            }

        }
        else {
            throw new \Exception('L\'action avec id ' . $actionId . ' n\'existe pas');
        }
    }

    /**
     * Execute la liste de toutes les actions.
     */
    public function processAll() {
        $this->beforeProcess();
        // Before.
        foreach ($this->availableActions as $action) {
            $action->beforeExecute($this->propertiesHelper);
        }

        // Execution.
        $count = count($this->availableActions);
        foreach ($this->availableActions as $key => $action) {
            $this->io->section(
              $this->s('[@key/@count] @actionName (Action: @id)', [
                  '@key'        => $key + 1,
                  '@count'      => $count,
                  '@actionName' => $action->getName(),
                  '@id'         => $action->getId(),
                ]
              ));
            $action->execute($this->propertiesHelper);
        }

        // After.
        foreach ($this->availableActions as $action) {
            $action->afterExecute($this->propertiesHelper);
        }
    }

    /**
     * Action avant de lancer un process.
     */
    protected function beforeProcess() {
        $this->propertiesHelper->mute();
    }

}
