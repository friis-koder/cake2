<?php
/**
 * CakePHP : Rapid Development Framework (https://cakephp.org)
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
App::uses('CommandTask', 'Console/Command/Task');

/**
 * CommandTaskTest class
 *
 * @package   Cake.Test.Case.Console.Command.Task
 */
class CommandTaskTest extends CakeTestCase
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

        $out = $this->getMock('ConsoleOutput', [], [], '', false);
        $in = $this->getMock('ConsoleInput', [], [], '', false);

        $this->CommandTask = $this->getMock(
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
        unset($this->CommandTask);
        CakePlugin::unload();
    }

    /**
     * Test the resulting list of shells
     */
    public function testGetShellList()
    {
        $result = $this->CommandTask->getShellList();

        $expected = [
            'CORE' => [
                'acl',
                'api',
                'bake',
                'command_list',
                'completion',
                'console',
                'i18n',
                'schema',
                'server',
                'test',
                'testsuite',
                'upgrade'
            ],
            'TestPlugin' => [
                'example',
                'test_plugin'
            ],
            'TestPluginTwo' => [
                'example',
                'welcome'
            ],
            'app' => [
                'sample'
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the resulting list of commands
     */
    public function testCommands()
    {
        $result = $this->CommandTask->commands();

        $expected = [
            'TestPlugin.example',
            'TestPlugin.test_plugin',
            'TestPluginTwo.example',
            'TestPluginTwo.welcome',
            'acl',
            'api',
            'bake',
            'command_list',
            'completion',
            'console',
            'i18n',
            'schema',
            'server',
            'test',
            'testsuite',
            'upgrade',
            'sample'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the resulting list of subcommands for the given command
     */
    public function testSubCommands()
    {
        $result = $this->CommandTask->subCommands('acl');

        $expected = [
            'check',
            'create',
            'db_config',
            'delete',
            'deny',
            'getPath',
            'grant',
            'inherit',
            'initdb',
            'nodeExists',
            'parseIdentifier',
            'setParent',
            'view'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that unknown commands return an empty array
     */
    public function testSubCommandsUnknownCommand()
    {
        $result = $this->CommandTask->subCommands('yoghurt');

        $expected = [];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that getting a existing shell returns the shell instance
     */
    public function testGetShell()
    {
        $result = $this->CommandTask->getShell('acl');
        $this->assertInstanceOf('AclShell', $result);
    }

    /**
     * Test that getting a non-existing shell returns false
     */
    public function testGetShellNonExisting()
    {
        $result = $this->CommandTask->getShell('strawberry');
        $this->assertFalse($result);
    }

    /**
     * Test that getting a existing core shell with 'core.' prefix returns the correct shell instance
     */
    public function testGetShellCore()
    {
        $result = $this->CommandTask->getShell('core.bake');
        $this->assertInstanceOf('BakeShell', $result);
    }

    /**
     * Test the options array for a known command
     */
    public function testOptions()
    {
        $result = $this->CommandTask->options('bake');

        $expected = [
            '--help',
            '-h',
            '--verbose',
            '-v',
            '--quiet',
            '-q',
            '--connection',
            '-c',
            '--theme',
            '-t'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the options array for an unknown command
     */
    public function testOptionsUnknownCommand()
    {
        $result = $this->CommandTask->options('pie');

        $expected = [
            '--help',
            '-h',
            '--verbose',
            '-v',
            '--quiet',
            '-q'
        ];
        $this->assertEquals($expected, $result);
    }
}
