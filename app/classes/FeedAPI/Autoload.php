<?php
/**
 * Autoload class file.
 *
 * PHP version 5.3
 *
 * @category Core
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */

namespace FeedAPI;

/**
 * Class used to load other classess automagically.
 *
 * @category Core
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
class Autoload
{
    private $_basePath = false;

    /**
     * Returns base path of autolader.
     *
     * @return string Base path
     */
    public function getBasePath()
    {
        return $this->_basePath;
    }

    /**
     * Sets base path of autoloader.
     *
     * @param  string $basePath Base path
     *
     * @return FeedAPI\Autoload self instance
     */
    public function setBasePath($basePath)
    {
        $this->_basePath = realpath($basePath);

        return $this;
    }

    /**
     * Finds the path to the file where the class is defined.
     *
     * @param  string $class The name of the class
     *
     * @return string|false The path if found, false otherwise
     */
    public function findFile($class)
    {
        // work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
        if ('\\' == $class[0]) {
            $class = substr($class, 1);
        }

        if (false !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            $classPath = strtr(substr($class, 0, $pos), '\\', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $className = substr($class, $pos + 1);
        } else {
            // PEAR-like class name
            $classPath = null;
            $className = $class;
        }

        $classPath .= strtr($className, '_', DIRECTORY_SEPARATOR) . '.php';

        return stream_resolve_include_path($this->_basePath . DIRECTORY_SEPARATOR . $classPath);
    }
    /**
     * Loads the given class or interface.
     *
     * @param  string    $class The name of the class
     *
     * @return bool|null True if loaded, null otherwise
     */
    public function loadClass($class)
    {
        if ($file = $this->findFile($class)) {
            include $file;

            return true;
        }
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not
     */
    public function register($prepend = false)
    {
        if (!$this->_basePath) {
            throw new \Exception('Base path for FeedAPI\Autoloader is not defined.');
        }

        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    /**
     * Unregisters this instance as an autoloader.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }
}
