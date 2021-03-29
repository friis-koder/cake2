<?php
/**
 * ControllerAuthorizeTest file
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
 * @package       Cake.Test.Case.Controller.Component.Auth
 *
 * @since         CakePHP(tm) v 2.0
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Controller', 'Controller');
App::uses('ControllerAuthorize', 'Controller/Component/Auth');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

/**
 * ControllerAuthorizeTest
 *
 * @package       Cake.Test.Case.Controller.Component.Auth
 */
class ControllerAuthorizeTest extends CakeTestCase
{
    /**
     * setup
     */
    public function setUp()
    {
        parent::setUp();
        $this->controller = $this->getMock('Controller', ['isAuthorized'], [], '', false);
        $this->components = $this->getMock('ComponentCollection');
        $this->components->expects($this->any())
            ->method('getController')
            ->will($this->returnValue($this->controller));

        $this->auth = new ControllerAuthorize($this->components);
    }

    /**
     * testControllerTypeError
     *
     * @expectedException PHPUnit_Framework_Error
     *
     * @throws PHPUnit_Framework_Error
     */
    public function testControllerTypeError()
    {
        try {
            $this->auth->controller(new StdClass());
            $this->fail('No exception thrown');
        } catch (TypeError $e) {
            throw new PHPUnit_Framework_Error('Raised an error', 100, __FILE__, __LINE__);
        }
    }

    /**
     * testControllerErrorOnMissingMethod
     *
     * @expectedException CakeException
     */
    public function testControllerErrorOnMissingMethod()
    {
        $this->auth->controller(new Controller());
    }

    /**
     * test failure
     */
    public function testAuthorizeFailure()
    {
        $user = [];
        $request = new CakeRequest('/posts/index', false);
        $this->assertFalse($this->auth->authorize($user, $request));
    }

    /**
     * test isAuthorized working.
     */
    public function testAuthorizeSuccess()
    {
        $user = ['User' => ['username' => 'mark']];
        $request = new CakeRequest('/posts/index', false);

        $this->controller->expects($this->once())
            ->method('isAuthorized')
            ->with($user)
            ->will($this->returnValue(true));

        $this->assertTrue($this->auth->authorize($user, $request));
    }
}
