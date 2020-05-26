<?php

namespace BimRunner\Application;

use BimRunner\Actions\Manager\ActionsManager;
use BimRunner\Tools\IO\FileHelper;
use Symfony\Component\Console\Application;
use BimRunner\Command\RunCommand;

class RunnerApplication {

    /**
     * Command
     *
     * @var \BimRunner\Command\RunCommand
     */
    protected $command;

    /**
     * Nom de l'appli
     *
     * @var string
     */
    protected $name;

    /**
     * Action manager
     *
     * @var \BimRunner\Actions\Manager\ActionsManager
     */
    protected $actionManager;

    /**
     * RunnerApp constructor.
     */
    public function __construct($name, $appDir, $baseNamespace, $actionsDirectory) {
        $this->name = $name;
        $this->appDir = $appDir;
        $this->baseNamespace = $baseNamespace;
        $this->actionsDirectory = $actionsDirectory;

        // Initialise l'action manager.
        $this->actionsManager = new ActionsManager($baseNamespace, $actionsDirectory, $appDir);

        // Creation de la commande.
        $this->command = new RunCommand($this->name,  $this->actionsManager->getActions(), FileHelper::create($appDir, getcwd()));
    }

    /**
     * Retourne la commande.
     *
     * @return \BimRunner\Command\RunCommand
     */
    public function getCommand(): RunCommand {
        return $this->command;
    }

    /**
     * Lancement de l'app.
     *
     * @throws \Exception
     */
    public function run($name = FALSE, $version = '1.0.0') {

        // Initialisation de la commande.
        $this->command->init();

        // Création de l'app.
        $app = new Application($name ?: $this->name, $version);
        $app->add($this->command);
        $app->setDefaultCommand($this->command->getName());
        $app->run();
    }

}
