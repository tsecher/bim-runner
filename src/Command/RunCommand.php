<?php

namespace BimRunner\Command;

use BimRunner\Actions\Manager\ActionsManager;
use BimRunner\Tools\IO\FileHelper;
use BimRunner\Tools\IO\FileHelperInterface;
use BimRunner\Tools\IO\IOHelper;
use BimRunner\Tools\IO\IOHelperInterface;
use BimRunner\Tools\Traits\StringTrait;
use Runner\Base\ActionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class RunCommand extends Command {

    use StringTrait;

    /**
     * ID de l'action de prune.
     *
     * @const string
     */
    const CANCEL_ACTION_ID = 2;

    /**
     * OPtion ONly steps
     *
     * @const string
     */
    const OPTION_ONLY_STEPS = 'only-steps';

    /**
     * Option from steps
     *
     * @const string
     */
    const OPTION_FROM_STEP = 'from-step';

    /**
     * OPtion actions
     *
     * @const string
     */
    const OPTION_ACTIONS = 'actions';

    /**
     * Option auto confirm
     *
     * @const string
     */
    const OPTION_AUTO_CONFIRM = 'y';

    /**
     * Previous properties file name.
     *
     * @const string
     */
    const FILE_RUNNER_DATA = 'runner.yml';

    /**
     * File Helper
     *
     * @var FileHelperInterface
     */
    protected $fileHelper;

    /**
     * Actions disponibles
     *
     * @var \BimRunner\Actions\Base\ActionInterface[]
     */
    protected $availableActions;

    /**
     * Liste des actions d'annulation et de continue.
     *
     * @var array
     */
    protected $stopActions = ['Continue', 'Cancel'];

    /**
     * Properties courantes.
     *
     * @var array;
     */
    protected $currentProperties = [];

    /**
     * State courrant.
     *
     * @var array
     */
    protected $state = [];

    /**
     * {@inheritdoc}
     */
    public function __construct($name = NULL, array $availableActions, FileHelperInterface $fileHelper) {
        parent::__construct($name);

        $this->fileHelper = $fileHelper;

        // Initialisation des ids des available actions.
        $this->initAvailableActions($availableActions);
    }

    /**
     * @return \BimRunner\Tools\IO\FileHelperInterface
     */
    public function getFileHelper() {
        return $this->fileHelper;
    }

    /**
     * Etant donné qu'on ajoute systématiquement des actions d'annulation,
     * on revoit les identifitans pour qu'ils humains.
     */
    protected function initAvailableActions(array $availableActions = []) {
        $this->availableActions = [];
        $id = count($this->stopActions);
        foreach ($availableActions as $action) {
            $action->setId(++$id);
            $this->availableActions[$id] = $action;
        }
    }

    /**
     * Init before use.
     */
    public function init() {
        // Initialisation des options globales.
        $this->initGlobalOptions();

        // Initialisation des options pour chaque action.
        foreach ($this->availableActions as $action) {
            $action->initOptions($this);
        }
    }

    /**
     * Définit les options globales.
     */
    protected function initGlobalOptions() {
        $this->addOption(
          static::OPTION_AUTO_CONFIRM,
          NULL,
          InputOption::VALUE_OPTIONAL,
          'Auto confirm all confirm questions ?',
          FALSE // this is the new default value, instead of null
        );

        $this->addOption(
          static::OPTION_ACTIONS,
          NULL,
          InputOption::VALUE_REQUIRED,
          'Actions separated with ","',
          FALSE // this is the new default value, instead of null
        );

        $this->addOption(
          static::OPTION_ONLY_STEPS,
          NULL,
          InputOption::VALUE_REQUIRED,
          'Steps separated with ","',
          FALSE // this is the new default value, instead of null
        );

        $this->addOption(
          static::OPTION_FROM_STEP,
          NULL,
          InputOption::VALUE_REQUIRED,
          'Step to launch from',
          FALSE // this is the new default value, instead of null
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        // Définition de l'io avec input et output, d'où le nom de IO
        $io = IOHelper::create($this, $input, $output);

        // Récupération des propriétés enregistrées si existantes.
        $savedData = $this->getSavedData($io);

        /**
         * Récupération des actions à exécuter.
         *
         * Si il n'y a pas de données sauvegarder dans runner.yml,
         * on ask l'utlisateur.
         */
        $actionsToExecute = empty($savedData['actions']) ? $this->askActionsToExecute($io) : $this->getActionsByIds($savedData['actions']);

        // On affiche le recap.
        $this->showActionsRecap($io, $actionsToExecute);

        // Si l'utilisateur confirm, on procèdes;
        if ($io->confirm('Valider les actions ?')) {
            // Récupération des propriétés.
            $this->currentProperties = $this->getProperties($savedData['properties'], $actionsToExecute, $io);

            // On enregiste les données d'execution.
            $this->saveData($this->currentProperties, $actionsToExecute);

            // On execute les actions.
            $this->executeActions($actionsToExecute, $this->currentProperties, $io);
        }
    }

    /**
     * Retourne les données du précédent run.
     *
     * @param \BimRunner\Tools\IO\IOHelperInterface $io
     */
    protected function getSavedData(IOHelperInterface $io) {
        $savedData = ['actions' => [], 'properties' => []];
        $filePath = $this->fileHelper->getExecutionDir() . '/' . static::FILE_RUNNER_DATA;
        if (file_exists($filePath)) {
            $io->info($this->s('Un fichier @file contenant les propriétés du dernier lancement a été trouvé.', [
              '@file' => static::FILE_RUNNER_DATA
            ]));
            if ($io->confirm('Voulez-vous l\'utiliser ?')) {
                $savedData = Yaml::parseFile($filePath);
            }
        }

        return $savedData;
    }

    /**
     * Ask la liste d'actions à effectuer à l'utilisateur.
     *
     * @param IOHelperInterface $io
     *
     * @return \BimRunner\Actions\Base\ActionInterface[]
     */
    protected function askActionsToExecute(IOHelperInterface $io) {
        // Ajout des stop actions..
        $names = [];
        foreach ($this->stopActions as $stopAction) {
            $names[count($names) + 1] = $stopAction;
        }

        // Ajout de la liste d'actions.
        foreach ($this->availableActions as $index => $action) {
            $names[count($names) + 1] = $action->getName();
        }

        // Get options
        if ($actionsOptions = $io->getInput()
          ->getOption(static::OPTION_ACTIONS)) {
            $actions = explode(',', $actionsOptions);
            $actions = $this->getActionsByIds($actions);
        }
        else {
            // Ask user.
            do {
                $result = $io->choice('Quelle(s) action(s) voulez-vous effectuer ?', $names, 1);
                $actionId = array_search($result, $names);

                // Si l'action existe.
                if ($action = $this->getActionById($actionId)) {
                    $actions[] = $action;
                }

                // Si l'action est l'annulatino
                if ($actionId === static::CANCEL_ACTION_ID) {
                    $io->error('Vous avez annulé. Du coup on a vider la liste d\'actions. Vous recommencez au départ.');
                    $actions = [];
                }

                // Si le tableau d'action est vide on relance l'action.
                if ($result <= count($this->stopActions) && empty($actions)) {
                    $io->error('Vous devez sélectionner au moins une action');
                }
            } while (empty($actions) || $actionId > count($this->stopActions));
        }

        // Clean actions list
        return static::getSortedActions($actions);
    }

    /**
     * Return the list of actions.
     *
     * @param $actions
     */
    protected function getActionsByIds(array $actions) {
        return array_map(
          [
            $this,
            'getActionById'
          ],
          $actions);
    }

    /**
     * Retourne une action par son identifiant numéric.
     *
     * @param $id
     */
    public function getActionById($id) {
        if (array_key_exists($id, $this->availableActions)) {
            return $this->availableActions[$id];
        }

        return NULL;
    }

    /**
     * @param \BimRunner\Actions\Base\ActionInterface[]
     *
     * @return \BimRunner\Actions\Base\ActionInterface[]
     */
    public static function getSortedActions($actionsList): array {
        $list = $actionsList;
        usort($list, function (ActionInterface $actionA, ActionInterface $actionB): int {
            $a = $actionA->getWeight();
            $b = $actionB->getWeight();
            if ($a > $b) {
                return 1;
            }
            if ($a < $b) {
                return -1;
            }

            return 0;
        });

        return $list;
    }

    /**
     * Affiche le recap des actions.
     *
     * @param \BimRunner\Tools\IO\IOHelperInterface $io
     * @param array $actionsToExecute
     */
    protected function showActionsRecap(IOHelperInterface $io, array $actionsToExecute = []) {
        $io->message('Voici les actions que vous avez sélectionnées', 'confirm');
        foreach ($actionsToExecute as $action) {
            $io->message($this->s(
              '@actionId : @actionName',
              [
                '@actionId'   => $action->getId(),
                '@actionName' => $action->getName(),
              ]
            ), 'confirm');
        }
    }

    /**
     * Retourne la liste des propriétés utilisés pour la liste d'actions.
     *
     * @param array $properties
     * @param \BimRunner\Actions\Base\ActionInterface[] $actions
     * @param \BimRunner\Tools\IO\IOHelperInterface $io
     *
     * @return array|mixed
     */
    protected function getProperties(array $properties, array $actions, IOHelperInterface $io) {
        // On récupère les propriétés passées par option.
        $options = array_filter($io->getInput()->getOptions());
        $properties = array_merge($properties, $options);

        // Initialise la liste de propriété en parcourant chanque action.
        foreach ($actions as $action) {
            $io->section($action->getName());
            $action->setDefaultProperties($properties);
            $action->initQuestions();
            $properties = array_merge($properties, $action->getProperties());
        }

        // Confirm.
        $confirm = $io->confirm('C\est OK pour vous? On peut lancer les actions ? Sinon on recommence.');
        if (!$confirm) {
            $properties = $this->getProperties([], $actions, $io);
        }

        return $properties;
    }

    /**
     * Enregistre les properties dans un fichier yaml à la récine d'execution.
     *
     * @param array $properties
     * @param array $actions
     */
    protected function saveData(array $properties, array $actions): void {
        $noSave = [
          static::OPTION_ONLY_STEPS,
          static::OPTION_FROM_STEP,
          static::OPTION_AUTO_CONFIRM,
          static::OPTION_ACTIONS,
        ];

        $data = [
          'actions'    => array_map(function (\BimRunner\Actions\Base\ActionInterface $action) {
              return $action->getId();
          }, $actions),
          'properties' => array_filter($properties, function ($value, $key) use ($noSave) {
              return !in_array($key, $noSave);
          }, ARRAY_FILTER_USE_BOTH),
        ];
        file_put_contents(
          $this->fileHelper->getExecutionDir() . '/' . static::FILE_RUNNER_DATA,
          Yaml::dump($data));
    }


    /**
     * Execute all actions.
     *
     * @param ActionInterface[] $actions
     * @param array $properties
     */
    protected function executeActions(array $actions, array $properties, IOHelperInterface $io): void {
        // Seul quelques étapes.
        $onlySteps = array_filter(explode(',', $io->getInput()
          ->getOption(static::OPTION_ONLY_STEPS)));
        if (!empty($onlySteps)) {
            $this->executeOnlySteps($onlySteps, $properties, $io);

            return;
        }

        // from step.
        $fromStep = $io->getInput()->getOption(static::OPTION_FROM_STEP);
        if (isset($fromStep) && $fromStep !== FALSE) {
            $this->executeFromStep($actions, $properties, $fromStep, $io);

            return;
        }

        // All steps.
        $this->executeAllActions($actions, $properties, $io);
    }

    /**
     * Execute la liste de action.
     *
     * @param array $actions
     * @param array $properties
     * @param \Runner\Tools\IO\IOHelper $io
     */
    protected function executeAllActions(array $actions, array $properties, IOHelperInterface $io) {
        // Before.
        foreach ($actions as $action) {
            $action->beforeExecute($properties, $this->state);
        }

        $count = count($actions);
        foreach ($actions as $key => $action) {
            $io->section(
              $this->s('[@key/@count] @actionName (Action: @id)', [
                  '@key'        => $key + 1,
                  '@count'      => $count,
                  '@actionName' => $action->getName(),
                  '@id'         => $action->getId(),
                ]
              ));
            $action->execute($properties, $this->state);
        }

        // After.
        foreach ($actions as $action) {
            $action->afterExecute($properties, $this->state);
        }
    }

    /**
     * Execute Seulement quelques steps.
     *
     * @param array $onlySteps
     */
    protected function executeOnlySteps(array $onlySteps, array $properties, IOHelperInterface $io) {
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

        foreach ($actionsData as $actionData) {
            /** @var ActionInterface $action */
            $action = $actionData['action'];
            $action->beforeExecute($properties, $this->state);
            $action->execute($properties, $this->state, $actionData['tasks']);
            $action->afterExecute($properties, $this->state);
        }
    }

    /**
     * Execute une liste d'actions à partir d'un step donné.
     *
     * @param array $actions
     * @param array $properties
     * @param string $fromStep
     * @param IOHelperInterface $io
     */
    protected function executeFromStep(array $actions, array $properties, $fromStep, IOHelperInterface $io) {
        // Définitions de la tache de départ.
        list($actionId, $taskId) = explode('.', $fromStep);
        $taskId = $taskId ?: '1';

        if ($startAction = $this->getActionById($actionId)) {
            // Récupération de la liste de taches.
            $tasks = $startAction->getTasksQueue();
            $tasksToExecute = array_splice(array_keys($tasks), $taskId);
            $startAction->execute($properties, $this->state, $tasksToExecute);

            // Récupération des actions qui suivent l'action de départ.
            $nextActions = array_filter($actions, function (ActionInterface $action) use ($actionId) {
                return $action->getId() > ($actionId - count($this->stopActions)) + 2;
            }, ARRAY_FILTER_USE_BOTH);
            foreach ($nextActions as $action) {
                $action->execute($properties, $this->state);
            }

        }
        else {
            throw new \Exception('L\'action avec id ' . $actionId . ' n\'existe pas');
        }
    }

    /**
     * Retourne les propriétés courrantes.
     *
     * @return array
     */
    public function getCurrentProperties(): array {
        return $this->currentProperties;
    }

    /**
     * Retourne le current state.
     *
     * @return mixed
     */
    public function getState() {
        return $this->state;
    }

}
