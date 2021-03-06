<?php
/**
 * BlowfishAuthenticateTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under the MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	  Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * @link          https://cakephp.org CakePHP(tm) Project
 *
 * @package	      Cake.Test.Case.Controller.Component.Auth
 *
 * @since	      CakePHP(tm) v 2.3
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('AuthComponent', 'Controller/Component');
App::uses('BlowfishAuthenticate', 'Controller/Component/Auth');
App::uses('AppModel', 'Model');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Security', 'Utility');

require_once CAKE . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'models.php';

/**
 * Test case for BlowfishAuthentication
 *
 * @package	Cake.Test.Case.Controller.Component.Auth
 */
class BlowfishAuthenticateTest extends CakeTestCase
{
    public $fixtures = ['core.user', 'core.auth_user'];

    /**
     * setup
     */
    public function setUp()
    {
        parent::setUp();
        $this->Collection = $this->getMock('ComponentCollection');
        $this->auth = new BlowfishAuthenticate($this->Collection, [
            'fields'    => ['username' => 'user', 'password' => 'password'],
            'userModel' => 'User'
        ]);
        $password = Security::hash('password', 'blowfish');
        $User = ClassRegistry::init('User');
        $User->updateAll(['password' => $User->getDataSource()->value($password)]);
        $this->response = $this->getMock('CakeResponse');

        $hash = Security::hash('password', 'blowfish');
        $this->skipIf(strpos($hash, '$2a$') === false, 'Skipping blowfish tests as hashing is not working');
    }

    /**
     * test applying settings in the constructor
     */
    public function testConstructor()
    {
        $Object = new BlowfishAuthenticate($this->Collection, [
            'userModel' => 'AuthUser',
            'fields'    => ['username' => 'user', 'password' => 'password']
        ]);
        $this->assertEquals('AuthUser', $Object->settings['userModel']);
        $this->assertEquals(['username' => 'user', 'password' => 'password'], $Object->settings['fields']);
    }

    /**
     * testAuthenticateNoData method
     */
    public function testAuthenticateNoData()
    {
        $request = new CakeRequest('posts/index', false);
        $request->data = [];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * testAuthenticateNoUsername method
     */
    public function testAuthenticateNoUsername()
    {
        $request = new CakeRequest('posts/index', false);
        $request->data = ['User' => ['password' => 'foobar']];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * testAuthenticateNoPassword method
     */
    public function testAuthenticateNoPassword()
    {
        $request = new CakeRequest('posts/index', false);
        $request->data = ['User' => ['user' => 'mariano']];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * testAuthenticatePasswordIsFalse method
     */
    public function testAuthenticatePasswordIsFalse()
    {
        $request = new CakeRequest('posts/index', false);
        $request->data = [
            'User' => [
                'user'     => 'mariano',
                'password' => null
            ]];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * testAuthenticateInjection method
     */
    public function testAuthenticateInjection()
    {
        $request = new CakeRequest('posts/index', false);
        $request->data = ['User' => [
            'user'     => '> 1',
            'password' => '\' OR 1 = 1'
        ]];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * testAuthenticateSuccess method
     */
    public function testAuthenticateSuccess()
    {
        $request = new CakeRequest('posts/index', false);
        $request->data = ['User' => [
            'user'     => 'mariano',
            'password' => 'password'
        ]];
        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id'      => 1,
            'user'    => 'mariano',
            'created' => '2007-03-17 01:16:23',
            'updated' => '2007-03-17 01:18:31',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testAuthenticateScopeFail method
     */
    public function testAuthenticateScopeFail()
    {
        $this->auth->settings['scope'] = ['user' => 'nate'];
        $request = new CakeRequest('posts/index', false);
        $request->data = ['User' => [
            'user'     => 'mariano',
            'password' => 'password'
        ]];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * testPluginModel method
     */
    public function testPluginModel()
    {
        Cache::delete('object_map', '_cake_core_');
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ], App::RESET);
        CakePlugin::load('TestPlugin');

        $PluginModel = ClassRegistry::init('TestPlugin.TestPluginAuthUser');
        $user['id'] = 1;
        $user['username'] = 'gwoo';
        $user['password'] = Security::hash('password', 'blowfish');
        $PluginModel->save($user, false);

        $this->auth->settings['userModel'] = 'TestPlugin.TestPluginAuthUser';
        $this->auth->settings['fields']['username'] = 'username';

        $request = new CakeRequest('posts/index', false);
        $request->data = ['TestPluginAuthUser' => [
            'username' => 'gwoo',
            'password' => 'password'
        ]];

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id'       => 1,
            'username' => 'gwoo',
            'created'  => '2007-03-17 01:16:23'
        ];
        $this->assertEquals(static::date(), $result['updated']);
        unset($result['updated']);
        $this->assertEquals($expected, $result);
        CakePlugin::unload();
    }
}
