<?php
/**
 * HelperCollectionTest file
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
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 *
 * @package       Cake.Test.Case.View
 *
 * @since         CakePHP(tm) v 2.0
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('HelperCollection', 'View');
App::uses('HtmlHelper', 'View/Helper');
App::uses('View', 'View');

/**
 * Extended HtmlHelper
 */
class HtmlAliasHelper extends HtmlHelper
{
}

/**
 * HelperCollectionTest
 *
 * @package       Cake.Test.Case.View
 */
class HelperCollectionTest extends CakeTestCase
{
    /**
     * setUp
     */
    public function setUp()
    {
        parent::setUp();
        $this->View = $this->getMock('View', [], [null]);
        $this->Helpers = new HelperCollection($this->View);
    }

    /**
     * tearDown
     */
    public function tearDown()
    {
        CakePlugin::unload();
        unset($this->Helpers, $this->View);
        parent::tearDown();
    }

    /**
     * test triggering callbacks on loaded helpers
     */
    public function testLoad()
    {
        $result = $this->Helpers->load('Html');
        $this->assertInstanceOf('HtmlHelper', $result);
        $this->assertInstanceOf('HtmlHelper', $this->Helpers->Html);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html'], $result, 'loaded() results are wrong.');

        $this->assertTrue($this->Helpers->enabled('Html'));
    }

    /**
     * test lazy loading of helpers
     */
    public function testLazyLoad()
    {
        $result = $this->Helpers->Html;
        $this->assertInstanceOf('HtmlHelper', $result);

        $result = $this->Helpers->Form;
        $this->assertInstanceOf('FormHelper', $result);

        App::build(['Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]]);
        $this->View->plugin = 'TestPlugin';
        CakePlugin::load(['TestPlugin']);
        $result = $this->Helpers->OtherHelper;
        $this->assertInstanceOf('OtherHelperHelper', $result);
    }

    /**
     * test lazy loading of helpers
     *
     * @expectedException MissingHelperException
     */
    public function testLazyLoadException()
    {
        $this->Helpers->NotAHelper;
    }

    /**
     * Tests loading as an alias
     */
    public function testLoadWithAlias()
    {
        $result = $this->Helpers->load('Html', ['className' => 'HtmlAlias']);
        $this->assertInstanceOf('HtmlAliasHelper', $result);
        $this->assertInstanceOf('HtmlAliasHelper', $this->Helpers->Html);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html'], $result, 'loaded() results are wrong.');

        $this->assertTrue($this->Helpers->enabled('Html'));

        $result = $this->Helpers->load('Html');
        $this->assertInstanceOf('HtmlAliasHelper', $result);

        App::build(['Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]]);
        CakePlugin::load(['TestPlugin']);
        $result = $this->Helpers->load('SomeOther', ['className' => 'TestPlugin.OtherHelper']);
        $this->assertInstanceOf('OtherHelperHelper', $result);
        $this->assertInstanceOf('OtherHelperHelper', $this->Helpers->SomeOther);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html', 'SomeOther'], $result, 'loaded() results are wrong.');
        App::build();
    }

    /**
     * test that the enabled setting disables the helper.
     */
    public function testLoadWithEnabledFalse()
    {
        $result = $this->Helpers->load('Html', ['enabled' => false]);
        $this->assertInstanceOf('HtmlHelper', $result);
        $this->assertInstanceOf('HtmlHelper', $this->Helpers->Html);

        $this->assertFalse($this->Helpers->enabled('Html'), 'Html should be disabled');
    }

    /**
     * test missinghelper exception
     *
     * @expectedException MissingHelperException
     */
    public function testLoadMissingHelper()
    {
        $this->Helpers->load('ThisHelperShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin helper.
     */
    public function testLoadPluginHelper()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load(['TestPlugin']);
        $result = $this->Helpers->load('TestPlugin.OtherHelper');
        $this->assertInstanceOf('OtherHelperHelper', $result, 'Helper class is wrong.');
        $this->assertInstanceOf('OtherHelperHelper', $this->Helpers->OtherHelper, 'Class is wrong');

        App::build();
    }

    /**
     * test unload()
     */
    public function testUnload()
    {
        $this->Helpers->load('Form');
        $this->Helpers->load('Html');

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Form', 'Html'], $result, 'loaded helpers is wrong');

        $this->Helpers->unload('Html');
        $this->assertNotContains('Html', $this->Helpers->loaded());
        $this->assertContains('Form', $this->Helpers->loaded());

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Form'], $result, 'loaded helpers is wrong');
    }
}
