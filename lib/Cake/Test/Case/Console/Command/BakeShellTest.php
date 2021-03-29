<?php
/**
 * BakeShell Test Case
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * @link          https://cakephp.org CakePHP(tm) Project
 *
 * @package       Cake.Test.Case.Console.Command
 *
 * @since         CakePHP(tm) v 1.3
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('ShellDispatcher', 'Console');
App::uses('Shell', 'Console');
App::uses('BakeShell', 'Console/Command');
App::uses('ModelTask', 'Console/Command/Task');
App::uses('ControllerTask', 'Console/Command/Task');
App::uses('DbConfigTask', 'Console/Command/Task');
App::uses('Controller', 'Controller');

if (!class_exists('UsersController')) {
    class UsersController extends Controller
    {
    }
}

class BakeShellTest extends CakeTestCase
{
    /**
     * fixtures
     *
     * @var array
     */
    public $fixtures = ['core.user'];

    /**
     * setup test
     */
    public function setUp()
    {
        parent::setUp();
        $out = $this->getMock('ConsoleOutput', [], [], '', false);
        $in = $this->getMock('ConsoleInput', [], [], '', false);

        $this->Shell = $this->getMock(
            'BakeShell',
            ['in', 'out', 'hr', 'err', 'createFile', '_stop', '_checkUnitTest'],
            [$out, $out, $in]
        );
    }

    /**
     * tearDown method
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Dispatch, $this->Shell);
    }

    /**
     * test bake all
     */
    public function testAllWithModelName()
    {
        App::uses('User', 'Model');
        $userExists = class_exists('User');
        $this->skipIf($userExists, 'User class exists, cannot test `bake all [param]`.');

        $this->Shell->Model = $this->getMock('ModelTask', [], [&$this->Dispatcher]);
        $this->Shell->Controller = $this->getMock('ControllerTask', [], [&$this->Dispatcher]);
        $this->Shell->View = $this->getMock('ModelTask', [], [&$this->Dispatcher]);
        $this->Shell->DbConfig = $this->getMock('DbConfigTask', [], [&$this->Dispatcher]);

        $this->Shell->DbConfig->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue('test'));

        $this->Shell->Model->expects($this->never())
            ->method('getName');

        $this->Shell->Model->expects($this->once())
            ->method('bake')
            ->will($this->returnValue(true));

        $this->Shell->Controller->expects($this->once())
            ->method('bake')
            ->will($this->returnValue(true));

        $this->Shell->View->expects($this->once())
            ->method('execute');

        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->expects($this->at(0))
            ->method('out')
            ->with('Bake All');

        $this->Shell->expects($this->at(5))
            ->method('out')
            ->with('<success>Bake All complete</success>');

        $this->Shell->connection = '';
        $this->Shell->params = [];
        $this->Shell->args = ['User'];
        $this->Shell->all();

        $this->assertEquals('User', $this->Shell->View->args[0]);
    }
}
