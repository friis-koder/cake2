<?php
/**
 * AclShell Test file
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
 * @since         CakePHP v 1.2.0.7726
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('ShellDispatcher', 'Console');
App::uses('Shell', 'Console');
App::uses('AclShell', 'Console/Command');
App::uses('ComponentCollection', 'Controller');
App::uses('AclComponent', 'Controller/Component');

/**
 * AclShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class AclShellTest extends CakeTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = ['core.aco', 'core.aro', 'core.aros_aco'];

    /**
     * @var AclShell
     */
    private $Task;

    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Acl.database', 'test');
        Configure::write('Acl.classname', 'DbAcl');

        $out = $this->getMock('ConsoleOutput', [], [], '', false);
        $in = $this->getMock('ConsoleInput', [], [], '', false);

        $this->Task = $this->getMock(
            'AclShell',
            ['in', 'out', 'hr', 'createFile', 'error', 'err', 'clear', 'dispatchShell'],
            [$out, $out, $in]
        );
        $collection = new ComponentCollection();
        $this->Task->Acl = new AclComponent($collection);
        $this->Task->params['datasource'] = 'test';
    }

    /**
     * test that model.foreign_key output works when looking at acl rows
     */
    public function testViewWithModelForeignKeyOutput()
    {
        $this->Task->command = 'view';
        $this->Task->startup();
        $data = [
            'parent_id'   => null,
            'model'       => 'MyModel',
            'foreign_key' => 2,
        ];
        $this->Task->Acl->Aro->create($data);
        $this->Task->Acl->Aro->save();
        $this->Task->args[0] = 'aro';

        $this->Task->expects($this->at(0))->method('out')->with('Aro tree:');
        $this->Task->expects($this->at(2))->method('out')
            ->with($this->stringContains('[1] ROOT'));

        $this->Task->expects($this->at(4))->method('out')
            ->with($this->stringContains('[3] Gandalf'));

        $this->Task->expects($this->at(6))->method('out')
            ->with($this->stringContains('[5] MyModel.2'));

        $this->Task->view();
    }

    /**
     * test view with an argument
     */
    public function testViewWithArgument()
    {
        $this->Task->args = ['aro', 'admins'];

        $this->Task->expects($this->at(0))->method('out')->with('Aro tree:');
        $this->Task->expects($this->at(2))->method('out')->with('  [2] admins');
        $this->Task->expects($this->at(3))->method('out')->with('    [3] Gandalf');
        $this->Task->expects($this->at(4))->method('out')->with('    [4] Elrond');

        $this->Task->view();
    }

    /**
     * test the method that splits model.foreign key. and that it returns an array.
     */
    public function testParsingModelAndForeignKey()
    {
        $result = $this->Task->parseIdentifier('Model.foreignKey');
        $expected = ['model' => 'Model', 'foreign_key' => 'foreignKey'];
        $this->assertEquals($expected, $result);

        $result = $this->Task->parseIdentifier('mySuperUser');
        $this->assertEquals('mySuperUser', $result);

        $result = $this->Task->parseIdentifier('111234');
        $this->assertEquals('111234', $result);
    }

    /**
     * test creating aro/aco nodes
     */
    public function testCreate()
    {
        $this->Task->args = ['aro', 'root', 'User.1'];
        $this->Task->expects($this->at(0))->method('out')->with('<success>New Aro</success> \'User.1\' created.', 2);
        $this->Task->expects($this->at(1))->method('out')->with('<success>New Aro</success> \'User.3\' created.', 2);
        $this->Task->expects($this->at(2))->method('out')->with('<success>New Aro</success> \'somealias\' created.', 2);

        $this->Task->create();

        $Aro = ClassRegistry::init('Aro');
        $Aro->cacheQueries = false;
        $result = $Aro->read();
        $this->assertEquals('User', $result['Aro']['model']);
        $this->assertEquals(1, $result['Aro']['foreign_key']);
        $this->assertEquals(null, $result['Aro']['parent_id']);
        $id = $result['Aro']['id'];

        $this->Task->args = ['aro', 'User.1', 'User.3'];
        $this->Task->create();

        $Aro = ClassRegistry::init('Aro');
        $result = $Aro->read();
        $this->assertEquals('User', $result['Aro']['model']);
        $this->assertEquals(3, $result['Aro']['foreign_key']);
        $this->assertEquals($id, $result['Aro']['parent_id']);

        $this->Task->args = ['aro', 'root', 'somealias'];
        $this->Task->create();

        $Aro = ClassRegistry::init('Aro');
        $result = $Aro->read();
        $this->assertEquals('somealias', $result['Aro']['alias']);
        $this->assertEquals(null, $result['Aro']['model']);
        $this->assertEquals(null, $result['Aro']['foreign_key']);
        $this->assertEquals(null, $result['Aro']['parent_id']);
    }

    /**
     * test the delete method with different node types.
     */
    public function testDelete()
    {
        $this->Task->args = ['aro', 'AuthUser.1'];
        $this->Task->expects($this->at(0))->method('out')
            ->with('<success>Aro deleted.</success>', 2);
        $this->Task->delete();

        $Aro = ClassRegistry::init('Aro');
        $result = $Aro->findById(3);
        $this->assertSame([], $result);
    }

    /**
     * test setParent method.
     */
    public function testSetParent()
    {
        $this->Task->args = ['aro', 'AuthUser.2', 'root'];
        $this->Task->setParent();

        $Aro = ClassRegistry::init('Aro');
        $result = $Aro->read(null, 4);
        $this->assertEquals(null, $result['Aro']['parent_id']);
    }

    /**
     * test grant
     */
    public function testGrant()
    {
        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'create'];
        $this->Task->expects($this->at(0))->method('out')
            ->with($this->matchesRegularExpression('/granted/'), true);
        $this->Task->grant();
        $node = $this->Task->Acl->Aro->node(['model' => 'AuthUser', 'foreign_key' => 2]);
        $node = $this->Task->Acl->Aro->read(null, $node[0]['Aro']['id']);

        $this->assertFalse(empty($node['Aco'][0]));
        $this->assertEquals(1, $node['Aco'][0]['Permission']['_create']);
    }

    /**
     * test deny
     */
    public function testDeny()
    {
        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'create'];
        $this->Task->expects($this->at(0))->method('out')
            ->with($this->stringContains('Permission denied'), true);

        $this->Task->deny();

        $node = $this->Task->Acl->Aro->node(['model' => 'AuthUser', 'foreign_key' => 2]);
        $node = $this->Task->Acl->Aro->read(null, $node[0]['Aro']['id']);
        $this->assertFalse(empty($node['Aco'][0]));
        $this->assertEquals(-1, $node['Aco'][0]['Permission']['_create']);
    }

    /**
     * test checking allowed and denied perms
     */
    public function testCheck()
    {
        $this->Task->expects($this->at(0))->method('out')
            ->with($this->matchesRegularExpression('/not allowed/'), true);
        $this->Task->expects($this->at(1))->method('out')
            ->with($this->matchesRegularExpression('/granted/'), true);
        $this->Task->expects($this->at(2))->method('out')
            ->with($this->matchesRegularExpression('/is.*allowed/'), true);
        $this->Task->expects($this->at(3))->method('out')
            ->with($this->matchesRegularExpression('/not.*allowed/'), true);

        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', '*'];
        $this->Task->check();

        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'create'];
        $this->Task->grant();

        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'create'];
        $this->Task->check();

        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'delete'];
        $this->Task->check();
    }

    /**
     * test inherit and that it 0's the permission fields.
     */
    public function testInherit()
    {
        $this->Task->expects($this->at(0))->method('out')
            ->with($this->matchesRegularExpression('/Permission .*granted/'), true);
        $this->Task->expects($this->at(1))->method('out')
            ->with($this->matchesRegularExpression('/Permission .*inherited/'), true);

        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'create'];
        $this->Task->grant();

        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'all'];
        $this->Task->inherit();

        $node = $this->Task->Acl->Aro->node(['model' => 'AuthUser', 'foreign_key' => 2]);
        $node = $this->Task->Acl->Aro->read(null, $node[0]['Aro']['id']);
        $this->assertFalse(empty($node['Aco'][0]));
        $this->assertEquals(0, $node['Aco'][0]['Permission']['_create']);
    }

    /**
     * test getting the path for an aro/aco
     */
    public function testGetPath()
    {
        $this->Task->args = ['aro', 'AuthUser.2'];
        $node = $this->Task->Acl->Aro->node(['model' => 'AuthUser', 'foreign_key' => 2]);
        $first = $node[0]['Aro']['id'];
        $second = $node[1]['Aro']['id'];
        $last = $node[2]['Aro']['id'];
        $this->Task->expects($this->at(2))->method('out')->with('[' . $last . '] ROOT');
        $this->Task->expects($this->at(3))->method('out')->with('  [' . $second . '] admins');
        $this->Task->expects($this->at(4))->method('out')->with('    [' . $first . '] Elrond');
        $this->Task->getPath();
    }

    /**
     * test that initdb makes the correct call.
     */
    public function testInitDb()
    {
        $this->Task->expects($this->once())->method('dispatchShell')
            ->with('schema create DbAcl');

        $this->Task->initdb();
    }
}
