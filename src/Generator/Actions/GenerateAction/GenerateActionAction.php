<?php

namespace BimRunner\Generator\Actions\GenerateAction;

use BimRunner\Actions\Base\AbstractAction;
use BimRunner\Tools\IO\FileHelper;
use BimRunner\Tools\IO\PropertiesHelperInterface;
use BimRunner\Tools\Traits\ReplaceTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use BimRunner\Actions\Manager\Annotation\Action;
use Symfony\Component\Finder\Finder;

/**
 * @Action(
 *     name = "Générer une action",
 *     weight = 0
 * )
 */
class GenerateActionAction extends AbstractAction {
    use ReplaceTrait;

    /**
     * CLass name
     *
     * @const string
     */
    const PROP_CLASS_NAME = 'action_class_name';

    /**
     * Action name
     *
     * @const string
     */
    const PROP_ACTION_NAME = 'action_name';

    /**
     * Weight
     *
     * @const string
     */
    const PROP_ACTION_WEIGHT = 'action_weight';

    /**
     * Namespace
     *
     * @const string
     */
    const PROP_NAMESPACE = 'action_namespace';

    /**
     * Suffix
     *
     * @const string
     */
    const CLASS_SUFFIX = 'Action';

    public function initOptions(Command $command) {
        $command->addOption(static::PROP_ACTION_NAME, NULL, InputOption::VALUE_REQUIRED);
        $command->addOption(static::PROP_CLASS_NAME, NULL, InputOption::VALUE_REQUIRED);
        $command->addOption(static::PROP_ACTION_WEIGHT, NULL, InputOption::VALUE_REQUIRED);
        $command->addOption(static::PROP_NAMESPACE, NULL, InputOption::VALUE_REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    public function initQuestions() {
        $this->ask(static::PROP_ACTION_NAME, 'Quel est le nom de l\'action ? ');
        $this->ask(static::PROP_CLASS_NAME, 'Quel est le nom de la class de l\'action ? ');
        $this->ask(static::PROP_ACTION_WEIGHT, 'Quel est le poids de l\'action ? ');
        $this->ask(static::PROP_NAMESPACE, 'Quel est le namespace de l\'action ?');
    }

    /**
     * {@inheritdoc}
     */
    public function getTasksQueue() {
        return [
          [$this, 'createAction']
        ];
    }

    /**
     * Crée les fichiers de l'action.
     *
     * @param \BimRunner\Tools\IO\PropertiesHelperInterface $propertiesHelper
     */
    protected function createAction(PropertiesHelperInterface $propertiesHelper) {
        $data = $propertiesHelper->getParams();
        // Clean class name
        $data[static::PROP_CLASS_NAME] = $this->getCleanClassName($this->properties[static::PROP_CLASS_NAME]);
        $data[static::PROP_NAMESPACE] = $this->getCleanNamespace($data[static::PROP_CLASS_NAME], $this->properties[static::PROP_NAMESPACE]);

        $idWrapper = ['{{' . $this->str_content_id . '}}'];
        $dir = $this->getSourceDir();
        $workspace = $this->getWorkspace($data[static::PROP_NAMESPACE], $dir);
        $this->copyDirTemplate(__DIR__.'/templates', $workspace , $data, $idWrapper);
    }

    /**
     * Retourne le répertoire de copie
     */
    protected function getSourceDir() {
        $dir = FileHelper::me()->getExecutionDir();
        if (strpos($dir, '/src/')) {
            $dir = explode('/src/', $dir)[0] . '/src/';
        }
        elseif (is_dir($dir . '/src')) {
            $dir .= 'src/';
        }

        return $dir;
    }

    /**
     * Retourne le répertoire.
     *
     * @param PropertiesHelperInterface $propertiesHelper
     * @param string $dir
     */
    protected function getWorkspace($namespace, string $dir) {
        $workspace = $dir . implode('/', array_slice(explode('\\', $namespace), 1));

        if (!is_dir($workspace)) {
            $this->command('mkdir ' . $workspace . ' -p');
        }

        return $workspace;
    }

    /**
     * Clean le class name.
     */
    protected function getCleanClassName($className) {
        return $className.static::CLASS_SUFFIX;
    }

    /**
     * Clean le namespace
     */
    protected function getCleanNamespace($className, $namespace) {
        // Si l'utilisateur a saisi le rep de l'action dans le namespace on le vire.
        $sub = substr($namespace, -(strlen($className)));
        if ($sub === static::CLASS_SUFFIX) {
            $namespace = substr($namespace, 0, -(strlen($className)));
        }

        return $namespace . '\\' . substr($className, 0, -(strlen(static::CLASS_SUFFIX)));
    }

}
