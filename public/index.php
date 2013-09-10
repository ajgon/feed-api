<?php
/**
 * Entry point for FeedAPI, the only PHP file which should be visible by docroot!
 *
 * PHP version 5.3
 *
 * @category Core
 * @package  FeedAPI
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/feed-api
 */

require implode(DIRECTORY_SEPARATOR, array('..', 'app', 'boot.php'));
