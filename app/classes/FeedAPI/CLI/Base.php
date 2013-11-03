<?php
/**
 * Base class for CLI actions.
 *
 * PHP version 5.3
 *
 * @category CLI
 * @package  FeedAPI
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/feed-api
 */
namespace FeedAPI\CLI;

/**
 * Base class for CLI actions.
 *
 * @category CLI
 * @package  FeedAPI
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/feed-api
 */
class Base
{
    static protected $action;
    static protected $object;
    static protected $param;
    static protected $command;
    static protected $extra;

    /**
     * Displays error if something unexpected occures.
     *
     * @param  string $message Error message
     * @param  string $param   If provided, it will replace [parameter] field in CLI usage description.
     *
     * @return null
     */
    protected function error($message, $param = 'parameter', $terminate = true)
    {
        file_put_contents('php://stderr', $message . "\n");
        if($param) {
            $this->showUsage($param);
        }
        // TODO: Fix this
        if($terminate) {
            die;
        }
    }

    /**
     * Displays a list of options for user, and asks him to choose one.
     *
     * @param  array   $list       List of options
     * @param  boolean $autoCommit If list is one-elem only, return this element index
     *
     * @return integer Chosen option index
     */
    protected function userDetermine($list, $autoCommit = true) {
        $listSize = count($list);
        $opt = -1;

        if ($listSize == 0) {
            throw new \FeedAPI\Exception('Empty list to determine!');
        }

        if ($listSize == 1 && $autoCommit) {
            return 0;
        }

        while($opt < 0 || $opt > $listSize) {
            foreach ($list as $l => $litem) {
                echo $l + 1 . ". {$litem}\n";
            }
            echo "0. Cancel\n";

            $opt = (int)$this->rawPrompt('');
        }

        return $opt - 1;
    }

    /**
     * Shows CLI usage line
     *
     * @param  string $param If provided, it will replace [parameter] field in CLI usage description.
     *
     * @return null
     */
    protected function showUsage($param = 'parameter') {
        echo 'Usage: ' . self::$command . " <object> <action> [{$param}]\n";
        echo 'Type ' . self::$command . " <object> help - to see detailed info.\n"; //TODO
        echo "Objects: feed, group, user\n";
    }

    /**
     * Prompts user for data, without display user input. Useful for all password-type data.
     *
     * @param  string $prompt Prompt text
     *
     * @return string User input
     */
    protected function hiddenPrompt($prompt = "Enter Password:")
    {
        if (preg_match('/^win/i', PHP_OS)) {
            $password = $this->rawPrompt("Warning: typed password won't be hidden in Windows-based OSes.\nIf you don't wish that to happen, please break this script now.\n{$prompt}");
        } else {
            $command = "/usr/bin/env bash -c 'echo OK'";
            if (rtrim(shell_exec($command)) !== 'OK') {
                $password = $this->rawPrompt($prompt, "Warning: typed password won't be hidden, since the bash shell is missing.\nIf you don't wish that to happen, please break this script now.\n{$prompt}");
            }

            $command = "/usr/bin/env bash -c 'read -s -p \"" . addslashes($prompt) . "\" mypassword && echo \$mypassword'";
            $password = rtrim(shell_exec($command));
            echo "\n";
        }
        return $password;
    }

    /**
     * Prompts user for data
     *
     * @param  string $prompt  Prompt text
     *
     * @return string User input
     */
    protected function rawPrompt($prompt)
    {
        if(!empty($prompt)) {
            echo $prompt . "\n";
        }

        $fh = fopen('php://stdin', 'r');
        $line = fgets($fh);
        $input = trim($line);
        fclose($fh);

        return $input;
    }
}
