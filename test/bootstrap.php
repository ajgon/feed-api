<?php

defined('DS') || define('DS', DIRECTORY_SEPARATOR);
$abs_dir = realpath(implode(DS, array(rtrim(__DIR__, DS), '..')));

// Initialize autoloaders
require $abs_dir . DS . 'vendor' . DS . 'autoload.php';
$db_path = $abs_dir . DS . 'db' . DS . 'test.db';
$log_path = $abs_dir . DS . 'migrations' . DS . '.migrations.test.log';

// Clean testing database
file_put_contents($db_path, '');
if (file_exists($log_path)) {
    unlink($log_path);
}
$app = new Phpmig\Console\PhpmigApplication('dev');
$app->setAutoExit(false);
// @todo send it to dev null
$app->run(
    new Symfony\Component\Console\Input\StringInput('--bootstrap=' . $abs_dir . DS . 'test' . DS . 'phpmig.php migrate'),
    new Symfony\Component\Console\Output\NullOutput()
);

// Configure test database
ORM::configure(
    array(
        'connection_string' => 'sqlite:db/test.db'
    )
);

// Load fixtures
// @todo
