<?php
/**
 * ControllerTask Test Case
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
 * @package       Cake.Test.Case.Console.Command.Task
 *
 * @since         CakePHP(tm) v 1.3
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('ShellDispatcher', 'Console');
App::uses('Shell', 'Console');
App::uses('CakeSchema', 'Model');
App::uses('ClassRegistry', 'Utility');
App::uses('Helper', 'View/Helper');
App::uses('ProjectTask', 'Console/Command/Task');
App::uses('ControllerTask', 'Console/Command/Task');
App::uses('ModelTask', 'Console/Command/Task');
App::uses('TemplateTask', 'Console/Command/Task');
App::uses('TestTask', 'Console/Command/Task');
App::uses('Model', 'Model');

App::uses('BakeArticle', 'Model');
App::uses('BakeComment', 'Model');
App::uses('BakeTags', 'Model');
$imported = class_exists('BakeArticle') || class_exists('BakeComment') || class_exists('BakeTag');

if (!$imported) {
    define('ARTICLE_MODEL_CREATED', true);

    /**
     * BakeArticle
     */
    class BakeArticle extends Model
    {
        public $hasMany = ['BakeComment'];

        public $hasAndBelongsToMany = ['BakeTag'];
    }
}

/**
 * ControllerTaskTest class
 *
 * @package       Cake.Test.Case.Console.Command.Task
 */
class ControllerTaskTest extends CakeTestCase
{
    /**
     * fixtures
     *
     * @var array
     */
    public $fixtures = ['core.bake_article', 'core.bake_articles_bake_tag', 'core.bake_comment', 'core.bake_tag'];

    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();
        $out = $this->getMock('ConsoleOutput', [], [], '', false);
        $in = $this->getMock('ConsoleInput', [], [], '', false);
        $this->Task = $this->getMock(
            'ControllerTask',
            ['in', 'out', 'err', 'hr', 'createFile', '_stop', '_checkUnitTest'],
            [$out, $out, $in]
        );
        $this->Task->name = 'Controller';
        $this->Task->Template = new TemplateTask($out, $out, $in);
        $this->Task->Template->params['theme'] = 'default';

        $this->Task->Model = $this->getMock(
            'ModelTask',
            ['in', 'out', 'err', 'createFile', '_stop', '_checkUnitTest'],
            [$out, $out, $in]
        );
        $this->Task->Project = $this->getMock(
            'ProjectTask',
            ['in', 'out', 'err', 'createFile', '_stop', '_checkUnitTest', 'getPrefix'],
            [$out, $out, $in]
        );
        $this->Task->Test = $this->getMock('TestTask', [], [$out, $out, $in]);

