<?php

namespace BimRunner\Generator\Actions\GenerateRunner;

use BimRunner\Actions\Base\AbstractAction;
use BimRunner\Generator\Actions\GenerateAction\GenerateActionAction;
use BimRunner\Tools\IO\FileHelper;
use BimRunner\Tools\IO\IOHelper;
use BimRunner\Tools\IO\PropertiesHelperInterface;
use BimRunner\Tools\Tools\ProjectTools;
use BimRunner\Tools\Traits\OSTrait;
use BimRunner\Tools\Traits\ReplaceTrait;
use ClassesWithParents\F;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use BimRunner\Actions\Manager\Annotation\Action;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @Action(
 *     name = "Générer un runner",
 *     weight = 0
 * )
 */
class GenerateRunnerAction extends AbstractAction {
    use ReplaceTrait;

    /**
     * Working Dir
     *
     * @const string
     */
    const PROP_WORKING_DIR = 'runner_working_dir';

    /**
     * Composer package name
     *
     * @const string
     */
    const PROP_COMPOSER_PACKAGE = 'runner_composer_package';

    /**
     * Runner
     *
     * @const string
     */
    const PROP_RUNNER_ID = 'runner_id';

    /**
     * Runner
     *
     * @const string
     */
    const PROP_RUNNER_NAME = 'runner_name';

    /**
     * Runner
     *
     * @const string
     */
    const PROP_RUNNER_NAMESPACE = 'runner_namespace';

    /**
     * Runner
     *
     * @const string
     */
    const PROP_RUNNER_ACTION_DIR = 'runner_action_dir';

    /**
     * Le répertoire de travail.
     *
     * @var mixed
     */
    protected $workDir;

    /**
     * Donnée de contexte composer.
     *
     * @var array|null[]|null
     */
    protected $composerContext;

