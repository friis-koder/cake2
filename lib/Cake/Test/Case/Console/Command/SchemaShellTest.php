<?php
/**
 * SchemaShellTest Test file
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
 * @package       Cake.Test.Case.Console.Command
 *
 * @since         CakePHP v 1.3
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('ShellDispatcher', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('CakeSchema', 'Model');
App::uses('SchemaShell', 'Console/Command');

/**
 * Test for Schema database management
 *
 * @package       Cake.Test.Case.Console.Command
 */
class SchemaShellTestSchema extends CakeSchema
{
    /**
     * connection property
     *
     * @var string
     */
    public $connection = 'test';

    /**
     * comments property
     *
     * @var array
     */
    public $comments = [
        'id'        => ['type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'],
        'post_id'   => ['type' => 'integer', 'null' => false, 'default' => 0],
        'user_id'   => ['type' => 'integer', 'null' => false],
        'title'     => ['type' => 'string', 'null' => false, 'length' => 100],
        'comment'   => ['type' => 'text', 'null' => false, 'default' => null],
        'published' => ['type' => 'string', 'null' => true, 'default' => 'N', 'length' => 1],
        'created'   => ['type' => 'datetime', 'null' => true, 'default' => null],
        'updated'   => ['type' => 'datetime', 'null' => true, 'default' => null],
        'indexes'   => ['PRIMARY' => ['column' => 'id', 'unique' => true]],
    ];

    /**
     * posts property
     *
     * @var array
     */
    public $articles = [
        'id'        => ['type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'],
        'user_id'   => ['type' => 'integer', 'null' => true, 'default' => ''],
        'title'     => ['type' => 'string', 'null' => false, 'default' => 'Title'],
        'body'      => ['type' => 'text', 'null' => true, 'default' => null],
        'summary'   => ['type' => 'text', 'null' => true],
        'published' => ['type' => 'string', 'null' => true, 'default' => 'Y', 'length' => 1],
        'created'   => ['type' => 'datetime', 'null' => true, 'default' => null],
        'updated'   => ['type' => 'datetime', 'null' => true, 'default' => null],
        'indexes'   => ['PRIMARY' => ['column' => 'id', 'unique' => true]],
    ];

    public $newone = [
        'id'      => ['type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'],
        'testit'  => ['type' => 'string', 'null' => false, 'default' => 'Title'],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'updated' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => true]],
    ];
}

