<?php

namespace BimRunner\Tools\Tools;

use BimRunner\Tools\IO\PropertiesHelperInterface;
use BimRunner\Tools\Traits\OSTrait;
use Symfony\Component\Console\Input\InputOption;

class DockerTools {
    use OSTrait;

    /**
     * Singleton
     *
     * @var
     */
    protected static $me;

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
     * Met en route le docker.
     */
    public function dockerUp(PropertiesHelperInterface $propertiesHelper) {
        if ($propertiesHelper->getState('docker-up') !== TRUE) {
            $this->command('make stop', ProjectTools::me()->getProjectDir());
            $this->command('make up', ProjectTools::me()->getProjectDir());
            $propertiesHelper->setState('docker-up', TRUE);
        }
    }

    /**
     * REtourne les infos des docker lancés.
     *
     * @return array|string
     */
    public function getRuningContainersInfo() {
        $result = $this->command('docker ps');
        return array_slice($result, 1);
    }

    /**
     * REturn true si il y a des container qui sont lancés.
     *
     * @return bool
     */
    public function containerIsRuning() {
        return count($this->getRuningContainersInfo() > 0 );
    }

    /**
     * Arrête les containers.
     */
    public function stopContainers() {
        $this->command('docker stop $(docker ps -aq)');
    }
}
