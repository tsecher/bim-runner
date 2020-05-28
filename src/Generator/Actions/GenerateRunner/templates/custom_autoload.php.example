<?php

// Set up symfony autoloader
$loader = FALSE;
if (file_exists($autoloadFile = __DIR__ . '/vendor/autoload.php')
  || file_exists($autoloadFile = __DIR__ . '/../autoload.php')
  || file_exists($autoloadFile = __DIR__ . '/../../autoload.php')
) {
    $loader = include_once($autoloadFile);
    $loader->setUseIncludePath(TRUE);
}
else {
    throw new \Exception("Could not locate autoload.php. cwd is $cwd; __DIR__ is " . __DIR__);
}

return $loader;

