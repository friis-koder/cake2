<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 *
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Console.Command
 *
 * @since         CakePHP(tm) v 2.7.0
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestPluginShell
 *
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Console.Command
 */
class TestPluginShell extends Shell
{
    /**
     * main method
     */
    public function main()
    {
        $this->out('This is the main method called from TestPlugin.TestPluginShell');
    }
}
