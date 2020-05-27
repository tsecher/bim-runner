<?php

namespace {{action_namespace}};

use BimRunner\Actions\Base\AbstractAction;
use BimRunner\Tools\IO\IOHelper;
use BimRunner\Tools\IO\PropertiesHelperInterface;
use BimRunner\Tools\Tools\ProjectTools;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use BimRunner\Actions\Manager\Annotation\Action;

/**
 * @Action(
 *     name = "{{action_name}}",
 *     weight = {{action_weight}}
 * )
 */
class {{action_class_name}} extends AbstractAction {

    /**
     * Exemple de propriété.
     *
     * @const string
     */
    const PROP_1 = 'prop_1';

    /**
     * {@inheritdoc}
     */
    public function initOptions(Command $command) {
        // Ajout de l'option sur le nom du projet si besoin.
        ProjectTools::me()->addProjectOption($command);
        // AJout de l'option sur la propriété prop_1
        $command->addOption(static::PROP_1, null, InputOption::VALUE_REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    public function initQuestions() {
        // Question sur le nom du projet.
        ProjectTools::me()->askName($this);
        // Question sur la prop 1 :
        $this->ask(static::PROP_1, 'Quel est la valeur de la propriété 1 ? ');
    }

    /**
     * {@inheritdoc}
     */
    public function getTasksQueue() {
        return [
            [$this, 'task1'],
        ];
    }

    /**
     * Afiche un exampel d'utilisation de IOHelper.
     *
     * @param \BimRunner\Tools\IO\PropertiesHelperInterface $propertiesHelper
     *
     * @throws \Exception
     */
    protected function task1(PropertiesHelperInterface $propertiesHelper) {
        IOHelper::me()->info('On traite la propriété 1 : ' . $propertiesHelper->getParam(static::PROP_1));
    }

}