        if (!defined('ARTICLE_MODEL_CREATED')) {
            $this->markTestSkipped('Could not run as an Article, Tag or Comment model was already loaded.');
        }
    }

    /**
     * tearDown method
     */
    public function tearDown()
    {
        unset($this->Task);
        ClassRegistry::flush();
        App::build();
        parent::tearDown();
    }

    /**
     * test ListAll
     */
    public function testListAll()
    {
        $count = count($this->Task->listAll('test'));
        if ($count != count($this->fixtures)) {
            $this->markTestSkipped('Additional tables detected.');
        }

        $this->Task->connection = 'test';
        $this->Task->interactive = true;
        $this->Task->expects($this->at(2))->method('out')->with(' 1. BakeArticles');
        $this->Task->expects($this->at(3))->method('out')->with(' 2. BakeArticlesBakeTags');
        $this->Task->expects($this->at(4))->method('out')->with(' 3. BakeComments');
        $this->Task->expects($this->at(5))->method('out')->with(' 4. BakeTags');

        $expected = ['BakeArticles', 'BakeArticlesBakeTags', 'BakeComments', 'BakeTags'];
        $result = $this->Task->listAll('test');
        $this->assertEquals($expected, $result);

        $this->Task->interactive = false;
        $result = $this->Task->listAll();

        $expected = ['bake_articles', 'bake_articles_bake_tags', 'bake_comments', 'bake_tags'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that getName interacts with the user and returns the controller name.
     */
    public function testGetNameValidIndex()
    {
        $count = count($this->Task->listAll('test'));
        if ($count != count($this->fixtures)) {
            $this->markTestSkipped('Additional tables detected.');
        }
        $this->Task->interactive = true;
        $this->Task->expects($this->any())->method('in')->will(
            $this->onConsecutiveCalls(3, 1)
        );

        $result = $this->Task->getName('test');
        $expected = 'BakeComments';
        $this->assertEquals($expected, $result);

        $result = $this->Task->getName('test');
        $expected = 'BakeArticles';
        $this->assertEquals($expected, $result);
    }

    /**
     * test getting invalid indexes.
     */
    public function testGetNameInvalidIndex()
    {
        $this->Task->interactive = true;
        $this->Task->expects($this->any())->method('in')
            ->will($this->onConsecutiveCalls(50, 'q'));

        $this->Task->expects($this->once())->method('err');
        $this->Task->expects($this->once())->method('_stop');

        $this->Task->getName('test');
    }

    /**
     * test helper interactions
     */
    public function testDoHelpersNo()
    {
        $this->Task->expects($this->any())->method('in')->will($this->returnValue('n'));
        $result = $this->Task->doHelpers();
        $this->assertSame([], $result);
    }

    /**
     * test getting helper values
     */
    public function testDoHelpersTrailingSpace()
    {
        $this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
        $this->Task->expects($this->at(1))->method('in')->will($this->returnValue(' Text, Number, CustomOne  '));
        $result = $this->Task->doHelpers();
        $expected = ['Text', 'Number', 'CustomOne'];
        $this->assertEquals($expected, $result);
    }

    /**
     * test doHelpers with extra commas
     */
    public function testDoHelpersTrailingCommas()
    {
        $this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
        $this->Task->expects($this->at(1))->method('in')->will($this->returnValue(' Text, Number, CustomOne, , '));
        $result = $this->Task->doHelpers();
        $expected = ['Text', 'Number', 'CustomOne'];
        $this->assertEquals($expected, $result);
    }

    /**
     * test component interactions
     */
    public function testDoComponentsNo()
    {
        $this->Task->expects($this->any())->method('in')->will($this->returnValue('n'));
        $result = $this->Task->doComponents();
        $this->assertSame(['Paginator'], $result);
    }

    /**
     * test components with spaces
     */
    public function testDoComponentsTrailingSpaces()
    {
        $this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
        $this->Task->expects($this->at(1))->method('in')->will($this->returnValue(' RequestHandler, Security  '));

        $result = $this->Task->doComponents();
        $expected = ['Paginator', 'RequestHandler', 'Security'];
        $this->assertEquals($expected, $result);
    }

    /**
     * test components with commas
     */
    public function testDoComponentsTrailingCommas()
    {
        $this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
        $this->Task->expects($this->at(1))->method('in')->will($this->returnValue(' RequestHandler, Security, , '));

        $result = $this->Task->doComponents();
        $expected = ['Paginator', 'RequestHandler', 'Security'];
        $this->assertEquals($expected, $result);
    }

    /**
     * test Confirming controller user interaction
     */
    public function testConfirmController()
    {
        $controller = 'Posts';
        $scaffold = false;
        $helpers = ['Js', 'Time'];
        $components = ['Acl', 'Auth'];

        $this->Task->expects($this->at(4))->method('out')->with("Controller Name:\n\t$controller");
        $this->Task->expects($this->at(5))->method('out')->with("Helpers:\n\tJs, Time");
        $this->Task->expects($this->at(6))->method('out')->with("Components:\n\tAcl, Auth");
        $this->Task->confirmController($controller, $scaffold, $helpers, $components);
    }

    /**
     * test the bake method
     */
    public function testBake()
    {
        $helpers = ['Js', 'Time'];
        $components = ['Acl', 'Auth'];
        $this->Task->expects($this->any())->method('createFile')->will($this->returnValue(true));

        $result = $this->Task->bake('Articles', null, $helpers, $components);
        $expected = file_get_contents(CAKE . 'Test' . DS . 'bake_compare' . DS . 'Controller' . DS . 'NoActions.ctp');
        $this->assertTextEquals($expected, $result);

        $result = $this->Task->bake('Articles', null, [], []);
        $expected = file_get_contents(CAKE . 'Test' . DS . 'bake_compare' . DS . 'Controller' . DS . 'NoHelpersOrComponents.ctp');
        $this->assertTextEquals($expected, $result);

        $result = $this->Task->bake('Articles', 'scaffold', $helpers, $components);
        $expected = file_get_contents(CAKE . 'Test' . DS . 'bake_compare' . DS . 'Controller' . DS . 'Scaffold.ctp');
        $this->assertTextEquals($expected, $result);
    }

    /**
     * test bake() with a -plugin param
     */
    public function testBakeWithPlugin()
    {
        $this->Task->plugin = 'ControllerTest';

        //fake plugin path
        CakePlugin::load('ControllerTest', ['path' => APP . 'Plugin' . DS . 'ControllerTest' . DS]);
        $path = APP . 'Plugin' . DS . 'ControllerTest' . DS . 'Controller' . DS . 'ArticlesController.php';

        $this->Task->expects($this->at(1))->method('createFile')->with(
            $path,
            new PHPUnit_Framework_Constraint_IsAnything()
        );
        $this->Task->expects($this->at(3))->method('createFile')->with(
            $path,
            $this->stringContains('ArticlesController extends ControllerTestAppController')
        )->will($this->returnValue(true));

        $this->Task->bake('Articles', '--actions--', [], [], []);

        $this->Task->plugin = 'ControllerTest';
        $path = APP . 'Plugin' . DS . 'ControllerTest' . DS . 'Controller' . DS . 'ArticlesController.php';
        $result = $this->Task->bake('Articles', '--actions--', [], [], []);

        $this->assertContains('App::uses(\'ControllerTestAppController\', \'ControllerTest.Controller\');', $result);
        $this->assertEquals('ControllerTest', $this->Task->Template->templateVars['plugin']);
        $this->assertEquals('ControllerTest.', $this->Task->Template->templateVars['pluginPath']);

        CakePlugin::unload();
    }

    /**
     * test that bakeActions is creating the correct controller Code. (Using sessions)
     */
    public function testBakeActionsUsingSessions()
    {
        $result = $this->Task->bakeActions('BakeArticles', null, true);
        $expected = file_get_contents(CAKE . 'Test' . DS . 'bake_compare' . DS . 'Controller' . DS . 'ActionsUsingSessions.ctp');
        $this->assertTextEquals($expected, $result);

        $result = $this->Task->bakeActions('BakeArticles', 'admin_', true);
        $this->assertContains('function admin_index() {', $result);
        $this->assertContains('function admin_add()', $result);
        $this->assertContains('function admin_view($id = null)', $result);
        $this->assertContains('function admin_edit($id = null)', $result);
        $this->assertContains('function admin_delete($id = null)', $result);
    }

    /**
     * Test baking with Controller::flash() or no sessions.
     */
    public function testBakeActionsWithNoSessions()
    {
        $result = $this->Task->bakeActions('BakeArticles', null, false);
        $expected = file_get_contents(CAKE . 'Test' . DS . 'bake_compare' . DS . 'Controller' . DS . 'ActionsWithNoSessions.ctp');
        $this->assertTextEquals($expected, $result);
    }

    /**
     * test baking a test
     */
    public function testBakeTest()
    {
        $this->Task->plugin = 'ControllerTest';
        $this->Task->connection = 'test';
        $this->Task->interactive = false;

        $this->Task->Test->expects($this->once())->method('bake')->with('Controller', 'BakeArticles');
        $this->Task->bakeTest('BakeArticles');

        $this->assertEquals($this->Task->plugin, $this->Task->Test->plugin);
        $this->assertEquals($this->Task->connection, $this->Task->Test->connection);
        $this->assertEquals($this->Task->interactive, $this->Task->Test->interactive);
    }

    /**
     * test Interactive mode.
     */
    public function testInteractive()
    {
        $count = count($this->Task->listAll('test'));
        if ($count != count($this->fixtures)) {
            $this->markTestSkipped('Additional tables detected.');
        }

        $this->Task->connection = 'test';
        $this->Task->path = '/my/path/';

        $this->Task->expects($this->any())->method('in')
            ->will($this->onConsecutiveCalls(
                '1',
                'y', // build interactive
                'n', // build no scaffolds
                'y', // build normal methods
                'n', // build admin methods
                'n', // helpers?
                'n', // components?
                'y', // sessions ?
                'y' // looks good?
            ));

        $filename = '/my/path/BakeArticlesController.php';
        $this->Task->expects($this->once())->method('createFile')->with(
            $filename,
            $this->stringContains('class BakeArticlesController')
        );
        $this->Task->execute();
    }

    /**
     * test Interactive mode.
     */
    public function testInteractiveAdminMethodsNotInteractive()
    {
        $count = count($this->Task->listAll('test'));
        if ($count != count($this->fixtures)) {
            $this->markTestSkipped('Additional tables detected.');
        }

        $this->Task->connection = 'test';
        $this->Task->interactive = true;
        $this->Task->path = '/my/path/';

        $this->Task->expects($this->any())->method('in')
            ->will($this->onConsecutiveCalls(
                '1',
                'y', // build interactive
                'n', // build no scaffolds
                'y', // build normal methods
                'y', // build admin methods
                'n', // helpers?
                'n', // components?
                'y', // sessions ?
                'y' // looks good?
            ));

        $this->Task->Project->expects($this->any())
            ->method('getPrefix')
            ->will($this->returnValue('admin_'));

        $filename = '/my/path/BakeArticlesController.php';
        $this->Task->expects($this->once())->method('createFile')->with(
            $filename,
            $this->stringContains('class BakeArticlesController')
        )->will($this->returnValue(true));

        $result = $this->Task->execute();
        $this->assertRegExp('/admin_index/', $result);
    }

    /**
     * test that execute runs all when the first arg == all
     */
    public function testExecuteIntoAll()
    {
        $count = count($this->Task->listAll('test'));
        if ($count != count($this->fixtures)) {
            $this->markTestSkipped('Additional tables detected.');
        }

        $this->Task->connection = 'test';
        $this->Task->path = '/my/path/';
        $this->Task->args = ['all'];

        $this->Task->expects($this->any())->method('_checkUnitTest')->will($this->returnValue(true));
        $this->Task->Test->expects($this->once())->method('bake');

        $filename = '/my/path/BakeArticlesController.php';
        $this->Task->expects($this->once())->method('createFile')->with(
            $filename,
            $this->stringContains('class BakeArticlesController')
        )->will($this->returnValue(true));

        $this->Task->execute();
    }

    /**
     * Test execute() with all and --admin
     */
    public function testExecuteIntoAllAdmin()
    {
        $count = count($this->Task->listAll('test'));
        if ($count != count($this->fixtures)) {
            $this->markTestSkipped('Additional tables detected.');
        }

        $this->Task->connection = 'test';
        $this->Task->path = '/my/path/';
        $this->Task->args = ['all'];
        $this->Task->params['admin'] = true;

        $this->Task->Project->expects($this->any())
            ->method('getPrefix')
            ->will($this->returnValue('admin_'));
        $this->Task->expects($this->any())
            ->method('_checkUnitTest')
            ->will($this->returnValue(true));
        $this->Task->Test->expects($this->once())->method('bake');

        $filename = '/my/path/BakeArticlesController.php';
        $this->Task->expects($this->once())->method('createFile')->with(
            $filename,
            $this->stringContains('function admin_index')
        )->will($this->returnValue(true));

        $this->Task->execute();
    }

    /**
     * test that `cake bake controller foos` works.
     */
    public function testExecuteWithController()
    {
        $this->Task->connection = 'test';
        $this->Task->path = '/my/path/';
        $this->Task->args = ['BakeArticles'];

        $filename = '/my/path/BakeArticlesController.php';
        $this->Task->expects($this->once())->method('createFile')->with(
            $filename,
            $this->stringContains('$scaffold')
        );

        $this->Task->execute();
    }

    /**
     * data provider for testExecuteWithControllerNameVariations
     */
    public static function nameVariations()
    {
        return [
            ['BakeArticles'], ['BakeArticle'], ['bake_article'], ['bake_articles']
        ];
    }

    /**
     * test that both plural and singular forms work for controller baking.
     *
     * @dataProvider nameVariations
     */
    public function testExecuteWithControllerNameVariations($name)
    {
        $this->Task->connection = 'test';
        $this->Task->path = '/my/path/';
        $this->Task->args = [$name];

        $filename = '/my/path/BakeArticlesController.php';
        $this->Task->expects($this->once())->method('createFile')->with(
            $filename,
            $this->stringContains('$scaffold')
        );
        $this->Task->execute();
    }

    /**
     * test that `cake bake controller foo scaffold` works.
     */
    public function testExecuteWithPublicParam()
    {
        $this->Task->connection = 'test';
        $this->Task->path = '/my/path/';
        $this->Task->args = ['BakeArticles'];
        $this->Task->params = ['public' => true];

        $filename = '/my/path/BakeArticlesController.php';
        $expected = new PHPUnit_Framework_Constraint_Not($this->stringContains('$scaffold'));
        $this->Task->expects($this->once())->method('createFile')->with(
            $filename,
            $expected
        );
        $this->Task->execute();
    }

    /**
     * test that `cake bake controller foos both` works.
     */
    public function testExecuteWithControllerAndBoth()
    {
        $this->Task->Project->expects($this->any())->method('getPrefix')->will($this->returnValue('admin_'));
        $this->Task->connection = 'test';
        $this->Task->path = '/my/path/';
        $this->Task->args = ['BakeArticles'];
        $this->Task->params = ['public' => true, 'admin' => true];

        $filename = '/my/path/BakeArticlesController.php';
        $this->Task->expects($this->once())->method('createFile')->with(
            $filename,
            $this->stringContains('admin_index')
        );
        $this->Task->execute();
    }

    /**
     * test that `cake bake controller foos admin` works.
     */
    public function testExecuteWithControllerAndAdmin()
    {
        $this->Task->Project->expects($this->any())->method('getPrefix')->will($this->returnValue('admin_'));
        $this->Task->connection = 'test';
        $this->Task->path = '/my/path/';
        $this->Task->args = ['BakeArticles'];
        $this->Task->params = ['admin' => true];

        $filename = '/my/path/BakeArticlesController.php';
        $this->Task->expects($this->once())->method('createFile')->with(
            $filename,
            $this->stringContains('admin_index')
        );
        $this->Task->execute();
    }
}
