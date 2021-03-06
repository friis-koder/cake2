<?php
/**
 * ScaffoldViewTest file
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
 * @package       Cake.Test.Case.View
 *
 * @since         CakePHP(tm) v 2.0
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Controller', 'Controller');
App::uses('Scaffold', 'Controller');
App::uses('ScaffoldView', 'View');
App::uses('AppModel', 'Model');

require_once dirname(dirname(__FILE__)) . DS . 'Model' . DS . 'models.php';

/**
 * TestScaffoldView class
 *
 * @package       Cake.Test.Case.Controller
 */
class TestScaffoldView extends ScaffoldView
{
    /**
     * testGetFilename method
     *
     * @param string $action
     */
    public function testGetFilename($action)
    {
        return $this->_getViewFileName($action);
    }
}

/**
 * ScaffoldViewMockController class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldViewMockController extends Controller
{
    /**
     * name property
     *
     * @var string
     */
    public $name = 'ScaffoldMock';

    /**
     * scaffold property
     *
     * @var mixed
     */
    public $scaffold;
}

/**
 * ScaffoldViewTest class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldViewTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['core.article', 'core.user', 'core.comment', 'core.join_thing', 'core.tag'];

    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();
        $this->request = new CakeRequest(null, false);
        $this->Controller = new ScaffoldViewMockController($this->request);
        $this->Controller->response = $this->getMock('CakeResponse', ['_sendHeader']);

        App::build([
            'View'   => [CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS],
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ]);
        CakePlugin::load('TestPlugin');
    }

    /**
     * tearDown method
     */
    public function tearDown()
    {
        unset($this->Controller, $this->request);
        parent::tearDown();
    }

    /**
     * testGetViewFilename method
     */
    public function testGetViewFilename()
    {
        $_admin = Configure::read('Routing.prefixes');
        Configure::write('Routing.prefixes', ['admin']);

        $this->Controller->request->params['action'] = 'index';
        $ScaffoldView = new TestScaffoldView($this->Controller);
        $result = $ScaffoldView->testGetFilename('index');
        $expected = CAKE . 'View' . DS . 'Scaffolds' . DS . 'index.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('edit');
        $expected = CAKE . 'View' . DS . 'Scaffolds' . DS . 'form.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('add');
        $expected = CAKE . 'View' . DS . 'Scaffolds' . DS . 'form.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('view');
        $expected = CAKE . 'View' . DS . 'Scaffolds' . DS . 'view.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('admin_index');
        $expected = CAKE . 'View' . DS . 'Scaffolds' . DS . 'index.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('admin_view');
        $expected = CAKE . 'View' . DS . 'Scaffolds' . DS . 'view.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('admin_edit');
        $expected = CAKE . 'View' . DS . 'Scaffolds' . DS . 'form.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('admin_add');
        $expected = CAKE . 'View' . DS . 'Scaffolds' . DS . 'form.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('error');
        $expected = CAKE . 'View' . DS . 'Errors' . DS . 'scaffold_error.ctp';
        $this->assertEquals($expected, $result);

        $Controller = new ScaffoldViewMockController($this->request);
        $Controller->scaffold = 'admin';
        $Controller->viewPath = 'Posts';
        $Controller->request['action'] = 'admin_edit';

        $ScaffoldView = new TestScaffoldView($Controller);
        $result = $ScaffoldView->testGetFilename('admin_edit');
        $expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Posts' . DS . 'scaffold.form.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('edit');
        $expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Posts' . DS . 'scaffold.form.ctp';
        $this->assertEquals($expected, $result);

        $Controller = new ScaffoldViewMockController($this->request);
        $Controller->scaffold = 'admin';
        $Controller->viewPath = 'Tests';
        $Controller->request->addParams([
            'plugin' => 'test_plugin',
            'action' => 'admin_add',
            'admin'  => true
        ]);
        $Controller->plugin = 'TestPlugin';

        $ScaffoldView = new TestScaffoldView($Controller);
        $result = $ScaffoldView->testGetFilename('admin_add');
        $expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' .
            DS . 'TestPlugin' . DS . 'View' . DS . 'Tests' . DS . 'scaffold.form.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('add');
        $expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' .
            DS . 'TestPlugin' . DS . 'View' . DS . 'Tests' . DS . 'scaffold.form.ctp';
        $this->assertEquals($expected, $result);

        Configure::write('Routing.prefixes', $_admin);
    }

    /**
     * test getting the view file name for themed scaffolds.
     */
    public function testGetViewFileNameWithTheme()
    {
        $this->Controller->request['action'] = 'index';
        $this->Controller->viewPath = 'Posts';
        $this->Controller->theme = 'TestTheme';
        $ScaffoldView = new TestScaffoldView($this->Controller);

        $result = $ScaffoldView->testGetFilename('index');
        $expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS .
            'Themed' . DS . 'TestTheme' . DS . 'Posts' . DS . 'scaffold.index.ctp';
        $this->assertEquals($expected, $result);
    }

    /**
     * test default index scaffold generation
     */
    public function testIndexScaffold()
    {
        $params = [
            'plugin'     => null,
            'pass'       => [],
            'form'       => [],
            'named'      => [],
            'url'        => ['url' => 'scaffold_mock'],
            'controller' => 'scaffold_mock',
            'action'     => 'index',
        ];
        $this->Controller->request->addParams($params);
        $this->Controller->request->webroot = '/';
        $this->Controller->request->base = '';
        $this->Controller->request->here = '/scaffold_mock/index';

        //set router.
        Router::reload();
        Router::setRequestInfo($this->Controller->request);

        $this->Controller->constructClasses();
        ob_start();
        new Scaffold($this->Controller, $this->Controller->request);
        $this->Controller->response->send();
        $result = ob_get_clean();

        $this->assertRegExp('#<h2>Scaffold Mock</h2>#', $result);
        $this->assertRegExp('#<table cellpadding="0" cellspacing="0">#', $result);

        $this->assertRegExp('#<a href="/scaffold_users/view/1">1</a>#', $result); //belongsTo links
        $this->assertRegExp('#<li><a href="/scaffold_mock/add">New Scaffold Mock</a></li>#', $result);
        $this->assertRegExp('#<li><a href="/scaffold_users">List Scaffold Users</a></li>#', $result);
        $this->assertRegExp('#<li><a href="/scaffold_comments/add">New Comment</a></li>#', $result);
    }

    /**
     * test default view scaffold generation
     */
    public function testViewScaffold()
    {
        $this->Controller->request->base = '';
        $this->Controller->request->here = '/scaffold_mock';
        $this->Controller->request->webroot = '/';
        $params = [
            'plugin'     => null,
            'pass'       => [1],
            'form'       => [],
            'named'      => [],
            'url'        => ['url' => 'scaffold_mock/view/1'],
            'controller' => 'scaffold_mock',
            'action'     => 'view',
        ];
        $this->Controller->request->addParams($params);

        //set router.
        Router::reload();
        Router::setRequestInfo($this->Controller->request);
        $this->Controller->constructClasses();

        ob_start();
        new Scaffold($this->Controller, $this->Controller->request);
        $this->Controller->response->send();
        $result = ob_get_clean();

        $this->assertRegExp('/<h2>View Scaffold Mock<\/h2>/', $result);
        $this->assertRegExp('/<dl>/', $result);

        $this->assertRegExp('/<a href="\/scaffold_users\/view\/1">1<\/a>/', $result); //belongsTo links
        $this->assertRegExp('/<li><a href="\/scaffold_mock\/edit\/1">Edit Scaffold Mock<\/a>\s<\/li>/', $result);
        $this->assertRegExp('/<a href="\#" onclick="if[^>]*>Delete Scaffold Mock<\/a>\s<\/li>/', $result);
        //check related table
        $this->assertRegExp('/<div class="related">\s*<h3>Related Scaffold Comments<\/h3>\s*<table cellpadding="0" cellspacing="0">/', $result);
        $this->assertRegExp('/<li><a href="\/scaffold_comments\/add">New Comment<\/a><\/li>/', $result);
        $this->assertNotRegExp('/<th>JoinThing<\/th>/', $result);
    }

    /**
     * test default view scaffold generation
     */
    public function testEditScaffold()
    {
        $this->Controller->request->base = '';
        $this->Controller->request->webroot = '/';
        $this->Controller->request->here = '/scaffold_mock/edit/1';

        $params = [
            'plugin'     => null,
            'pass'       => [1],
            'form'       => [],
            'named'      => [],
            'url'        => ['url' => 'scaffold_mock'],
            'controller' => 'scaffold_mock',
            'action'     => 'edit',
        ];
        $this->Controller->request->addParams($params);

        //set router.
        Router::reload();
        Router::setRequestInfo($this->Controller->request);
        $this->Controller->constructClasses();

        ob_start();
        new Scaffold($this->Controller, $this->Controller->request);
        $this->Controller->response->send();
        $result = ob_get_clean();

        $this->assertContains('<form action="/scaffold_mock/edit/1" id="ScaffoldMockEditForm" method="post"', $result);
        $this->assertContains('<legend>Edit Scaffold Mock</legend>', $result);

        $this->assertContains('input type="hidden" name="data[ScaffoldMock][id]" value="1" id="ScaffoldMockId"', $result);
        $this->assertContains('select name="data[ScaffoldMock][user_id]" id="ScaffoldMockUserId"', $result);
        $this->assertContains('input name="data[ScaffoldMock][title]" maxlength="255" type="text" value="First Article" id="ScaffoldMockTitle"', $result);
        $this->assertContains('input name="data[ScaffoldMock][published]" maxlength="1" type="text" value="Y" id="ScaffoldMockPublished"', $result);
        $this->assertContains('textarea name="data[ScaffoldMock][body]" cols="30" rows="6" id="ScaffoldMockBody"', $result);
        $this->assertRegExp('/<a href="\#" onclick="if[^>]*>Delete<\/a><\/li>/', $result);
    }

    /**
     * Test Admin Index Scaffolding.
     */
    public function testAdminIndexScaffold()
    {
        $_backAdmin = Configure::read('Routing.prefixes');

        Configure::write('Routing.prefixes', ['admin']);
        $params = [
            'plugin'     => null,
            'pass'       => [],
            'form'       => [],
            'named'      => [],
            'prefix'     => 'admin',
            'url'        => ['url' => 'admin/scaffold_mock'],
            'controller' => 'scaffold_mock',
            'action'     => 'admin_index',
            'admin'      => 1,
        ];
        $this->Controller->request->base = '';
        $this->Controller->request->webroot = '/';
        $this->Controller->request->here = '/admin/scaffold_mock';
        $this->Controller->request->addParams($params);

        //reset, and set router.
        Router::reload();
        Router::setRequestInfo($this->Controller->request);

        $this->Controller->scaffold = 'admin';
        $this->Controller->constructClasses();

        ob_start();
        new Scaffold($this->Controller, $this->Controller->request);
        $this->Controller->response->send();
        $result = ob_get_clean();

        $this->assertRegExp('/<h2>Scaffold Mock<\/h2>/', $result);
        $this->assertRegExp('/<table cellpadding="0" cellspacing="0">/', $result);

        $this->assertRegExp('/<li><a href="\/admin\/scaffold_mock\/add">New Scaffold Mock<\/a><\/li>/', $result);

        Configure::write('Routing.prefixes', $_backAdmin);
    }

    /**
     * Test Admin Index Scaffolding.
     */
    public function testAdminEditScaffold()
    {
        Configure::write('Routing.prefixes', ['admin']);
        $params = [
            'plugin'     => null,
            'pass'       => [1],
            'form'       => [],
            'named'      => [],
            'prefix'     => 'admin',
            'url'        => ['url' => 'admin/scaffold_mock/edit/1'],
            'controller' => 'scaffold_mock',
            'action'     => 'admin_edit',
            'admin'      => 1,
        ];
        $this->Controller->request->base = '';
        $this->Controller->request->webroot = '/';
        $this->Controller->request->here = '/admin/scaffold_mock/edit/1';
        $this->Controller->request->addParams($params);

        //reset, and set router.
        Router::reload();
        Router::setRequestInfo($this->Controller->request);

        $this->Controller->scaffold = 'admin';
        $this->Controller->constructClasses();

        ob_start();
        new Scaffold($this->Controller, $this->Controller->request);
        $this->Controller->response->send();
        $result = ob_get_clean();

        $this->assertRegExp('#admin/scaffold_mock/edit/1#', $result);
        $this->assertRegExp('#Scaffold Mock#', $result);
    }

    /**
     * Test Admin Index Scaffolding.
     */
    public function testMultiplePrefixScaffold()
    {
        $_backAdmin = Configure::read('Routing.prefixes');

        Configure::write('Routing.prefixes', ['admin', 'member']);
        $params = [
            'plugin'     => null,
            'pass'       => [],
            'form'       => [],
            'named'      => [],
            'prefix'     => 'member',
            'url'        => ['url' => 'member/scaffold_mock'],
            'controller' => 'scaffold_mock',
            'action'     => 'member_index',
            'member'     => 1,
        ];
        $this->Controller->request->base = '';
        $this->Controller->request->webroot = '/';
        $this->Controller->request->here = '/member/scaffold_mock';
        $this->Controller->request->addParams($params);

        //reset, and set router.
        Router::reload();
        Router::setRequestInfo($this->Controller->request);

        $this->Controller->scaffold = 'member';
        $this->Controller->constructClasses();

        ob_start();
        new Scaffold($this->Controller, $this->Controller->request);
        $this->Controller->response->send();
        $result = ob_get_clean();

        $this->assertRegExp('/<h2>Scaffold Mock<\/h2>/', $result);
        $this->assertRegExp('/<table cellpadding="0" cellspacing="0">/', $result);

        $this->assertRegExp('/<li><a href="\/member\/scaffold_mock\/add">New Scaffold Mock<\/a><\/li>/', $result);

        Configure::write('Routing.prefixes', $_backAdmin);
    }
}
