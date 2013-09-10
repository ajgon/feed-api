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


// Invoke test migrations
$app = new Phpmig\Console\PhpmigApplication('dev');
$app->setAutoExit(false);
$app->run(
    new Symfony\Component\Console\Input\StringInput('--bootstrap=' . $abs_dir . DS . 'test' . DS . 'phpmig.php migrate'),
    new Symfony\Component\Console\Output\NullOutput()
);
unset($app);

// Configure test database
ORM::configure(
    array(
        'connection_string' => 'sqlite:db/test.db'
    )
);

// Load fixtures
$fixture_files = glob($abs_dir . DS .'test' . DS . 'fixtures' . DS . '*.php');
$fixtures = array();

foreach ($fixture_files as $fixture) {
    $name = preg_replace('/\.php$/', '', basename($fixture));
    $values = include $fixture;

    $fixtures[$name] = $values;

    foreach ($values as $value) {
        $model = ORM::for_table($name)->create();

        foreach ($value as $key => $item) {
            $model->$key = $item;
        }

        $model->save();
    }
}

// Set autoloader
require $abs_dir . DS . 'app' . DS . 'classes' . DS . 'FeedAPI' . DS .'Autoload.php';

$autoloader = new FeedAPI\Autoload();
$autoloader->setBasePath($abs_dir . DS . 'app' . DS . 'classes')->register();
