<?php
/**
 * CakeRequest Test case file.
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
 * @package       Cake.Test.Case.Routing.Route
 *
 * @since         CakePHP(tm) v 2.0
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('PluginShortRoute', 'Routing/Route');
App::uses('Router', 'Routing');

/**
 * test case for PluginShortRoute
 *
 * @package       Cake.Test.Case.Routing.Route
 */
class PluginShortRouteTest extends CakeTestCase
{
    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Routing', ['admin' => null, 'prefixes' => []]);
        Router::reload();
    }

    /**
     * test the parsing of routes.
     */
    public function testParsing()
    {
        $route = new PluginShortRoute('/:plugin', ['action' => 'index'], ['plugin' => 'foo|bar']);

        $result = $route->parse('/foo');
        $this->assertEquals('foo', $result['plugin']);
        $this->assertEquals('foo', $result['controller']);
        $this->assertEquals('index', $result['action']);

        $result = $route->parse('/wrong');
        $this->assertFalse($result, 'Wrong plugin name matched %s');
    }

    /**
     * test the reverse routing of the plugin shortcut URLs.
     */
    public function testMatch()
    {
        $route = new PluginShortRoute('/:plugin', ['action' => 'index'], ['plugin' => 'foo|bar']);

        $result = $route->match(['plugin' => 'foo', 'controller' => 'posts', 'action' => 'index']);
        $this->assertFalse($result, 'plugin controller mismatch was converted. %s');

        $result = $route->match(['plugin' => 'foo', 'controller' => 'foo', 'action' => 'index']);
        $this->assertEquals('/foo', $result);
    }
}
