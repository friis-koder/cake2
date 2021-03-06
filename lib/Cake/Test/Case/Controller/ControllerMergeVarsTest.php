<?php
/**
 * Controller Merge vars Test file
 *
 * Isolated from the Controller and Component test as to not pollute their AppController class
 *
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
 * @package       Cake.Test.Case.Controller
 *
 * @since         CakePHP(tm) v 1.2.3
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Controller', 'Controller');

/**
 * Test case AppController
 *
 * @package       Cake.Test.Case.Controller
 */
class MergeVarsAppController extends Controller
{
    /**
     * components
     *
     * @var array
     */
    public $components = ['MergeVar' => ['flag', 'otherFlag', 'redirect' => false]];

    /**
     * helpers
     *
     * @var array
     */
    public $helpers = ['MergeVar' => ['format' => 'html', 'terse']];
}

/**
 * MergeVar Component
 *
 * @package       Cake.Test.Case.Controller
 */
class MergeVarComponent extends CakeObject
{
}

/**
 * Additional controller for testing
 *
 * @package       Cake.Test.Case.Controller
 */
class MergeVariablesController extends MergeVarsAppController
{
    /**
     * uses
     *
     * @var arrays
     */
    public $uses = [];

    /**
     * parent for mergeVars
     *
     * @var string
     */
    protected $_mergeParent = 'MergeVarsAppController';
}

/**
 * MergeVarPlugin App Controller
 *
 * @package       Cake.Test.Case.Controller
 */
class MergeVarPluginAppController extends MergeVarsAppController
{
    /**
     * components
     *
     * @var array
     */
    public $components = ['Auth' => ['setting' => 'val', 'otherVal']];

    /**
     * helpers
     *
     * @var array
     */
    public $helpers = ['Js'];

    /**
     * parent for mergeVars
     *
     * @var string
     */
    protected $_mergeParent = 'MergeVarsAppController';
}

/**
 * MergePostsController
 *
 * @package       Cake.Test.Case.Controller
 */
class MergePostsController extends MergeVarPluginAppController
{
    /**
     * uses
     *
     * @var array
     */
    public $uses = [];
}

/**
 * Test Case for Controller Merging of Vars.
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerMergeVarsTest extends CakeTestCase
{
    /**
     * test that component settings are not duplicated when merging component settings
     */
    public function testComponentParamMergingNoDuplication()
    {
        $Controller = new MergeVariablesController();
        $Controller->constructClasses();

        $expected = ['MergeVar' => ['flag', 'otherFlag', 'redirect' => false]];
        $this->assertEquals($expected, $Controller->components, 'Duplication of settings occurred. %s');
    }

    /**
     * test component merges with redeclared components
     */
    public function testComponentMergingWithRedeclarations()
    {
        $Controller = new MergeVariablesController();
        $Controller->components['MergeVar'] = ['remote', 'redirect' => true];
        $Controller->constructClasses();

        $expected = ['MergeVar' => ['flag', 'otherFlag', 'redirect' => true, 'remote']];
        $this->assertEquals($expected, $Controller->components, 'Merging of settings is wrong. %s');
    }

    /**
     * test merging of helpers array, ensure no duplication occurs
     */
    public function testHelperSettingMergingNoDuplication()
    {
        $Controller = new MergeVariablesController();
        $Controller->constructClasses();

        $expected = ['MergeVar' => ['format' => 'html', 'terse']];
        $this->assertEquals($expected, $Controller->helpers, 'Duplication of settings occurred. %s');
    }

    /**
     * Test that helpers declared in appcontroller come before those in the subclass
     * orderwise
     */
    public function testHelperOrderPrecedence()
    {
        $Controller = new MergeVariablesController();
        $Controller->helpers = ['Custom', 'Foo' => ['something']];
        $Controller->constructClasses();

        $expected = [
            'MergeVar' => ['format' => 'html', 'terse'],
            'Custom'   => null,
            'Foo'      => ['something']
        ];
        $this->assertSame($expected, $Controller->helpers, 'Order is incorrect.');
    }

    /**
     * test merging of vars with plugin
     */
    public function testMergeVarsWithPlugin()
    {
        $Controller = new MergePostsController();
        $Controller->components = ['Email' => ['ports' => 'open']];
        $Controller->plugin = 'MergeVarPlugin';
        $Controller->constructClasses();

        $expected = [
            'MergeVar' => ['flag', 'otherFlag', 'redirect' => false],
            'Auth'     => ['setting' => 'val', 'otherVal'],
            'Email'    => ['ports' => 'open']
        ];
        $this->assertEquals($expected, $Controller->components, 'Components are unexpected.');

        $expected = [
            'MergeVar' => ['format' => 'html', 'terse'],
            'Js'       => null
        ];
        $this->assertEquals($expected, $Controller->helpers, 'Helpers are unexpected.');

        $Controller = new MergePostsController();
        $Controller->components = [];
        $Controller->plugin = 'MergeVarPlugin';
        $Controller->constructClasses();

        $expected = [
            'MergeVar' => ['flag', 'otherFlag', 'redirect' => false],
            'Auth'     => ['setting' => 'val', 'otherVal'],
        ];
        $this->assertEquals($expected, $Controller->components, 'Components are unexpected.');
    }

    /**
     * Ensure that _mergeControllerVars is not being greedy and merging with
     * AppController when you make an instance of Controller
     */
    public function testMergeVarsNotGreedy()
    {
        $Controller = new Controller();
        $Controller->components = [];
        $Controller->uses = [];
        $Controller->constructClasses();

        $this->assertFalse(isset($Controller->Session));
    }

    /**
     * Ensure that $modelClass is correct even when Controller::$uses
     * has been iterated, eg: by a Component, or event handlers.
     */
    public function testMergeVarsModelClass()
    {
        $Controller = new MergeVariablescontroller();
        $Controller->uses = ['Test', 'TestAlias'];
        $Controller->constructClasses();
        $this->assertEquals($Controller->uses[0], $Controller->modelClass);
    }
}