    /**
     * {@inheritdoc}
     */
    public function initOptions(Command $command) {
        ProjectTools::me()->addProjectOption($command);
        $command->addOption(static::PROP_RUNNER_NAME, NULL, InputOption::VALUE_REQUIRED);
        $command->addOption(static::PROP_RUNNER_ID, NULL, InputOption::VALUE_REQUIRED);
        $command->addOption(static::PROP_RUNNER_NAMESPACE, NULL, InputOption::VALUE_REQUIRED);
        $command->addOption(static::PROP_RUNNER_ACTION_DIR, NULL, InputOption::VALUE_REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    public function initQuestions() {
        // ON vérifie si on est dans un contexte de composer
        $this->initComposerContextData();

        // Ask sur le répertoire de travail
        $this->askWorkingDir();
        $this->ask(static::PROP_RUNNER_NAME, 'Quel est le nom humain de votre runner ?');
        $this->ask(static::PROP_RUNNER_ID, 'QUel est l\'id de votre runner (la commande) ?', NULL, ['isSnake']);
        $this->ask(static::PROP_RUNNER_NAMESPACE, 'Quel est le namespace de base de l\'app ?');
        $this->ask(static::PROP_RUNNER_ACTION_DIR, 'Quel est le répertiore où seront placées les Actions (par rapport à src) ?');

        // On génère automatiquement le namespace de l'action si on en crée une ensuite.
        $this->properties[GenerateActionAction::PROP_NAMESPACE] =
          $this->properties[static::PROP_RUNNER_NAMESPACE] . '\\'
          . $this->s($this->properties[static::PROP_RUNNER_ACTION_DIR], [
            '/',
            '\\'
          ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTasksQueue() {
        return [
          [$this, 'checkComposer'],
          [$this, 'createDir'],
          [$this, 'addToComposer'],
          [$this, 'requireBimRunner'],
        ];
    }

    /**
     * Vérifie la validité de composer.
     *
     * @throws \Exception
     */
    protected function checkComposer(PropertiesHelperInterface $propertiesHelper) {
        if (!file_exists($this->workDir . '/composer.json')) {
            $this->command('mkdir ' . $this->workDir . ' -p');
            $this->composer('init --stability=dev -n', $this->workDir);
            $this->initComposerContextData();
            unset($this->composerContext['data']['require']);
            $this->saveComposer();
        }
        else {
            // Gestion de la stability.
            if (!isset($this->composerContext['data']['minimum-stability'])
              || $this->composerContext['data']['minimum-stability'] != 'dev') {
                $this->composerContext['data']['minimum-stability'] = 'dev';
                $this->saveComposer();
            }
        }
    }

    /**
     * Crée les fichiers d'application.
     *
     * @param \BimRunner\Tools\IO\PropertiesHelperInterface $propertiesHelper
     *
     * @throws \Exception
     */
    protected function createDir(PropertiesHelperInterface $propertiesHelper) {
        IOHelper::me()->info('Ajout des fichiers root');
        $data = $propertiesHelper->getParams();
        $data['.example'] = '';

        $this->copyDirTemplate(
          __DIR__ . '/templates',
          $this->workDir,
          $data,
          [
            '{{' . $this->str_content_id . '}}'
          ]);
        $this->command('chmod 775 ' . $propertiesHelper->getParam(static::PROP_RUNNER_ID), $this->workDir);

        // Créatiio du rep d'actinos
        IOHelper::me()->info('Ajout du rep d\'actions');
        $this->command('mkdir src/' . $propertiesHelper->getParam(static::PROP_RUNNER_ACTION_DIR) . ' -p', $this->workDir);

    }

    /**
     * Ajoute le binaire à composer.
     *
     * @param \BimRunner\Tools\IO\PropertiesHelperInterface $propertiesHelper
     *
     * @throws \Exception
     */
    protected function addToComposer(PropertiesHelperInterface $propertiesHelper) {
        $mustSave = FALSE;
        IOHelper::me()->info('Ajout du binaire dans composer');
        $binName = $propertiesHelper->getParam(static::PROP_RUNNER_ID);
        if (!isset($this->composerContext['data']['bin']) || !in_array($binName, $this->composerContext['data']['bin'])) {
            $this->composerContext['data']['bin'][] = $binName;
            $mustSave = TRUE;
        }

        // Gestion du namespace
        IOHelper::me()->info('Ajout du namespace');
        $namespace = $propertiesHelper->getParam(static::PROP_RUNNER_NAMESPACE) . '\\';
        if (!isset($this->composerContext['data']['autoload'])
          || !isset($this->composerContext['data']['autoload']['psr-4'])
          || !array_key_exists($namespace, $this->composerContext['data']['autoload']['psr-4'])) {
            $this->composerContext['data']['autoload']['psr-4'][$namespace] = 'src';
            $mustSave = TRUE;
        }

        if ($mustSave) {
            $this->saveComposer();
        }
    }

    protected function requireBimRunner(PropertiesHelperInterface $propertiesHelper) {
        $this->composer('config repositories.bim-runner vcs https://github.com/tsecher/bim-runner', $this->workDir);
        $this->composer('require tsecher/bim-runner ', $this->workDir);
    }

    /**
     * Valid le composer package name.
     *
     * @param $value
     *
     * @return mixed
     */
    public function packageName($value) {
        if (count(array_filter(explode('/', $value))) !== 2) {
            throw  new \Exception('Le nom du package n\'est pas valide. Format : "namespace/project"');
        }

        return $value;
    }

    /**
     * Initialise les données du contexte composer.
     */
    protected function initComposerContextData() {
        $dir = FileHelper::me()
            ->getExecutionDir() . (isset($this->properties[static::PROP_WORKING_DIR]) ? $this->properties[static::PROP_WORKING_DIR] : '');

        $this->composerContext = $this->getComposerContext($dir);
    }

    /**
     * DEmande à l'utilisateur le rep de travail si besoin.
     */
    protected function askWorkingDir() {
        if ($this->composerContext['dir']) {
            $this->workDir = $this->composerContext['dir'];
        }
        else {
            IOHelper::me()
              ->info('Vous n\'êtes pas dans un contexte composer. Vous allez devoir créer un nouveau projet composer.');
            $this->ask(static::PROP_WORKING_DIR, 'Quel est le nom du répertoire du projet ?');
            $this->workDir = FileHelper::me()
                ->getExecutionDir() . $this->properties[static::PROP_WORKING_DIR];
        }
    }

    /**
     * Modifie les données de composer.
     */
    protected function saveComposer() {
        file_put_contents($this->composerContext['dir'] . '/composer.json', json_encode($this->composerContext['data'], JSON_PRETTY_PRINT));
    }

}
