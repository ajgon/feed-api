<?php

use \Phpmig\Adapter;
use \Phpmig\Pimple\Pimple;

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

$container = new Pimple();

$container['db'] = $container->share(
    function () {
        $db_config = require 'database.php';

        return new PDO($db_config['connection_string'], @$db_config['username'], @$db_config['password']);
    }
);

$container['phpmig.adapter'] = $container->share(
    function () {
        // replace this with a better Phpmig\Adapter\AdapterInterface
        return new Adapter\File\Flat(__DIR__ . DS . '..' . DS . 'migrations' . DS . '.migrations.log');
    }
);

$container['phpmig.migrations'] = function () {
    return glob(__DIR__ . DS . '..' . DS . 'migrations' . DS . '*.php');
};

return $container;
