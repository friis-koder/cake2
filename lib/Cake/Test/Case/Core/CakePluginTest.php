<?php
/**
 * CakePluginTest file.
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
 * @package       Cake.Test.Case.Core
 *
 * @since         CakePHP(tm) v 2.0
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('CakePlugin', 'Core');

/**
 * CakePluginTest class
 */
class CakePluginTest extends CakeTestCase
{
    /**
     * Sets the plugins folder for this test
     */
    public function setUp()
    {
        parent::setUp();
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ], App::RESET);
        App::objects('plugins', null, false);
    }

    /**
     * Reverts the changes done to the environment while testing
     */
    public function tearDown()
    {
        parent::tearDown();
        CakePlugin::unload();
    }

    /**
     * Tests loading a single plugin
     */
    public function testLoadSingle()
    {
        CakePlugin::unload();
        CakePlugin::load('TestPlugin');
        $expected = ['TestPlugin'];
        $this->assertEquals($expected, CakePlugin::loaded());
    }

    /**
     * Tests unloading plugins
     */
    public function testUnload()
    {
        CakePlugin::load('TestPlugin');
        $expected = ['TestPlugin'];
        $this->assertEquals($expected, CakePlugin::loaded());

        CakePlugin::unload('TestPlugin');
        $this->assertEquals([], CakePlugin::loaded());

        CakePlugin::load('TestPlugin');
        $expected = ['TestPlugin'];
        $this->assertEquals($expected, CakePlugin::loaded());

        CakePlugin::unload('TestFakePlugin');
        $this->assertEquals($expected, CakePlugin::loaded());
    }

    /**
     * Tests loading a plugin and its bootstrap file
     */
    public function testLoadSingleWithBootstrap()
    {
        CakePlugin::load('TestPlugin', ['bootstrap' => true]);
        $this->assertTrue(CakePlugin::loaded('TestPlugin'));
        $this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
    }

    /**
     * Tests loading a plugin with bootstrap file and routes file
     */
    public function testLoadSingleWithBootstrapAndRoutes()
    {
        CakePlugin::load('TestPlugin', ['bootstrap' => true, 'routes' => true]);
        $this->assertTrue(CakePlugin::loaded('TestPlugin'));
        $this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));

        CakePlugin::routes();
        $this->assertEquals('loaded plugin routes', Configure::read('CakePluginTest.test_plugin.routes'));
    }

    /**
     * Tests loading multiple plugins at once
     */
    public function testLoadMultiple()
    {
        CakePlugin::load(['TestPlugin', 'TestPluginTwo']);
        $expected = ['TestPlugin', 'TestPluginTwo'];
        $this->assertEquals($expected, CakePlugin::loaded());
    }

    /**
     * Tests loading multiple plugins and their bootstrap files
     */
    public function testLoadMultipleWithDefaults()
    {
        CakePlugin::load(['TestPlugin', 'TestPluginTwo'], ['bootstrap' => true, 'routes' => false]);
        $expected = ['TestPlugin', 'TestPluginTwo'];
        $this->assertEquals($expected, CakePlugin::loaded());
        $this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
        $this->assertEquals('loaded plugin two bootstrap', Configure::read('CakePluginTest.test_plugin_two.bootstrap'));
    }

    /**
     * Tests loading multiple plugins with default loading params and some overrides
     */
    public function testLoadMultipleWithDefaultsAndOverride()
    {
        CakePlugin::load(
            ['TestPlugin', 'TestPluginTwo' => ['routes' => false]],
            ['bootstrap' => true, 'routes' => true]
        );
        $expected = ['TestPlugin', 'TestPluginTwo'];
        $this->assertEquals($expected, CakePlugin::loaded());
        $this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
        $this->assertEquals(null, Configure::read('CakePluginTest.test_plugin_two.bootstrap'));
    }

    /**
     * Tests that it is possible to load multiple bootstrap files at once
     */
    public function testMultipleBootstrapFiles()
    {
        CakePlugin::load('TestPlugin', ['bootstrap' => ['bootstrap', 'custom_config']]);
        $this->assertTrue(CakePlugin::loaded('TestPlugin'));
        $this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
    }

    /**
     * Tests that it is possible to load plugin bootstrap by calling a callback function
     */
    public function testCallbackBootstrap()
    {
        CakePlugin::load('TestPlugin', ['bootstrap' => [$this, 'pluginBootstrap']]);
        $this->assertTrue(CakePlugin::loaded('TestPlugin'));
        $this->assertEquals('called plugin bootstrap callback', Configure::read('CakePluginTest.test_plugin.bootstrap'));
    }

    /**
     * Tests that loading a missing routes file throws a warning
     *
     * @expectedException PHPUNIT_FRAMEWORK_ERROR_WARNING
     */
    public function testLoadMultipleWithDefaultsMissingFile()
    {
        CakePlugin::load(['TestPlugin', 'TestPluginTwo'], ['bootstrap' => true, 'routes' => true]);
        CakePlugin::routes();
    }

    /**
     * Test ignoring missing bootstrap/routes file
     */
    public function testIgnoreMissingFiles()
    {
        CakePlugin::loadAll([[
            'bootstrap'     => true,
            'routes'        => true,
            'ignoreMissing' => true
        ]]);
        CakePlugin::routes();
    }

    /**
     * Tests that CakePlugin::load() throws an exception on unknown plugin
     *
     * @expectedException MissingPluginException
     */
    public function testLoadNotFound()
    {
        CakePlugin::load('MissingPlugin');
    }

    /**
     * Tests that CakePlugin::path() returns the correct path for the loaded plugins
     */
    public function testPath()
    {
        CakePlugin::load(['TestPlugin', 'TestPluginTwo']);
        $expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS;
        $this->assertEquals($expected, CakePlugin::path('TestPlugin'));

        $expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPluginTwo' . DS;
        $this->assertEquals($expected, CakePlugin::path('TestPluginTwo'));
    }

    /**
     * Tests that CakePlugin::path() throws an exception on unknown plugin
     *
     * @expectedException MissingPluginException
     */
    public function testPathNotFound()
    {
        CakePlugin::path('TestPlugin');
    }

    /**
     * Tests that CakePlugin::loadAll() will load all plugins in the configured folder
     */
    public function testLoadAll()
    {
        CakePlugin::loadAll();
        $expected = ['PluginJs', 'TestPlugin', 'TestPluginTwo'];
        $this->assertEquals($expected, CakePlugin::loaded());
    }

    /**
     * Tests that CakePlugin::loadAll() will load all plugins in the configured folder with bootstrap loading
     */
    public function testLoadAllWithDefaults()
    {
        $defaults = ['bootstrap' => true];
        CakePlugin::loadAll([$defaults]);
        $expected = ['PluginJs', 'TestPlugin', 'TestPluginTwo'];
        $this->assertEquals($expected, CakePlugin::loaded());
        $this->assertEquals('loaded js plugin bootstrap', Configure::read('CakePluginTest.js_plugin.bootstrap'));
        $this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
        $this->assertEquals('loaded plugin two bootstrap', Configure::read('CakePluginTest.test_plugin_two.bootstrap'));
    }

    /**
     * Tests that CakePlugin::loadAll() will load all plugins in the configured folder with defaults
     * and merges in global defaults.
     */
    public function testLoadAllWithDefaultsAndOverride()
    {
        CakePlugin::loadAll([['bootstrap' => true], 'TestPlugin' => ['routes' => true]]);
        CakePlugin::routes();

        $expected = ['PluginJs', 'TestPlugin', 'TestPluginTwo'];
        $this->assertEquals($expected, CakePlugin::loaded());
        $this->assertEquals('loaded js plugin bootstrap', Configure::read('CakePluginTest.js_plugin.bootstrap'));
        $this->assertEquals('loaded plugin routes', Configure::read('CakePluginTest.test_plugin.routes'));
        $this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
        $this->assertEquals('loaded plugin two bootstrap', Configure::read('CakePluginTest.test_plugin_two.bootstrap'));
    }

    /**
     * Tests that CakePlugin::loadAll() will load all plugins in the configured folder with defaults
     * and overrides for a plugin
     */
    public function testLoadAllWithDefaultsAndOverrideComplex()
    {
        CakePlugin::loadAll([['bootstrap' => true], 'TestPlugin' => ['routes' => true, 'bootstrap' => false]]);
        CakePlugin::routes();

        $expected = ['PluginJs', 'TestPlugin', 'TestPluginTwo'];
        $this->assertEquals($expected, CakePlugin::loaded());
        $this->assertEquals('loaded js plugin bootstrap', Configure::read('CakePluginTest.js_plugin.bootstrap'));
        $this->assertEquals('loaded plugin routes', Configure::read('CakePluginTest.test_plugin.routes'));
        $this->assertEquals(null, Configure::read('CakePluginTest.test_plugin.bootstrap'));
        $this->assertEquals('loaded plugin two bootstrap', Configure::read('CakePluginTest.test_plugin_two.bootstrap'));
    }

    /**
     * Auxiliary function to test plugin bootstrap callbacks
     */
    public function pluginBootstrap()
    {
        Configure::write('CakePluginTest.test_plugin.bootstrap', 'called plugin bootstrap callback');
    }
}
