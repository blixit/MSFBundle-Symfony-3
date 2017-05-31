<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 31/05/17
 * Time: 08:36
 */

use Doctrine\Common\Annotations\AnnotationRegistry;

if ( !is_file($loaderFile = __DIR__.'/../vendor/autoload.php') ){
    throw new \LogicException('Could not find autoload.php in vendor/. Did you run "composer install --dev"?');
}

$loader = require $loaderFile;

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
