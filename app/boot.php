<?php
/**
 * FeedAPI boot file.
 *
 * PHP version 5.3
 *
 * @category Core
 * @package  FeedAPI
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/feed-api
 */

define('DS', DIRECTORY_SEPARATOR);

$abs_dir = realpath(implode(DS, array(rtrim(__DIR__, DS), '..')));

// Initialize autoloaders
require $abs_dir . DS . 'vendor' . DS . 'autoload.php';
require $abs_dir . DS . 'app' . DS . 'classes' . DS . 'FeedAPI' . DS .'Autoload.php';

$autoloader = new FeedAPI\Autoload();
$autoloader->setBasePath($abs_dir . DS . 'app' . DS . 'classes')->register();

$base = new FeedAPI\Base($abs_dir);
$base->init();
