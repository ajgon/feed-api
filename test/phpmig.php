<?php

use \Phpmig\Adapter;
use \Phpmig\Pimple\Pimple;

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

$container = new Pimple();

$container['db'] = $container->share(
    function () {
        return new PDO('sqlite:' . realpath(__DIR__ . DS . '..' . DS . 'db' . DS . 'test.db'));
    }
);

$container['phpmig.adapter'] = $container->share(
    function () {
        // replace this with a better Phpmig\Adapter\AdapterInterface
        return new Adapter\File\Flat(__DIR__ . DS . '..' . DS . 'migrations' . DS . '.migrations.test.log');
    }
);

$container['phpmig.migrations'] = function () {
    return glob(__DIR__ . DS . '..' . DS . 'migrations' . DS . '*.php');
};

return $container;
