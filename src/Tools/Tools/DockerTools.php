<?php

namespace BimRunner\Tools\Tools;

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
            throw new \Exception('Singleton non créé. Utilisez static::create');
        }

        return static::$me;
    }

    /**
     * Create singleton.
     */
    public static function create() {
        static::$me = new static();

        return static::$me;
    }

    /**
     * DockerTools constructor.
     */
    protected function __construct() {
    }

    /**
     * Met en route le docker.
     */
    public function dockerUp(&$state) {
        if (!isset($state['docker-up']) || $state['docker-up'] !== TRUE) {
            $this->command('make stop', ProjectTools::me()->getProjectDir());
            $this->command('make up', ProjectTools::me()->getProjectDir());
            $state['docker-up'] = TRUE;
        }
    }

}
