<?php
/**
 * Connection Manager tests
 *
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
 * @package       Cake.Test.Case.Model
 *
 * @since         CakePHP(tm) v 1.2.0.5550
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('ConnectionManager', 'Model');

/**
 * ConnectionManagerTest
 *
 * @package       Cake.Test.Case.Model
 */
class ConnectionManagerTest extends CakeTestCase
{
    /**
     * tearDown method
     */
    public function tearDown()
    {
        parent::tearDown();
        CakePlugin::unload();
    }

    /**
     * testEnumConnectionObjects method
     */
    public function testEnumConnectionObjects()
    {
        $sources = ConnectionManager::enumConnectionObjects();
        $this->assertTrue(count($sources) >= 1);

        $connections = ['default', 'test', 'test'];
        $this->assertTrue(count(array_intersect(array_keys($sources), $connections)) >= 1);
    }

    /**
     * testGetDataSource method
     */
    public function testGetDataSource()
    {
        App::build([
            'Model/Datasource' => [
                CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS
            ]
        ]);

        $name = 'test_get_datasource';
        $config = ['datasource' => 'Test2Source'];

        ConnectionManager::create($name, $config);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertTrue(count(array_keys($connections)) >= 1);

        $source = ConnectionManager::getDataSource('test_get_datasource');
        $this->assertTrue(is_object($source));
        ConnectionManager::drop('test_get_datasource');
    }

    /**
     * testGetDataSourceException() method
     *
     * @expectedException MissingDatasourceConfigException
     */
    public function testGetDataSourceException()
    {
        ConnectionManager::getDataSource('non_existent_source');
    }

