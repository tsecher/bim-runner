<?php

namespace BimRunner\Tools\Tools;

use BimRunner\Actions\Base\ActionInterface;
use BimRunner\Command\RunCommand;
use Symfony\Component\Console\Input\InputOption;

class ProjectTools {
    use OSTrait;

    /**
     * Project name
     *
     * @const string
     */
    const PROP_PROJECT_NAME = 'project_name';

    /**
     * Singleton
     *
     * @var
     */
    protected static $me;

    /**
     * Runner command.
     *
     * @var \BimRunner\Command\RunCommand
     */
    private $runnerCommand;

    /**
     * Project dir.
     *
     * @var string|bool
     */
    protected $projectDir = FALSE;

    /**
     * Retourne le singleton.
     *
     * @return static
     *   Le singleton.
     */
    public static function me() {
        if (!isset(static::$me)) {
            throw new \Exception('Singleton non créé. Utilisez static::create');
        }

        return static::$me;
    }

    /**
     * Create singleton.
     *
     * @param string $appDir
     * @param string $executionDir
     */
    public static function create(RunCommand $runnerCommand) {
        static::$me = new static($runnerCommand);

        return static::$me;
    }

    /**
     * FileHelper constructor.
     *
     * @param string $appDir
     * @param string $executionDir
     */
    protected function __construct(RunnerCommand $runnerCommand) {
        $this->runnerCommand = $runnerCommand;
    }

    /**
     * Retourne le drupal root.
     */
    public function getProjectDir() {
        if ($this->projectDir === FALSE) {
            if (array_key_exists(static::PROP_PROJECT_NAME, $this->runnerCommand->getCurrentProperties())) {
                $this->projectDir = FileHelper::me()
                    ->getExecutionDir() . $this->runnerCommand->getCurrentProperties()[static::PROP_PROJECT_NAME];
            }
            else {
                $this->projectDir = NULL;
            }
        }

        return $this->projectDir;
    }

    /**
     * Force l'update du project dir.
     *
     * @param bool|string $projectDir
     */
    public function setProjectDir($projectDir): void {
        $this->projectDir = $projectDir;
    }

    /**
     * Ajoute l'option sur le nom du projet.
     */
    public function addProjectOption() {
        $this->runnerCommand->addOption(static::PROP_PROJECT_NAME, NULL, InputOption::VALUE_REQUIRED, 'Quel est le nom du projet ?',);
    }

    /**
     * Ask pour le nom du projet.
     *
     * @param \BimRunner\Actions\Base\ActionInterface $action
     */
    public function askName(ActionInterface $action) {
        $action->ask(static::PROP_PROJECT_NAME, 'Nom du projet ?', NULL, [
          'notEmpty',
          'isSnake'
        ]);
    }

}
