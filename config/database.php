<?php
/**
 * Database configuration file, configured for sqlite3 living in db/data.db by default.
 * Sqlite, mysql, postgres and more are supported.
 * See: http://idiorm.readthedocs.org/en/latest/configuration.html
 *
 * PHP version 5.3
 *
 * @category Core
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */

return array(
    // For sqlite (default setup), don't use db/test.db! This file is overwritten by phpunit tests!
    'connection_string' => 'sqlite:db/data.db',

    /* For mysql
    'connection_string' => 'mysql:host=localhost;dbname=my_database',
    'username' => 'database_user',
    'password' => 'top_secret',
    */
);
