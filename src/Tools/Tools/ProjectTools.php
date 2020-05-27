<?php

namespace BimRunner\Tools\Tools;

use BimRunner\Actions\Base\ActionInterface;
use BimRunner\Command\RunCommand;
use BimRunner\Tools\IO\FileHelper;
use BimRunner\Tools\IO\PropertiesHelper;
use BimRunner\Tools\Traits\OSTrait;
use Symfony\Component\Console\Command\Command;
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
            static::$me = new static();
        }

        return static::$me;
    }

    /**
     * FileHelper constructor.
     *
     * @param string $appDir
     * @param string $executionDir
     */
    protected function __construct() {
    }

    /**
     * Retourne le drupal root.
     */
    public function getProjectDir() {
        if ($this->projectDir === FALSE) {
            $project = PropertiesHelper::me()
              ->getParam(static::PROP_PROJECT_NAME);
            if ($project) {
                $this->projectDir = FileHelper::me()
                    ->getExecutionDir() . $project;
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
    public function addProjectOption(Command $command) {
        $command->addOption(static::PROP_PROJECT_NAME, NULL, InputOption::VALUE_REQUIRED, 'Quel est le nom du projet ?',);
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