    /**
     * testGetPluginDataSource method
     */
    public function testGetPluginDataSource()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ], App::RESET);
        CakePlugin::load('TestPlugin');
        $name = 'test_source';
        $config = ['datasource' => 'TestPlugin.TestSource'];
        $connection = ConnectionManager::create($name, $config);

        $this->assertTrue(class_exists('TestSource'));
        $this->assertEquals($connection->configKeyName, $name);
        $this->assertEquals($connection->config, $config);

        ConnectionManager::drop($name);
    }

    /**
     * testGetPluginDataSourceAndPluginDriver method
     */
    public function testGetPluginDataSourceAndPluginDriver()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ], App::RESET);
        CakePlugin::load('TestPlugin');
        $name = 'test_plugin_source_and_driver';
        $config = ['datasource' => 'TestPlugin.Database/TestDriver'];

        $connection = ConnectionManager::create($name, $config);

        $this->assertTrue(class_exists('TestSource'));
        $this->assertTrue(class_exists('TestDriver'));
        $this->assertEquals($connection->configKeyName, $name);
        $this->assertEquals($connection->config, $config);

        ConnectionManager::drop($name);
    }

    /**
     * testGetLocalDataSourceAndPluginDriver method
     */
    public function testGetLocalDataSourceAndPluginDriver()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ]);
        CakePlugin::load('TestPlugin');
        $name = 'test_local_source_and_plugin_driver';
        $config = ['datasource' => 'TestPlugin.Database/DboDummy'];

        $connection = ConnectionManager::create($name, $config);

        $this->assertTrue(class_exists('DboSource'));
        $this->assertTrue(class_exists('DboDummy'));
        $this->assertEquals($connection->configKeyName, $name);

        ConnectionManager::drop($name);
    }

    /**
     * testGetPluginDataSourceAndLocalDriver method
     */
    public function testGetPluginDataSourceAndLocalDriver()
    {
        App::build([
            'Plugin'                    => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS],
            'Model/Datasource/Database' => [
                CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS . 'Database' . DS
            ]
        ]);

        $name = 'test_plugin_source_and_local_driver';
        $config = ['datasource' => 'Database/TestLocalDriver'];

        $connection = ConnectionManager::create($name, $config);

        $this->assertTrue(class_exists('TestSource'));
        $this->assertTrue(class_exists('TestLocalDriver'));
        $this->assertEquals($connection->configKeyName, $name);
        $this->assertEquals($connection->config, $config);
        ConnectionManager::drop($name);
    }

    /**
     * testSourceList method
     */
    public function testSourceList()
    {
        ConnectionManager::getDataSource('test');
        $sources = ConnectionManager::sourceList();
        $this->assertTrue(count($sources) >= 1);
        $this->assertTrue(in_array('test', array_keys($sources)));
    }

    /**
     * testGetSourceName method
     */
    public function testGetSourceName()
    {
        $source = ConnectionManager::getDataSource('test');
        $result = ConnectionManager::getSourceName($source);

        $this->assertEquals('test', $result);

        $source = new StdClass();
        $result = ConnectionManager::getSourceName($source);
        $this->assertNull($result);
    }

    /**
     * testLoadDataSource method
     */
    public function testLoadDataSource()
    {
        $connections = [
            ['classname' => 'Mysql', 'filename' => 'Mysql', 'package' => 'Database'],
            ['classname' => 'Postgres', 'filename' => 'Postgres', 'package' => 'Database'],
            ['classname' => 'Sqlite', 'filename' => 'Sqlite', 'package' => 'Database'],
        ];

        foreach ($connections as $connection) {
            $exists = class_exists($connection['classname']);
            $loaded = ConnectionManager::loadDataSource($connection);
            $this->assertEquals($loaded, !$exists, "Failed loading the {$connection['classname']} datasource");
        }
    }

    /**
     * testLoadDataSourceException() method
     *
     * @expectedException MissingDatasourceException
     */
    public function testLoadDataSourceException()
    {
        $connection = ['classname' => 'NonExistentDataSource', 'filename' => 'non_existent'];
        ConnectionManager::loadDataSource($connection);
    }

    /**
     * testCreateDataSource method
     */
    public function testCreateDataSourceWithIntegrationTests()
    {
        $name = 'test_created_connection';

        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertTrue(count(array_keys($connections)) >= 1);

        $source = ConnectionManager::getDataSource('test');
        $this->assertTrue(is_object($source));

        $config = $source->config;
        $connection = ConnectionManager::create($name, $config);

        $this->assertTrue(is_object($connection));
        $this->assertEquals($name, $connection->configKeyName);
        $this->assertEquals($name, ConnectionManager::getSourceName($connection));

        $source = ConnectionManager::create(null, []);
        $this->assertEquals(null, $source);

        $source = ConnectionManager::create('another_test', []);
        $this->assertEquals(null, $source);

        $config = ['classname' => 'DboMysql', 'filename' => 'dbo' . DS . 'dbo_mysql'];
        $source = ConnectionManager::create(null, $config);
        $this->assertEquals(null, $source);
    }

    /**
     * testConnectionData method
     */
    public function testConnectionData()
    {
        App::build([
            'Plugin'           => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS],
            'Model/Datasource' => [
                CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS
            ]
        ], App::RESET);
        CakePlugin::load(['TestPlugin', 'TestPluginTwo']);
        $expected = [
            'datasource' => 'Test2Source'
        ];

        ConnectionManager::create('connection1', ['datasource' => 'Test2Source']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals($expected, $connections['connection1']);
        ConnectionManager::drop('connection1');

        ConnectionManager::create('connection2', ['datasource' => 'Test2Source']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals($expected, $connections['connection2']);
        ConnectionManager::drop('connection2');

        ConnectionManager::create('connection3', ['datasource' => 'TestPlugin.TestSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $expected['datasource'] = 'TestPlugin.TestSource';
        $this->assertEquals($expected, $connections['connection3']);
        ConnectionManager::drop('connection3');

        ConnectionManager::create('connection4', ['datasource' => 'TestPlugin.TestSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals($expected, $connections['connection4']);
        ConnectionManager::drop('connection4');

        ConnectionManager::create('connection5', ['datasource' => 'Test2OtherSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $expected['datasource'] = 'Test2OtherSource';
        $this->assertEquals($expected, $connections['connection5']);
        ConnectionManager::drop('connection5');

        ConnectionManager::create('connection6', ['datasource' => 'Test2OtherSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals($expected, $connections['connection6']);
        ConnectionManager::drop('connection6');

        ConnectionManager::create('connection7', ['datasource' => 'TestPlugin.TestOtherSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $expected['datasource'] = 'TestPlugin.TestOtherSource';
        $this->assertEquals($expected, $connections['connection7']);
        ConnectionManager::drop('connection7');

        ConnectionManager::create('connection8', ['datasource' => 'TestPlugin.TestOtherSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals($expected, $connections['connection8']);
        ConnectionManager::drop('connection8');
    }

    /**
     * Tests that a connection configuration can be deleted in runtime
     */
    public function testDrop()
    {
        App::build([
            'Model/Datasource' => [
                CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS
            ]
        ]);
        ConnectionManager::create('droppable', ['datasource' => 'Test2Source']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals(['datasource' => 'Test2Source'], $connections['droppable']);

        $this->assertTrue(ConnectionManager::drop('droppable'));
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertFalse(isset($connections['droppable']));
    }
}
