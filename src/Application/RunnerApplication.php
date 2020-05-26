<?php

namespace BimRunner\Application;

use Symfony\Component\Console\Application;
use BimRunner\Command\RunCommand;
use Symfony\Component\Templating\Loader\FilesystemLoader;

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
     * RunnerApp constructor.
     */
    public function __construct($name, $appDir) {
        $this->name = $name;

        // Create twig.
        $twig = new FilesystemLoader();

        // Creation de la commande.
        $this->command = new RunCommand($this->name, new Environment($twig), $appDir, getcwd());
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
    public function run($name, $version = '1.0.0') {

        // Initialisation de la commande.
        $this->command->init();

        // Création de l'app.
        $app = new Application($name ?: $this->name, $version);
        $app->add($this->command);
        $app->setDefaultCommand($this->command->getName());
        $app->run();
    }

}
