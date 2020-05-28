<?php

namespace BimRunner\Command;

use BimRunner\Command\Tools\ActionsProcessor;
use BimRunner\Command\Tools\PropertiesStorage;
use BimRunner\Command\Traits\ActionRetrieverTrait;
use BimRunner\Tools\IO\FileHelperInterface;
use BimRunner\Tools\IO\IOHelper;
use BimRunner\Tools\IO\IOHelperInterface;
use BimRunner\Tools\IO\PropertiesHelperInterface;
use BimRunner\Tools\Traits\StringTrait;
use BimRunner\Actions\Base\ActionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class RunCommand extends Command {

    use StringTrait, ActionRetrieverTrait;

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
     * File Helper
     *
     * @var FileHelperInterface
     */
    protected $fileHelper;

    /**
     * Properties helerp
     *
     * @var PropertiesHelperInterface
     */
    protected $propertiesHelper;

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
    public function __construct($name = NULL, array $availableActions, FileHelperInterface $fileHelper, PropertiesHelperInterface $propertiesHelper) {
        parent::__construct($name);

        $this->fileHelper = $fileHelper;
        $this->propertiesHelper = $propertiesHelper;

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
        foreach (static::getSortedActions($availableActions) as $action) {
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
        $io = IOHelper::create($this->getHelper('question'), $input, $output);

        // Gestinonaire des propriétés sauvegardées.
        $storage = new PropertiesStorage($io, $this->fileHelper);
        // Récupération des propriétés enregistrées si existantes.
        $savedData = $storage->getSavedData($io);

        /**
         * Récupération des actions à exécuter.
         *
         * Si il n'y a pas de données sauvegarder dans runner.yml,
         * on ask l'utlisateur.
         */
        $actionsToExecute = $this->getActionsToExecute($io, $savedData);

        // On affiche le recap.
        $this->showActionsRecap($io, $actionsToExecute);

        // Si l'utilisateur confirm, on procèdes;
        if ($io->confirm('Valider les actions ?')) {
            // Récupération des paramèetres.
            $this->propertiesHelper->setParams($this->getParams($savedData[PropertiesStorage::FIELD_PARAMS], $actionsToExecute, $io));

            // On enregiste les données d'execution.
            if( $io->confirm('Voulez-vous enregistrer ses propriétés pour une utilisation ultérieure ?') ) {
                $storage->saveData($this->propertiesHelper->getParams(), $actionsToExecute);
            }

            // On execute les actions.
            $this->process($actionsToExecute, $io);
        }
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
     * @param \BimRunner\Actions\Base\ActionInterface[]
     *
     * @return \BimRunner\Actions\Base\ActionInterface[]
     */
    public static function getSortedActions($actionsList): array {
        $list = $actionsList;
        usort($list, function (\BimRunner\Actions\Base\ActionInterface $actionA, ActionInterface $actionB): int {
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
     * @param array $params
     * @param \BimRunner\Actions\Base\ActionInterface[] $actions
     * @param \BimRunner\Tools\IO\IOHelperInterface $io
     *
     * @return array|mixed
     */
    protected function getParams(array $params, array $actions, IOHelperInterface $io) {
        // On récupère les propriétés passées par option.
        $options = array_filter($io->getInput()->getOptions());
        $params = array_merge($params, $options);

        // Initialise la liste de propriété en parcourant chanque action.
        foreach ($actions as $action) {
            $io->section($action->getName());
            $action->setDefaultParams($params);
            $action->initQuestions();
            $params = array_merge($params, $action->getParams());
        }

        // Confirm.
        $confirm = $io->confirm('C\est OK pour vous? On peut lancer les actions ? Sinon on recommence.');
        if (!$confirm) {
            $params = $this->getParams([], $actions, $io);
        }

        return $params;
    }

    /**
     * Execute all actions.
     *
     * @param ActionInterface[] $actions
     * @param array $properties
     */
    protected function process(array $actions, IOHelperInterface $io): void {
        $processor = new ActionsProcessor($actions, $this->propertiesHelper, $io);

        // Récupérations des options.
        $onlySteps = array_filter(explode(',', $io->getInput()
          ->getOption(static::OPTION_ONLY_STEPS)));
        $fromStep = $io->getInput()->getOption(static::OPTION_FROM_STEP);

        if (!empty($onlySteps)) {
            $processor->processSteps($onlySteps);
        }
        elseif (isset($fromStep) && $fromStep !== FALSE) {
            $processor->processFromStep($fromStep);
        }
        else {
            $processor->processAll();
        }
    }

    /**
     * Retourne la liste des actions à executer.
     *
     * @param \BimRunner\Tools\IO\IOHelperInterface $io
     * @param array $savedData
     */
    protected function getActionsToExecute(IOHelperInterface $io, array $savedData) {
        $actionsToExecute = [];
        // Si on a un step de défini.
        $onlySteps = array_filter(explode(',', $io->getInput()
          ->getOption(static::OPTION_ONLY_STEPS)));
        if( !empty($onlySteps) ){
            $actionsIds = array_map(function($step){
                $stepData = explode('.', $step);
                return reset($stepData);
            }, $onlySteps);

            $actionsToExecute = $this->getActionsByIds($actionsIds);
        }
        // Si il y a des actions dans le storage.
        elseif (!empty($savedData[PropertiesStorage::FIELD_ACTIONS])){
            $actionsToExecute = $this->getActionsByIds($savedData[PropertiesStorage::FIELD_ACTIONS]);
        }
        // Sinon on demande.
        else{
            $actionsToExecute = $this->askActionsToExecute($io);
        }

        return $actionsToExecute;
    }

}