/**
 * SchemaShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class SchemaShellTest extends CakeTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'core.article', 'core.user', 'core.post', 'core.auth_user', 'core.author',
        'core.comment', 'core.test_plugin_comment', 'core.aco', 'core.aro', 'core.aros_aco',
    ];

    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();

        $out = $this->getMock('ConsoleOutput', [], [], '', false);
        $in = $this->getMock('ConsoleInput', [], [], '', false);
        $this->Shell = $this->getMock(
            'SchemaShell',
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop'],
            [$out, $out, $in]
        );
    }

    /**
     * tearDown method
     */
    public function tearDown()
    {
        parent::tearDown();
        if (!empty($this->file) && $this->file instanceof File) {
            $this->file->delete();
            unset($this->file);
        }
    }

    /**
     * test startup method
     */
    public function testStartup()
    {
        $this->Shell->startup();
        $this->assertTrue(isset($this->Shell->Schema));
        $this->assertInstanceOf('CakeSchema', $this->Shell->Schema);
        $this->assertEquals(Inflector::camelize(Inflector::slug(APP_DIR)), $this->Shell->Schema->name);
        $this->assertEquals('schema.php', $this->Shell->Schema->file);

        $this->Shell->Schema = null;
        $this->Shell->params = [
            'name' => 'TestSchema'
        ];
        $this->Shell->startup();
        $this->assertEquals('TestSchema', $this->Shell->Schema->name);
        $this->assertEquals('test_schema.php', $this->Shell->Schema->file);
        $this->assertEquals('default', $this->Shell->Schema->connection);
        $this->assertEquals(CONFIG . 'Schema', $this->Shell->Schema->path);

        $this->Shell->Schema = null;
        $this->Shell->params = [
            'file'       => 'other_file.php',
            'connection' => 'test',
            'path'       => '/test/path'
        ];
        $this->Shell->startup();
        $this->assertEquals(Inflector::camelize(Inflector::slug(APP_DIR)), $this->Shell->Schema->name);
        $this->assertEquals('other_file.php', $this->Shell->Schema->file);
        $this->assertEquals('test', $this->Shell->Schema->connection);
        $this->assertEquals('/test/path', $this->Shell->Schema->path);
    }

    /**
     * Test View - and that it dumps the schema file to stdout
     */
    public function testView()
    {
        $this->Shell->startup();
        $this->Shell->Schema->path = CONFIG . 'Schema';
        $this->Shell->params['file'] = 'i18n.php';
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->expects($this->once())->method('out');
        $this->Shell->view();
    }

    /**
     * test that view() can find plugin schema files.
     */
    public function testViewWithPlugins()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ]);
        CakePlugin::load('TestPlugin');
        $this->Shell->args = ['TestPlugin.schema'];
        $this->Shell->startup();
        $this->Shell->expects($this->exactly(2))->method('_stop');
        $this->Shell->expects($this->atLeastOnce())->method('out');
        $this->Shell->view();

        $this->Shell->args = [];
        $this->Shell->params = ['plugin' => 'TestPlugin'];
        $this->Shell->startup();
        $this->Shell->view();

        App::build();
        CakePlugin::unload();
    }

    /**
     * test dump() with sql file generation
     */
    public function testDumpWithFileWriting()
    {
        $this->Shell->params = [
            'name'       => 'i18n',
            'connection' => 'test',
            'write'      => TMP . 'tests' . DS . 'i18n.sql'
        ];
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->startup();
        $this->Shell->dump();

        $this->file = new File(TMP . 'tests' . DS . 'i18n.sql');
        $contents = $this->file->read();
        $this->assertRegExp('/DROP TABLE/', $contents);
        $this->assertRegExp('/CREATE TABLE.*?i18n/', $contents);
        $this->assertRegExp('/id/', $contents);
        $this->assertRegExp('/model/', $contents);
        $this->assertRegExp('/field/', $contents);
        $this->assertRegExp('/locale/', $contents);
        $this->assertRegExp('/foreign_key/', $contents);
        $this->assertRegExp('/content/', $contents);
    }

    /**
     * test that dump() can find and work with plugin schema files.
     */
    public function testDumpFileWritingWithPlugins()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ]);
        CakePlugin::load('TestPlugin');
        $this->Shell->args = ['TestPlugin.TestPluginApp'];
        $this->Shell->params = [
            'connection' => 'test',
            'write'      => TMP . 'tests' . DS . 'dump_test.sql'
        ];
        $this->Shell->startup();
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->dump();

        $this->file = new File(TMP . 'tests' . DS . 'dump_test.sql');
        $contents = $this->file->read();

        $this->assertRegExp('/CREATE TABLE.*?test_plugin_acos/', $contents);
        $this->assertRegExp('/id/', $contents);
        $this->assertRegExp('/model/', $contents);

        $this->file->delete();
        App::build();
        CakePlugin::unload();
    }

    /**
     * test generate with snapshot generation
     */
    public function testGenerateSnapshot()
    {
        $this->Shell->path = TMP;
        $this->Shell->params['file'] = 'schema.php';
        $this->Shell->params['force'] = false;
        $this->Shell->args = ['snapshot'];
        $this->Shell->Schema = $this->getMock('CakeSchema');
        $this->Shell->Schema->expects($this->at(0))->method('read')->will($this->returnValue(['schema data']));
        $this->Shell->Schema->expects($this->at(0))->method('write')->will($this->returnValue(true));

        $this->Shell->Schema->expects($this->at(1))->method('read');
        $this->Shell->Schema->expects($this->at(1))->method('write')->with(['schema data', 'file' => 'schema_0.php']);

        $this->Shell->generate();
    }

    /**
     * test generate without a snapshot.
     */
    public function testGenerateNoOverwrite()
    {
        touch(TMP . 'schema.php');
        $this->Shell->params['file'] = 'schema.php';
        $this->Shell->params['force'] = false;
        $this->Shell->args = [];

        $this->Shell->expects($this->once())->method('in')->will($this->returnValue('q'));
        $this->Shell->Schema = $this->getMock('CakeSchema');
        $this->Shell->Schema->path = TMP;
        $this->Shell->Schema->expects($this->never())->method('read');

        $this->Shell->generate();
        unlink(TMP . 'schema.php');
    }

    /**
     * test generate with overwriting of the schema files.
     */
    public function testGenerateOverwrite()
    {
        touch(TMP . 'schema.php');
        $this->Shell->params['file'] = 'schema.php';
        $this->Shell->params['force'] = false;
        $this->Shell->args = [];

        $this->Shell->expects($this->once())->method('in')->will($this->returnValue('o'));

        $this->Shell->expects($this->at(2))->method('out')
            ->with(new PHPUnit_Framework_Constraint_PCREMatch('/Schema file:\s[a-z\.]+\sgenerated/'));

        $this->Shell->Schema = $this->getMock('CakeSchema');
        $this->Shell->Schema->path = TMP;
        $this->Shell->Schema->expects($this->once())->method('read')->will($this->returnValue(['schema data']));
        $this->Shell->Schema->expects($this->once())->method('write')->will($this->returnValue(true));

        $this->Shell->Schema->expects($this->once())->method('read');
        $this->Shell->Schema->expects($this->once())->method('write')
            ->with(['schema data', 'file' => 'schema.php']);

        $this->Shell->generate();
        unlink(TMP . 'schema.php');
    }

    /**
     * test that generate() can read plugin dirs and generate schema files for the models
     * in a plugin.
     */
    public function testGenerateWithPlugins()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ], App::RESET);
        CakePlugin::load('TestPlugin');

        $this->db->cacheSources = false;
        $this->Shell->params = [
            'plugin'     => 'TestPlugin',
            'connection' => 'test',
            'force'      => false
        ];
        $this->Shell->startup();
        $this->Shell->Schema->path = TMP . 'tests' . DS;

        $this->Shell->generate();
        $this->file = new File(TMP . 'tests' . DS . 'schema.php');
        $contents = $this->file->read();

        $this->assertRegExp('/class TestPluginSchema/', $contents);
        $this->assertRegExp('/public \$posts/', $contents);
        $this->assertRegExp('/public \$auth_users/', $contents);
        $this->assertRegExp('/public \$authors/', $contents);
        $this->assertRegExp('/public \$test_plugin_comments/', $contents);
        $this->assertNotRegExp('/public \$users/', $contents);
        $this->assertNotRegExp('/public \$articles/', $contents);
        CakePlugin::unload();
    }

    /**
     * test generate with specific models
     */
    public function testGenerateModels()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ], App::RESET);
        CakePlugin::load('TestPlugin');

        $this->db->cacheSources = false;
        $this->Shell->params = [
            'plugin'     => 'TestPlugin',
            'connection' => 'test',
            'models'     => 'TestPluginComment',
            'force'      => false,
            'overwrite'  => true
        ];
        $this->Shell->startup();
        $this->Shell->Schema->path = TMP . 'tests' . DS;

        $this->Shell->generate();
        $this->file = new File(TMP . 'tests' . DS . 'schema.php');
        $contents = $this->file->read();

        $this->assertRegExp('/class TestPluginSchema/', $contents);
        $this->assertRegExp('/public \$test_plugin_comments/', $contents);
        $this->assertNotRegExp('/public \$authors/', $contents);
        $this->assertNotRegExp('/public \$auth_users/', $contents);
        $this->assertNotRegExp('/public \$posts/', $contents);
        CakePlugin::unload();
    }

    /**
     * test generate with excluded tables
     */
    public function testGenerateExclude()
    {
        Configure::write('Acl.database', 'test');
        $this->db->cacheSources = false;
        $this->Shell->params = [
            'connection' => 'test',
            'force'      => false,
            'models'     => 'Aro, Aco, Permission',
            'overwrite'  => true,
            'exclude'    => 'acos, aros',
        ];
        $this->Shell->startup();
        $this->Shell->Schema->path = TMP . 'tests' . DS;

        $this->Shell->generate();
        $this->file = new File(TMP . 'tests' . DS . 'schema.php');
        $contents = $this->file->read();

        $this->assertNotContains('public $acos = array(', $contents);
        $this->assertNotContains('public $aros = array(', $contents);
        $this->assertContains('public $aros_acos = array(', $contents);
    }

    /**
     * Test schema run create with --yes option
     */
    public function testCreateOptionYes()
    {
        $this->Shell = $this->getMock(
            'SchemaShell',
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'],
            [&$this->Dispatcher]
        );

        $this->Shell->params = [
            'connection' => 'test',
            'yes'        => true,
        ];
        $this->Shell->args = ['i18n'];
        $this->Shell->expects($this->never())->method('in');
        $this->Shell->expects($this->exactly(2))->method('_run');
        $this->Shell->startup();
        $this->Shell->create();
    }

    /**
     * Test schema run create with no table args.
     */
    public function testCreateNoArgs()
    {
        $this->Shell->params = [
            'connection' => 'test'
        ];
        $this->Shell->args = ['i18n'];
        $this->Shell->startup();
        $this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
        $this->Shell->create();

        $db = ConnectionManager::getDataSource('test');

        $db->cacheSources = false;
        $sources = $db->listSources();
        $this->assertTrue(in_array($db->config['prefix'] . 'i18n', $sources));

        $schema = new i18nSchema();
        $db->execute($db->dropSchema($schema));
    }

    /**
     * Test schema run create with no table args.
     */
    public function testCreateWithTableArgs()
    {
        $db = ConnectionManager::getDataSource('test');
        $sources = $db->listSources();
        if (in_array('i18n', $sources)) {
            $this->markTestSkipped('i18n table already exists, cannot try to create it again.');
        }
        $this->Shell->params = [
            'connection' => 'test',
            'name'       => 'I18n',
            'path'       => CONFIG . 'Schema'
        ];
        $this->Shell->args = ['I18n', 'i18n'];
        $this->Shell->startup();
        $this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
        $this->Shell->create();

        $db = ConnectionManager::getDataSource('test');
        $db->cacheSources = false;
        $sources = $db->listSources();
        $this->assertTrue(in_array($db->config['prefix'] . 'i18n', $sources), 'i18n should be present.');

        $schema = new I18nSchema();
        $db->execute($db->dropSchema($schema, 'i18n'));
    }

    /**
     * test run update with a table arg.
     */
    public function testUpdateWithTable()
    {
        $this->Shell = $this->getMock(
            'SchemaShell',
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'],
            [&$this->Dispatcher]
        );

        $this->Shell->params = [
            'connection' => 'test',
            'force'      => true
        ];
        $this->Shell->args = ['SchemaShellTest', 'articles'];
        $this->Shell->startup();
        $this->Shell->expects($this->any())
            ->method('in')
            ->will($this->returnValue('y'));
        $this->Shell->expects($this->once())
            ->method('_run')
            ->with($this->arrayHasKey('articles'), 'update', $this->isInstanceOf('CakeSchema'));

        $this->Shell->update();
    }

    /**
     * test run update with a table arg. and checks that a CREATE statement is issued
     * table creation
     */
    public function testUpdateWithTableCreate()
    {
        $this->Shell = $this->getMock(
            'SchemaShell',
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'],
            [&$this->Dispatcher]
        );

        $this->Shell->params = [
            'connection' => 'test',
            'force'      => true
        ];
        $this->Shell->args = ['SchemaShellTest', 'newone'];
        $this->Shell->startup();
        $this->Shell->expects($this->any())
            ->method('in')
            ->will($this->returnValue('y'));
        $this->Shell->expects($this->once())
            ->method('_run')
            ->with($this->arrayHasKey('newone'), 'update', $this->isInstanceOf('CakeSchema'));

        $this->Shell->update();
    }

    /**
     * test run update with --yes option
     */
    public function testUpdateWithOptionYes()
    {
        $this->Shell = $this->getMock(
            'SchemaShell',
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'],
            [&$this->Dispatcher]
        );

        $this->Shell->params = [
            'connection' => 'test',
            'force'      => true,
            'yes'        => true,
        ];
        $this->Shell->args = ['SchemaShellTest', 'articles'];
        $this->Shell->startup();
        $this->Shell->expects($this->never())->method('in');
        $this->Shell->expects($this->once())
            ->method('_run')
            ->with($this->arrayHasKey('articles'), 'update', $this->isInstanceOf('CakeSchema'));

        $this->Shell->update();
    }

    /**
     * test that the plugin param creates the correct path in the schema object.
     */
    public function testPluginParam()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ]);
        CakePlugin::load('TestPlugin');
        $this->Shell->params = [
            'plugin'     => 'TestPlugin',
            'connection' => 'test'
        ];
        $this->Shell->startup();
        $expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'Config' . DS . 'Schema';
        $this->assertEquals($expected, $this->Shell->Schema->path);
        CakePlugin::unload();
    }

    /**
     * test that underscored names also result in CamelCased class names
     */
    public function testName()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ]);
        CakePlugin::load('TestPlugin');
        $this->Shell->params = [
            'plugin'     => 'TestPlugin',
            'connection' => 'test',
            'name'       => 'custom_names',
            'force'      => false,
            'overwrite'  => true,
        ];
        $this->Shell->startup();
        if (file_exists($this->Shell->Schema->path . DS . 'custom_names.php')) {
            unlink($this->Shell->Schema->path . DS . 'custom_names.php');
        }
        $this->Shell->generate();

        $contents = file_get_contents($this->Shell->Schema->path . DS . 'custom_names.php');
        $this->assertRegExp('/class CustomNamesSchema/', $contents);
        unlink($this->Shell->Schema->path . DS . 'custom_names.php');
        CakePlugin::unload();
    }

    /**
     * test that passing name and file creates the passed filename with the
     * passed class name
     */
    public function testNameAndFile()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ]);
        CakePlugin::load('TestPlugin');
        $this->Shell->params = [
            'plugin'     => 'TestPlugin',
            'connection' => 'test',
            'name'       => 'custom_name',
            'file'       => 'other_name',
            'force'      => false,
            'overwrite'  => true,
        ];
        $this->Shell->startup();
        $file = $this->Shell->Schema->path . DS . 'other_name.php';
        if (file_exists($file)) {
            unlink($file);
        }
        $this->Shell->generate();

        $this->assertFileExists($file);
        $contents = file_get_contents($file);
        $this->assertRegExp('/class CustomNameSchema/', $contents);

        if (file_exists($file)) {
            unlink($file);
        }
        CakePlugin::unload();
    }

    /**
     * test that using Plugin.name with write.
     */
    public function testPluginDotSyntaxWithCreate()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ]);
        CakePlugin::load('TestPlugin');
        $this->Shell->params = [
            'connection' => 'test'
        ];
        $this->Shell->args = ['TestPlugin.TestPluginApp'];
        $this->Shell->startup();
        $this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
        $this->Shell->create();

        $db = ConnectionManager::getDataSource('test');
        $sources = $db->listSources();
        $this->assertTrue(in_array($db->config['prefix'] . 'test_plugin_acos', $sources));

        $schema = new TestPluginAppSchema();
        $db->execute($db->dropSchema($schema, 'test_plugin_acos'));
        CakePlugin::unload();
    }
}
