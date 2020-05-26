<?php

namespace BimRunner\Actions\Base;

use BimRunner\Tools\IO\IOHelperInterface;
use Symfony\Component\Console\Command\Command;

interface ActionInterface {

    public function getName(): string;

    public function setName($name);

    public function getWeight(): int;

    public function setWeight($weight);

    public function initQuestions();

    public function getProperties(): array;

    public function getTasksQueue();

    public function setDefaultProperties(array $properties = []);

    public function beforeExecute(array $properties, array &$state, array $tasks = []);

    public function execute(array $properties, array &$state, array $tasks = []);

    public function afterExecute(array $properties, array &$state, array $tasks = []);

    public function initOptions(Command $command);

    public function getId(): int;

    public function setId(int $id): void;

}
