<?php
/**
 * CakeTestCaseTest file
 *
 * Test Case for CakeTestCase class
 *
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
 * @package       Cake.Test.Case.TestSuite
 *
 * @since         CakePHP v 1.2.0.4487
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('CakePlugin', 'Core');
App::uses('Controller', 'Controller');
App::uses('CakeHtmlReporter', 'TestSuite/Reporter');
App::uses('Model', 'Model');

/**
 * Secondary Post stub class.
 */
class SecondaryPost extends Model
{
    /**
     * @var string
     */
    public $useTable = 'posts';

    /**
     * @var string
     */
    public $useDbConfig = 'secondary';
}

/**
 * ConstructorPost test stub.
 */
class ConstructorPost extends Model
{
    /**
     * @var string
     */
    public $useTable = 'posts';

    public function __construct($id = false, $table = null, $ds = null)
    {
        parent::__construct($id, $table, $ds);
        $this->getDataSource()->cacheMethods = false;
    }
}

/**
 * CakeTestCaseTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestCaseTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['core.post', 'core.author', 'core.test_plugin_comment'];

    /**
     * CakeTestCaseTest::setUpBeforeClass()
     */
    public static function setUpBeforeClass()
    {
        require_once CAKE . 'Test' . DS . 'Fixture' . DS . 'AssertTagsTestCase.php';
        require_once CAKE . 'Test' . DS . 'Fixture' . DS . 'FixturizedTestCase.php';
    }

    /**
     * setUp
     */
    public function setUp()
    {
        parent::setUp();
        $this->Reporter = $this->getMock('CakeHtmlReporter');
    }

    /**
     * tearDown
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Result);
        unset($this->Reporter);
    }

    /**
     * testAssertTags
     */
    public function testAssertTagsBasic()
    {
        $test = new AssertTagsTestCase('testAssertTagsQuotes');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals(0, $result->failureCount());
    }

    /**
     * test assertTags works with single and double quotes
     */
    public function testAssertTagsQuoting()
    {
        $input = '<a href="/test.html" class="active">My link</a>';
        $pattern = [
            'a' => ['href' => '/test.html', 'class' => 'active'],
            'My link',
            '/a'
        ];
        $this->assertTags($input, $pattern);

        $input = '<a href=\'/test.html\' class=\'active\'>My link</a>';
        $pattern = [
            'a' => ['href' => '/test.html', 'class' => 'active'],
            'My link',
            '/a'
        ];
        $this->assertTags($input, $pattern);

        $input = '<a href=\'/test.html\' class=\'active\'>My link</a>';
        $pattern = [
            'a' => ['href' => 'preg:/.*\.html/', 'class' => 'active'],
            'My link',
            '/a'
        ];
        $this->assertTags($input, $pattern);

        $input = '<span><strong>Text</strong></span>';
        $pattern = [
            '<span',
            '<strong',
            'Text',
            '/strong',
            '/span'
        ];
        $this->assertTags($input, $pattern);

        $input = '<span class=\'active\'><strong>Text</strong></span>';
        $pattern = [
            'span' => ['class'],
            '<strong',
            'Text',
            '/strong',
            '/span'
        ];
        $this->assertTags($input, $pattern);
    }

    /**
     * Test that assertTags runs quickly.
     */
    public function testAssertTagsRuntimeComplexity()
    {
        $pattern = [
            'div' => [
                'attr1' => 'val1',
                'attr2' => 'val2',
                'attr3' => 'val3',
                'attr4' => 'val4',
                'attr5' => 'val5',
                'attr6' => 'val6',
                'attr7' => 'val7',
                'attr8' => 'val8',
            ],
            'My div',
            '/div'
        ];
        $input = '<div attr8="val8" attr6="val6" attr4="val4" attr2="val2"' .
            ' attr1="val1" attr3="val3" attr5="val5" attr7="val7" />' .
            'My div' .
            '</div>';
        $this->assertTags($input, $pattern);
    }

    /**
     * testNumericValuesInExpectationForAssertTags
     */
    public function testNumericValuesInExpectationForAssertTags()
    {
        $test = new AssertTagsTestCase('testNumericValuesInExpectationForAssertTags');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals(0, $result->failureCount());
    }

    /**
     * testBadAssertTags
     */
    public function testBadAssertTags()
    {
        $test = new AssertTagsTestCase('testBadAssertTags');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
        $this->assertFalse($result->wasSuccessful());
        $this->assertEquals(1, $result->failureCount());

        $test = new AssertTagsTestCase('testBadAssertTags2');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
        $this->assertFalse($result->wasSuccessful());
        $this->assertEquals(1, $result->failureCount());
    }

    /**
     * testLoadFixtures
     */
    public function testLoadFixtures()
    {
        $test = new FixturizedTestCase('testFixturePresent');
        $manager = $this->getMock('CakeFixtureManager');
        $manager->fixturize($test);
        $test->fixtureManager = $manager;
        $manager->expects($this->once())->method('load');
        $manager->expects($this->once())->method('unload');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals(0, $result->failureCount());
    }

    /**
     * testLoadFixturesOnDemand
     */
    public function testLoadFixturesOnDemand()
    {
        $test = new FixturizedTestCase('testFixtureLoadOnDemand');
        $test->autoFixtures = false;
        $manager = $this->getMock('CakeFixtureManager');
        $manager->fixturize($test);
        $test->fixtureManager = $manager;
        $manager->expects($this->once())->method('loadSingle');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
    }

    /**
     * testLoadFixturesOnDemand
     */
    public function testUnoadFixturesAfterFailure()
    {
        $test = new FixturizedTestCase('testFixtureLoadOnDemand');
        $test->autoFixtures = false;
        $manager = $this->getMock('CakeFixtureManager');
        $manager->fixturize($test);
        $test->fixtureManager = $manager;
        $manager->expects($this->once())->method('loadSingle');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
    }

    /**
     * testThrowException
     */
    public function testThrowException()
    {
        $test = new FixturizedTestCase('testThrowException');
        $test->autoFixtures = false;
        $manager = $this->getMock('CakeFixtureManager');
        $manager->fixturize($test);
        $test->fixtureManager = $manager;
        $manager->expects($this->once())->method('unload');
        $result = $test->run();
        $this->assertEquals(1, $result->errorCount());
    }

    /**
     * testSkipIf
     */
    public function testSkipIf()
    {
        $test = new FixturizedTestCase('testSkipIfTrue');
        $result = $test->run();
        $this->assertEquals(1, $result->skippedCount());

        $test = new FixturizedTestCase('testSkipIfFalse');
        $result = $test->run();
        $this->assertEquals(0, $result->skippedCount());
    }

    /**
     * Test that CakeTestCase::setUp() backs up values.
     */
    public function testSetupBackUpValues()
    {
        $this->assertArrayHasKey('debug', $this->_configure);
        $this->assertArrayHasKey('Plugin', $this->_pathRestore);
    }

    /**
     * test assertTextNotEquals()
     */
    public function testAssertTextNotEquals()
    {
        $one = "\r\nOne\rTwooo";
        $two = "\nOne\nTwo";
        $this->assertTextNotEquals($one, $two);
    }

    /**
     * test assertTextEquals()
     */
    public function testAssertTextEquals()
    {
        $one = "\r\nOne\rTwo";
        $two = "\nOne\nTwo";
        $this->assertTextEquals($one, $two);
    }

    /**
     * test assertTextStartsWith()
     */
    public function testAssertTextStartsWith()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertStringStartsWith("some\nstring", $stringDirty);
        $this->assertStringStartsNotWith("some\r\nstring\r\nwith", $stringDirty);
        $this->assertStringStartsNotWith("some\nstring\nwith", $stringDirty);

        $this->assertTextStartsWith("some\nstring\nwith", $stringDirty);
        $this->assertTextStartsWith("some\r\nstring\r\nwith", $stringDirty);
    }

    /**
     * test assertTextStartsNotWith()
     */
    public function testAssertTextStartsNotWith()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
        $this->assertTextStartsNotWith("some\nstring\nwithout", $stringDirty);
    }

    /**
     * test assertTextEndsWith()
     */
    public function testAssertTextEndsWith()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
        $this->assertTextEndsWith("string\nwith\r\ndifferent\rline endings!", $stringDirty);
        $this->assertTextEndsWith("string\r\nwith\ndifferent\nline endings!", $stringDirty);
    }

    /**
     * test assertTextEndsNotWith()
     */
    public function testAssertTextEndsNotWith()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
        $this->assertStringEndsNotWith("different\nline endings", $stringDirty);
        $this->assertTextEndsNotWith("different\rline endings", $stringDirty);
    }

    /**
     * test assertTextContains()
     */
    public function testAssertTextContains()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
        $this->assertContains('different', $stringDirty);
        $this->assertNotContains("different\rline", $stringDirty);
        $this->assertTextContains("different\rline", $stringDirty);
    }

    /**
     * test assertTextNotContains()
     */
    public function testAssertTextNotContains()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
        $this->assertTextNotContains("different\rlines", $stringDirty);
    }

    /**
     * test getMockForModel()
     */
    public function testGetMockForModel()
    {
        App::build([
            'Model' => [
                CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS
            ]
        ], App::RESET);
        $Post = $this->getMockForModel('Post');
        $this->assertEquals('test', $Post->useDbConfig);
        $this->assertInstanceOf('Post', $Post);
        $this->assertNull($Post->save([]));
        $this->assertNull($Post->find('all'));
        $this->assertEquals('posts', $Post->useTable);

        $Post = $this->getMockForModel('Post', ['save']);

        $this->assertNull($Post->save([]));
        $this->assertInternalType('array', $Post->find('all'));
    }

    /**
     * Test getMockForModel on secondary datasources.
     */
    public function testGetMockForModelSecondaryDatasource()
    {
        App::build([
            'Plugin'                    => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS],
            'Model/Datasource/Database' => [
                CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS . 'Database' . DS
            ]
        ], App::RESET);
        CakePlugin::load('TestPlugin');
        ConnectionManager::create('test_secondary', [
            'datasource' => 'Database/TestLocalDriver',
            'prefix'     => ''
        ]);
        $post = $this->getMockForModel('SecondaryPost', ['save']);
        $this->assertEquals('test_secondary', $post->useDbConfig);
        ConnectionManager::drop('test_secondary');
    }

    /**
     * Test getMockForModel when the model accesses the datasource in the constructor.
     */
    public function testGetMockForModelConstructorDatasource()
    {
        $post = $this->getMockForModel('ConstructorPost', ['save'], ['ds' => 'test']);
        $this->assertEquals('test', $post->useDbConfig);
    }

    /**
     * test getMockForModel() with plugin models
     */
    public function testGetMockForModelWithPlugin()
    {
        App::build([
            'Plugin' => [
                CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS
            ]
        ], App::RESET);
        CakePlugin::load('TestPlugin');
        $this->getMockForModel('TestPlugin.TestPluginAppModel');
        $this->getMockForModel('TestPlugin.TestPluginComment');

        $result = ClassRegistry::init('TestPlugin.TestPluginComment');
        $this->assertInstanceOf('TestPluginComment', $result);
        $this->assertEquals('test', $result->useDbConfig);

        $TestPluginComment = $this->getMockForModel('TestPlugin.TestPluginComment', ['save']);

        $this->assertInstanceOf('TestPluginComment', $TestPluginComment);
        $TestPluginComment->expects($this->at(0))
            ->method('save')
            ->will($this->returnValue(true));
        $TestPluginComment->expects($this->at(1))
            ->method('save')
            ->will($this->returnValue(false));
        $this->assertTrue($TestPluginComment->save([]));
        $this->assertFalse($TestPluginComment->save([]));
    }

    /**
     * testGetMockForModelModel
     */
    public function testGetMockForModelModel()
    {
        $Mock = $this->getMockForModel('Model', ['save', 'setDataSource'], ['name' => 'Comment']);

        $result = ClassRegistry::init('Comment');
        $this->assertInstanceOf('Model', $result);

        $Mock->expects($this->at(0))
            ->method('save')
            ->will($this->returnValue(true));
        $Mock->expects($this->at(1))
            ->method('save')
            ->will($this->returnValue(false));

        $this->assertTrue($Mock->save([]));
        $this->assertFalse($Mock->save([]));
    }

    /**
     * testGetMockForModelDoesNotExist
     *
     * @expectedException MissingModelException
     * @expectedExceptionMessage Model IDoNotExist could not be found
     */
    public function testGetMockForModelDoesNotExist()
    {
        $this->getMockForModel('IDoNotExist');
    }
}
