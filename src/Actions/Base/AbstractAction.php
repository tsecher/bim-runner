<?php

namespace BimRunner\Actions\Base;

use BimRunner\Tools\IO\IOHelper;
use BimRunner\Tools\IO\PropertiesHelperInterface;
use BimRunner\Tools\Traits\StringTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractAction implements ActionInterface {

    use StringTrait;

    /**
     * Config.
     *
     * @var array
     */
    protected $config;

    /**
     * Properties.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * File helper.
     *
     * @var \Runner\Tools\IO\FileHelper
     */
    protected $fileHelper;

    /**
     * ID
     *
     * @var int
     */
    protected $id;

    /**
     * Nom
     *
     * @var string
     */
    protected $name;

    /**
     * Weight
     *
     * @var int
     */
    protected $weight;

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void {
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalConfig() {
        if (is_null($this->config)) {
            $this->config = [];
            $reflector = new \ReflectionClass(get_called_class());
            $dirname = pathinfo($reflector->getFileName())['dirname'];

            foreach (glob($dirname . '/config/*.yml') as $configFile) {
                $this->config[pathinfo($configFile)['filename']] = Yaml::parseFile($configFile);
            }
        }

        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function getParams(): array {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultParams(array $properties = []) {
        $this->properties = array_merge($this->properties, $properties);
    }

    /**
     * {@inheritdoc}
     */
    public function ask($id, string $questionTitle, $default = NULL, $validators = []) {
        if (!array_key_exists($id, $this->properties) || is_null($this->properties[$id])) {
            $this->properties[$id] = IOHelper::me()
              ->ask($questionTitle, $default, $validators);
        }
        else {
            IOHelper::me()
              ->info($this->s('<question>@questionTitle</question> @value', [
                '@questionTitle' => $questionTitle,
                '@value'         => $this->properties[$id],
              ]));
        }

        return $this->properties[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function confirm($id, string $questionTitle) {
        if (!array_key_exists($id, $this->properties)) {
            $this->properties[$id] = IOHelper::me()->confirm($questionTitle);
        }

        return $this->properties[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function section($id, $sectionTitle) {
        if (!array_key_exists($id, $this->properties)) {
            $this->properties[$id] = IOHelper::me()->section($sectionTitle);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function choice($id, $questionTitle, $options, $default) {
        if (!array_key_exists($id, $this->properties)) {
            $this->properties[$id] = IOHelper::me()
              ->choice($questionTitle, $options, $default);
        }

        return $this->properties[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeExecute(PropertiesHelperInterface $propertiesHelper, array $tasks = []) {
    }

    /**
     * {@inheritdoc}
     */
    public function afterExecute(PropertiesHelperInterface $propertiesHelper, array $tasks = []) {
    }

    /**
     * {@inheritdoc}
     */
    public function execute(PropertiesHelperInterface $propertiesHelper, array $tasks = []) {
        $callbacksList = $this->getTasksQueue();
        $count = count($this->getTasksQueue());

        if (!empty($tasks)) {
            $tasksIndex = array_map(function ($id) {
                return $id - 1;
            }, $tasks);
            $callbacksList = array_intersect_key($callbacksList, array_flip($tasksIndex));
        }

        foreach ($callbacksList as $key => $callback) {

            $method = end($callback);
            IOHelper::me()
              ->step($this->s(' Action @action [@key/@count] : @info - @description (Step ID : @action.@key)', [
                '@action'      => $this->getId(),
                '@info'        => $method,
                '@description' => $this->getDescription(get_class(reset($callback)), $method),
                '@key'         => $key + 1,
                '@count'       => $count,
              ]));

            // Gestion d'erreur.
            try {
                call_user_func_array($callback, [$propertiesHelper]);
            }
            catch (\Exception $e) {
                IOHelper::me()->error($e->getMessage());
                break;
            }
        }
    }

    /**
     * Return method description.
     *
     * @param $class
     * @param $method
     *
     * @return mixed
     * @throws \ReflectionException
     */
    protected function getDescription($class, $method) {
        $reflectionClass = new \ReflectionMethod($class, $method);
        $property = $reflectionClass->getDocComment();
        $desc = explode(PHP_EOL, $property);
        $desc = isset($desc[1]) ? $desc[1] : '';
        $desc = explode('* ', $desc);

        return isset($desc[1]) ? $desc[1] : NULL;
    }

    /**
     * @return string
     */
    final public function getName(): string {
        return $this->name;
    }

    /**
     * @param string $name
     */
    final public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    final public function getWeight(): int {
        return $this->weight;
    }

    /**
     * @param mixed $weight
     */
    final public function setWeight($weight) {
        $this->weight = $weight;
    }

}
