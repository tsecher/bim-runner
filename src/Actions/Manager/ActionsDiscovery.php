<?php

namespace BimRunner\Actions\Manager;

use Doctrine\Common\Annotations\Reader;
use Runner\Annotation\Action;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ActionsDiscovery {
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    private $annotationReader;

    /**
     * The Kernel root directory
     * @var string
     */
    private $rootDir;

    /**
     * @var array
     */
    private $actions = [];

    /**
     * ActionDiscovery constructor.
     *
     * @param $namespace
     *   The namespace of the actions
     * @param $directory
     *   The directory of the actions
     * @param $rootDir
     * @param Reader $annotationReader
     */
    public function __construct($namespace, $directory, $rootDir, Reader $annotationReader) {
        $this->namespace = $namespace;
        $this->annotationReader = $annotationReader;
        $this->directory = $directory;
        $this->rootDir = $rootDir;
    }

    /**
     * Returns all the workers
     */
    public function getActions() {
        if (!$this->actions) {
            $this->discoverActions();
        }

        return $this->actions;
    }

    /**
     * Discovers actions
     */
    private function discoverActions() {
        $path = $this->rootDir . 'src/' . $this->directory;
        $finder = new Finder();
        $finder->files()->in($path);

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            if ($file->getExtension() === 'php') {
                $class = $this->getNamespaceFromPath($file->getPath()) . '\\' . $file->getBasename('.php');

                $annotation = $this->annotationReader->getClassAnnotation(new \ReflectionClass($class), Action::class);
                if (!$annotation) {
                    continue;
                }

                /** @var \Runner\Annotation\Action $annotation */
                $this->actions[$annotation->getName()] = [
                  'class'      => $class,
                  'annotation' => $annotation,
                ];
            }
        }
    }

    /**
     * Retourne le namespace de la classe supposée du fichier.
     *
     * @param string $getPath
     *
     * @return string
     */
    protected function getNamespaceFromPath(string $path): string {
        $replace = [
          $this->rootDir . 'src/' => '',
          '/'                     => '\\',
        ];

        $namespace = str_replace(
          array_keys($replace),
          $replace,
          $path
        );

        return $this->namespace.'\\'.$namespace;
    }

}
