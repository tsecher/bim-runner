<?php

namespace BimRunner\Tools\IO;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FileHelper implements FileHelperInterface {

    /**
     * App dir
     *
     * @var string
     */
    protected $appDir;

    /**
     * ExecutionDir
     *
     * @var string
     */
    protected $executionDir;

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
     *
     * @param string $appDir
     * @param string $executionDir
     */
    public static function create(string $appDir, string $executionDir) {
        static::$me = new static($appDir, $executionDir);

        return static::$me;
    }

    /**
     * FileHelper constructor.
     *
     * @param string $appDir
     * @param string $executionDir
     */
    protected function __construct(string $appDir, string $executionDir) {
        $this->appDir = $appDir;
        $this->executionDir = $executionDir;
    }

    /**
     * @return string
     */
    public function getAppDir(): string {
        return $this->appDir;
    }

    /**
     * @return string
     */
    public function getExecutionDir(): string {
        return $this->executionDir;
    }

}
