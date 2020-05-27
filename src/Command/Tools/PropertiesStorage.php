<?php

namespace BimRunner\Command\Tools;

use BimRunner\Command\RunCommand;
use BimRunner\Tools\IO\IOHelperInterface;
use BimRunner\Tools\Traits\StringTrait;
use Symfony\Component\Yaml\Yaml;

class PropertiesStorage {
    use StringTrait;

    /**
     * Previous properties file name.
     *
     * @const string
     */
    const FILE_RUNNER_DATA = 'runner.yml';

    /**
     * Field actions
     *
     * @const string
     */
    const FIELD_ACTIONS = 'actions';

    /**
     * FIeld params
     *
     * @const string
     */
    const FIELD_PARAMS = 'params';

    /**
     * IO helper
     *
     * @var IOHelperInterface
     */
    protected $io;

    /**
     * File Helper
     *
     * @var \BimRunner\Tools\IO\FileHelperInterface
     */
    protected $fileHelper;

    /**
     * PropertiesStorage constructor.
     *
     * @param \BimRunner\Tools\IO\IOHelperInterface $io
     * @param \BimRunner\Tools\IO\FileHelperInterface $fileHelper
     */
    public function __construct(\BimRunner\Tools\IO\IOHelperInterface $io, \BimRunner\Tools\IO\FileHelperInterface $fileHelper) {
        $this->io = $io;
        $this->fileHelper = $fileHelper;
    }

    /**
     * Retourne les données du précédent run.
     *
     * @return array
     */
    public function getSavedData() {
        $savedData = [
          static::FIELD_ACTIONS=> [],
          static::FIELD_PARAMS => [],
          ];
        $filePath = $this->fileHelper->getExecutionDir() . '/' . static::FILE_RUNNER_DATA;
        if (file_exists($filePath)) {
            $this->io->info($this->s('Un fichier @file contenant les propriétés du dernier lancement a été trouvé.', [
              '@file' => static::FILE_RUNNER_DATA
            ]));
            if ($this->io->confirm('Voulez-vous l\'utiliser ?')) {
                $savedData = Yaml::parseFile($filePath);
            }
        }

        return $savedData;
    }


    /**
     * Enregistre les properties dans un fichier yaml à la récine d'execution.
     *
     * @param array $properties
     * @param array $actions
     */
    public function saveData(array $properties, array $actions): void {
        $noSave = [
          RunCommand::OPTION_ONLY_STEPS,
          RunCommand::OPTION_FROM_STEP,
          RunCommand::OPTION_AUTO_CONFIRM,
          RunCommand::OPTION_ACTIONS,
        ];

        $data = [
          static::FIELD_ACTIONS    => array_map(function (\BimRunner\Actions\Base\ActionInterface $action) {
              return $action->getId();
          }, $actions),
          static::FIELD_PARAMS => array_filter($properties, function ($value, $key) use ($noSave) {
              return !in_array($key, $noSave);
          }, ARRAY_FILTER_USE_BOTH),
        ];
        file_put_contents(
          $this->fileHelper->getExecutionDir() . '/' . static::FILE_RUNNER_DATA,
          Yaml::dump($data));
    }
}
