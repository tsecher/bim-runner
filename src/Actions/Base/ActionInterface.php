<?php

namespace BimRunner\Actions\Base;

use BimRunner\Tools\IO\IOHelperInterface;
use BimRunner\Tools\IO\PropertiesHelper;
use BimRunner\Tools\IO\PropertiesHelperInterface;
use Symfony\Component\Console\Command\Command;

interface ActionInterface {

    public function getName(): string;

    public function setName($name);

    public function getWeight(): int;

    public function setWeight($weight);

    public function initQuestions();

    public function getParams(): array;

    public function getTasksQueue();

    public function setDefaultParams(array $properties = []);

    public function beforeExecute(PropertiesHelperInterface $propertiesHelper, array $tasks = []);

    public function execute(PropertiesHelperInterface $propertiesHelper, array $tasks = []);

    public function afterExecute(PropertiesHelperInterface $propertiesHelper, array $tasks = []);

    public function initOptions(Command $command);

    public function getId(): int;

    public function setId(int $id): void;

}
