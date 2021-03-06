<?php
/**
 * CompletionShellTest file
 *
 * PHP 5
 *
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * @link          https://cakephp.org CakePHP Project
 *
 * @package       Cake.Test.Case.Console.Command
 *
 * @since         CakePHP v 2.5
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('CompletionShell', 'Console/Command');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('CommandTask', 'Console/Command/Task');

/**
 * TestCompletionStringOutput
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestCompletionStringOutput extends ConsoleOutput
{
    public $output = '';

    protected function _write($message)
    {
        $this->output .= $message;
    }
}

/**
 * CompletionShellTest
 *
 * @package       Cake.Test.Case.Console.Command
 */
class CompletionShellTest extends CakeTestCase
{
    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();
        App::build([
            'Plugin' => [
                CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS
            ],
            'Console/Command' => [
                CAKE . 'Test' . DS . 'test_app' . DS . 'Console' . DS . 'Command' . DS
            ]
        ], App::RESET);
        CakePlugin::load(['TestPlugin', 'TestPluginTwo']);

        $out = new TestCompletionStringOutput();
        $in = $this->getMock('ConsoleInput', [], [], '', false);

        $this->Shell = $this->getMock(
            'CompletionShell',
            ['in', '_stop', 'clear'],
            [$out, $out, $in]
        );

        $this->Shell->Command = $this->getMock(
            'CommandTask',
            ['in', '_stop', 'clear'],
            [$out, $out, $in]
        );
    }

    /**
     * tearDown
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Shell);
        CakePlugin::unload();
    }

    /**
     * test that the startup method supresses the shell header
     */
    public function testStartup()
    {
        $this->Shell->runCommand('main', []);
        $output = $this->Shell->stdout->output;

        $needle = 'Welcome to CakePHP';
        $this->assertTextNotContains($needle, $output);
    }

    /**
     * test that main displays a warning
     */
    public function testMain()
    {
        $this->Shell->runCommand('main', []);
        $output = $this->Shell->stdout->output;

        $expected = '/This command is not intended to be called manually/';
        $this->assertRegExp($expected, $output);
    }

    /**
     * test commands method that list all available commands
     */
    public function testCommands()
    {
        $this->Shell->runCommand('commands', []);
        $output = $this->Shell->stdout->output;

        $expected = "TestPlugin.example TestPlugin.test_plugin TestPluginTwo.example TestPluginTwo.welcome acl api bake command_list completion console i18n schema server test testsuite upgrade sample\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that options without argument returns the default options
     */
    public function testOptionsNoArguments()
    {
        $this->Shell->runCommand('options', []);
        $output = $this->Shell->stdout->output;

        $expected = "--help -h --verbose -v --quiet -q\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that options with a nonexisting command returns the default options
     */
    public function testOptionsNonExistingCommand()
    {
        $this->Shell->runCommand('options', ['options', 'foo']);
        $output = $this->Shell->stdout->output;

        $expected = "--help -h --verbose -v --quiet -q\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that options with a existing command returns the proper options
     */
    public function testOptions()
    {
        $this->Shell->runCommand('options', ['options', 'bake']);
        $output = $this->Shell->stdout->output;

        $expected = "--help -h --verbose -v --quiet -q --connection -c --theme -t\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subCommands with a existing CORE command returns the proper sub commands
     */
    public function testSubCommandsCorePlugin()
    {
        $this->Shell->runCommand('subCommands', ['subCommands', 'CORE.bake']);
        $output = $this->Shell->stdout->output;

        $expected = "controller db_config fixture model plugin project test view\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subCommands with a existing APP command returns the proper sub commands (in this case none)
     */
    public function testSubCommandsAppPlugin()
    {
        $this->Shell->runCommand('subCommands', ['subCommands', 'app.sample']);
        $output = $this->Shell->stdout->output;

        $expected = '';
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subCommands with a existing plugin command returns the proper sub commands
     */
    public function testSubCommandsPlugin()
    {
        $this->Shell->runCommand('subCommands', ['subCommands', 'TestPluginTwo.welcome']);
        $output = $this->Shell->stdout->output;

        $expected = "say_hello\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subcommands without arguments returns nothing
     */
    public function testSubCommandsNoArguments()
    {
        $this->Shell->runCommand('subCommands', []);
        $output = $this->Shell->stdout->output;

        $expected = '';
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subcommands with a nonexisting command returns nothing
     */
    public function testSubCommandsNonExistingCommand()
    {
        $this->Shell->runCommand('subCommands', ['subCommands', 'foo']);
        $output = $this->Shell->stdout->output;

        $expected = '';
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subcommands returns the available subcommands for the given command
     */
    public function testSubCommands()
    {
        $this->Shell->runCommand('subCommands', ['subCommands', 'bake']);
        $output = $this->Shell->stdout->output;

        $expected = "controller db_config fixture model plugin project test view\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that fuzzy returns nothing
     */
    public function testFuzzy()
    {
        $this->Shell->runCommand('fuzzy', []);
        $output = $this->Shell->stdout->output;

        $expected = '';
        $this->assertEquals($expected, $output);
    }
}
