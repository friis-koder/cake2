<?php
/**
 * FlashHelperTest file
 *
 * Series of tests for flash helper.
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
 * @package       Cake.Test.Case.View.Helper
 *
 * @since         CakePHP(tm) v 2.7.0-dev
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('FlashHelper', 'View/Helper');
App::uses('View', 'View');
App::uses('CakePlugin', 'Core');

/**
 * FlashHelperTest class
 *
 * @package		Cake.Test.Case.View.Helper
 */
class FlashHelperTest extends CakeTestCase
{
    /**
     * setupBeforeClass method
     */
    public static function setupBeforeClass()
    {
        App::build([
            'View' => [CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS]
        ]);
    }

    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();
        $controller = null;
        $this->View = new View($controller);
        $this->Flash = new FlashHelper($this->View);

        if (!CakeSession::started()) {
            CakeSession::start();
        }
        CakeSession::write([
            'Message' => [
                'flash' => [
                    [
                        'key'     => 'flash',
                        'message' => 'This is the first Message',
                        'element' => 'Flash/default',
                        'params'  => []
                    ],
                    [
                        'key'     => 'flash',
                        'message' => 'This is the second Message',
                        'element' => 'Flash/default',
                        'params'  => []
                    ]
                ],
                'notification' => [
                    [
                        'key'     => 'notification',
                        'message' => 'Broadcast message testing',
                        'element' => 'flash_helper',
                        'params'  => [
                            'title' => 'Notice!',
                            'name'  => 'Alert!'
                        ]
                    ]
                ],
                'classy' => [
                    [
                        'key'     => 'classy',
                        'message' => 'Recorded',
                        'element' => 'flash_classy',
                        'params'  => []
                    ]
                ],
                'default' => [
                    [
                        'key'     => 'default',
                        'message' => 'Default',
                        'element' => 'default',
                        'params'  => []
                    ]
                ]
            ]
        ]);
    }

    /**
     * tearDown method
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->View, $this->Flash);
        CakeSession::destroy();
    }

    /**
     * testFlash method
     */
    public function testFlash()
    {
        $result = $this->Flash->render();
        $expected = '<div class="message">This is the first Message</div><div class="message">This is the second Message</div>';
        $this->assertContains($expected, $result);

        $expected = '<div id="classy-message">Recorded</div>';
        $result = $this->Flash->render('classy');
        $this->assertContains($expected, $result);

        $result = $this->Flash->render('notification');
        $expected = "<div id=\"notificationLayout\">\n\t<h1>Alert!</h1>\n\t<h3>Notice!</h3>\n\t<p>Broadcast message testing</p>\n</div>";
        $this->assertContains($expected, $result);

        $this->assertNull($this->Flash->render('non-existent'));
    }

    /**
     * testFlashThrowsException
     *
     * @expectedException UnexpectedValueException
     */
    public function testFlashThrowsException()
    {
        CakeSession::write('Message.foo', 'bar');
        $this->Flash->render('foo');
    }

    /**
     * test setting the element from the attrs.
     */
    public function testFlashElementInAttrs()
    {
        $result = $this->Flash->render('notification', [
            'element' => 'flash_helper',
            'params'  => ['title' => 'Alert!', 'name' => 'Notice!']
        ]);

        $expected = "<div id=\"notificationLayout\">\n\t<h1>Notice!</h1>\n\t<h3>Alert!</h3>\n\t<p>Broadcast message testing</p>\n</div>";

        $this->assertContains($expected, $result);
    }

    /**
     * test using elements in plugins.
     */
    public function testFlashWithPluginElement()
    {
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ]);
        CakePlugin::load('TestPlugin');

        $result = $this->Flash->render('flash', ['element' => 'TestPlugin.plugin_element']);
        $expected = 'this is the plugin element';
        $this->assertContains($expected, $result);
    }

    /**
     * Test that the default element fallbacks to the Flash/default element.
     */
    public function testFlashFallback()
    {
        $result = $this->Flash->render('default');
        $expected = '<div class="message">Default</div>';
        $this->assertContains($expected, $result);
    }
}
