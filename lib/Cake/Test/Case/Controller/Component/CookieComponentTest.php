<?php
/**
 * CookieComponentTest file
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
 * @package       Cake.Test.Case.Controller.Component
 *
 * @since         CakePHP(tm) v 1.2.0.5435
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Component', 'Controller');
App::uses('Controller', 'Controller');
App::uses('CookieComponent', 'Controller/Component');

/**
 * CookieComponentTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class CookieComponentTestController extends Controller
{
    /**
     * components property
     *
     * @var array
     */
    public $components = ['Cookie'];

    /**
     * beforeFilter method
     */
    public function beforeFilter()
    {
        $this->Cookie->name = 'CakeTestCookie';
        $this->Cookie->time = 10;
        $this->Cookie->path = '/';
        $this->Cookie->domain = '';
        $this->Cookie->secure = false;
        $this->Cookie->key = 'somerandomhaskey';
    }
}

/**
 * CookieComponentTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class CookieComponentTest extends CakeTestCase
{
    /**
     * Controller property
     *
     * @var CookieComponentTestController
     */
    public $Controller;

    /**
     * start
     */
    public function setUp()
    {
        parent::setUp();
        $_COOKIE = [];
        $this->Controller = new CookieComponentTestController(new CakeRequest(), new CakeResponse());
        $this->Controller->constructClasses();
        $this->Cookie = $this->Controller->Cookie;

        $this->Cookie->name = 'CakeTestCookie';
        $this->Cookie->time = 10;
        $this->Cookie->path = '/';
        $this->Cookie->domain = '';
        $this->Cookie->secure = false;
        $this->Cookie->key = 'somerandomhaskey';

        $this->Cookie->startup($this->Controller);
    }

    /**
     * end
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->Cookie->destroy();
    }

    /**
     * sets up some default cookie data.
     */
    protected function _setCookieData()
    {
        $this->Cookie->write(['Encrytped_array' => ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!']]);
        $this->Cookie->write(['Encrytped_multi_cookies.name' => 'CakePHP']);
        $this->Cookie->write(['Encrytped_multi_cookies.version' => '1.2.0.x']);
        $this->Cookie->write(['Encrytped_multi_cookies.tag' => 'CakePHP Rocks!']);

        $this->Cookie->write(['Plain_array' => ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!']], null, false);
        $this->Cookie->write(['Plain_multi_cookies.name' => 'CakePHP'], null, false);
        $this->Cookie->write(['Plain_multi_cookies.version' => '1.2.0.x'], null, false);
        $this->Cookie->write(['Plain_multi_cookies.tag' => 'CakePHP Rocks!'], null, false);
    }

    /**
     * test that initialize sets settings from components array
     */
    public function testSettings()
    {
        $settings = [
            'time' => '5 days',
            'path' => '/'
        ];
        $Cookie = new CookieComponent(new ComponentCollection(), $settings);
        $this->assertEquals($Cookie->time, $settings['time']);
        $this->assertEquals($Cookie->path, $settings['path']);
    }

    /**
     * testCookieName
     */
    public function testCookieName()
    {
        $this->assertEquals('CakeTestCookie', $this->Cookie->name);
    }

    /**
     * testReadEncryptedCookieData
     */
    public function testReadEncryptedCookieData()
    {
        $this->_setCookieData();
        $data = $this->Cookie->read('Encrytped_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);
    }

    /**
     * test read operations on corrupted cookie data.
     */
    public function testReadCorruptedCookieData()
    {
        $this->Cookie->type('aes');
        $this->Cookie->key = sha1('some bad key');

        $data = $this->_implode(['name' => 'jill', 'age' => 24]);
        // Corrupt the cookie data by slicing some bytes off.
        $_COOKIE['CakeTestCookie'] = [
            'BadData' => substr(Security::encrypt($data, $this->Cookie->key), 0, -5)
        ];
        $this->assertFalse($this->Cookie->check('BadData.name'), 'Key does not exist');
        $this->assertNull($this->Cookie->read('BadData.name'), 'Key does not exist');
    }

    /**
     * testReadPlainCookieData
     */
    public function testReadPlainCookieData()
    {
        $this->_setCookieData();
        $data = $this->Cookie->read('Plain_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);
    }

    /**
     * test read array keys from string data.
     */
    public function testReadNestedDataFromStrings()
    {
        $_COOKIE['CakeTestCookie'] = [
            'User' => 'bad data'
        ];
        $this->assertFalse($this->Cookie->check('User.name'), 'No key');
        $this->assertNull($this->Cookie->read('User.name'), 'No key');
    }

    /**
     * test read() after switching the cookie name.
     */
    public function testReadWithNameSwitch()
    {
        $_COOKIE = [
            'CakeTestCookie' => [
                'key' => 'value'
            ],
            'OtherTestCookie' => [
                'key' => 'other value'
            ]
        ];
        $this->assertEquals('value', $this->Cookie->read('key'));

        $this->Cookie->name = 'OtherTestCookie';
        $this->assertEquals('other value', $this->Cookie->read('key'));
    }

    /**
     * test a simple write()
     */
    public function testWriteSimple()
    {
        $this->Cookie->write('Testing', 'value');
        $result = $this->Cookie->read('Testing');

        $this->assertEquals('value', $result);
    }

    /**
     * test write() encrypted data with falsey value
     */
    public function testWriteWithFalseyValue()
    {
        $this->skipIf(!extension_loaded('mcrypt'), 'No Mcrypt, skipping.');
        $this->Cookie->type('aes');
        $this->Cookie->key = 'qSI232qs*&sXOw!adre@34SAv!@*(XSL#$%)asGb$@11~_+!@#HKis~#^';

        $this->Cookie->write('Testing');
        $result = $this->Cookie->read('Testing');
        $this->assertNull($result);

        $this->Cookie->write('Testing', '');
        $result = $this->Cookie->read('Testing');
        $this->assertEquals('', $result);

        $this->Cookie->write('Testing', false);
        $result = $this->Cookie->read('Testing');
        $this->assertFalse($result);

        $this->Cookie->write('Testing', 1);
        $result = $this->Cookie->read('Testing');
        $this->assertEquals(1, $result);

        $this->Cookie->write('Testing', '0');
        $result = $this->Cookie->read('Testing');
        $this->assertSame('0', $result);

        $this->Cookie->write('Testing', 0);
        $result = $this->Cookie->read('Testing');
        $this->assertSame(0, $result);
    }

    /**
     * test that two write() calls use the expiry.
     */
    public function testWriteMultipleShareExpiry()
    {
        $this->Cookie->write('key1', 'value1', false);
        $this->Cookie->write('key2', 'value2', false);

        $name = $this->Cookie->name . '[key1]';
        $result = $this->Controller->response->cookie($name);
        $this->assertWithinMargin(time() + 10, $result['expire'], 2, 'Expiry time is wrong');

        $name = $this->Cookie->name . '[key2]';
        $result = $this->Controller->response->cookie($name);
        $this->assertWithinMargin(time() + 10, $result['expire'], 2, 'Expiry time is wrong');
    }

    /**
     * test write with distant future cookies
     */
    public function testWriteFarFuture()
    {
        $this->Cookie->write('Testing', 'value', false, '+90 years');
        $future = new DateTime('now');
        $future->modify('+90 years');

        $expected = [
            'name'     => $this->Cookie->name . '[Testing]',
            'value'    => 'value',
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httpOnly' => false,
            'sameSite' => 'None'
        ];
        $result = $this->Controller->response->cookie($this->Cookie->name . '[Testing]');

        $this->assertEquals($future->format('U'), $result['expire'], '', 3);
        unset($result['expire']);

        $this->assertEquals($expected, $result);
    }

    /**
     * test write with httpOnly cookies
     */
    public function testWriteHttpOnly()
    {
        $this->Cookie->httpOnly = true;
        $this->Cookie->secure = false;
        $this->Cookie->write('Testing', 'value', false);
        $expected = [
            'name'     => $this->Cookie->name . '[Testing]',
            'value'    => 'value',
            'expire'   => time() + 10,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httpOnly' => true,
            'sameSite' => 'None'
        ];
        $result = $this->Controller->response->cookie($this->Cookie->name . '[Testing]');
        $this->assertEquals($expected, $result);
    }

    /**
     * test delete with httpOnly
     */
    public function testDeleteHttpOnly()
    {
        $this->Cookie->httpOnly = true;
        $this->Cookie->secure = false;
        $this->Cookie->delete('Testing', false);
        $expected = [
            'name'     => $this->Cookie->name . '[Testing]',
            'value'    => '',
            'expire'   => time() - 42000,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httpOnly' => true,
            'sameSite' => 'None'
        ];
        $result = $this->Controller->response->cookie($this->Cookie->name . '[Testing]');
        $this->assertEquals($expected, $result);
    }

    /**
     * testWritePlainCookieArray
     */
    public function testWritePlainCookieArray()
    {
        $this->Cookie->write(['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'], null, false);

        $this->assertEquals('CakePHP', $this->Cookie->read('name'));
        $this->assertEquals('1.2.0.x', $this->Cookie->read('version'));
        $this->assertEquals('CakePHP Rocks!', $this->Cookie->read('tag'));

        $this->Cookie->delete('name');
        $this->Cookie->delete('version');
        $this->Cookie->delete('tag');
    }

    /**
     * test writing values that are not scalars
     */
    public function testWriteArrayValues()
    {
        $this->Cookie->secure = false;
        $this->Cookie->write('Testing', [1, 2, 3], false);
        $expected = [
            'name'     => $this->Cookie->name . '[Testing]',
            'value'    => '[1,2,3]',
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httpOnly' => false,
            'sameSite' => 'None'
        ];
        $result = $this->Controller->response->cookie($this->Cookie->name . '[Testing]');

        $this->assertWithinMargin($result['expire'], time() + 10, 1);
        unset($result['expire']);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that writing mixed arrays results in the correct data.
     */
    public function testWriteMixedArray()
    {
        $this->Cookie->encrypt = false;
        $this->Cookie->write('User', ['name' => 'mark'], false);
        $this->Cookie->write('User.email', 'mark@example.com', false);
        $expected = [
            'name'     => $this->Cookie->name . '[User]',
            'value'    => '{"name":"mark","email":"mark@example.com"}',
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httpOnly' => false,
            'sameSite' => 'None'
        ];
        $result = $this->Controller->response->cookie($this->Cookie->name . '[User]');
        unset($result['expire']);

        $this->assertEquals($expected, $result);

        $this->Cookie->write('User.email', 'mark@example.com', false);
        $this->Cookie->write('User', ['name' => 'mark'], false);
        $expected = [
            'name'     => $this->Cookie->name . '[User]',
            'value'    => '{"name":"mark"}',
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httpOnly' => false,
            'sameSite' => 'None'
        ];
        $result = $this->Controller->response->cookie($this->Cookie->name . '[User]');
        unset($result['expire']);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that replacing scalar with array works.
     */
    public function testReplaceScalarWithArray()
    {
        $this->Cookie->write('foo', 1);
        $this->Cookie->write('foo.bar', 2);

        $data = $this->Cookie->read();
        $expected = [
            'foo' => [
                'bar' => 2
            ]
        ];
        $this->assertEquals($expected, $data);
    }

    /**
     * testReadingCookieValue
     */
    public function testReadingCookieValue()
    {
        $this->_setCookieData();
        $data = $this->Cookie->read();
        $expected = [
            'Encrytped_array' => [
                'name'    => 'CakePHP',
                'version' => '1.2.0.x',
                'tag'     => 'CakePHP Rocks!'],
            'Encrytped_multi_cookies' => [
                'name'    => 'CakePHP',
                'version' => '1.2.0.x',
                'tag'     => 'CakePHP Rocks!'],
            'Plain_array' => [
                'name'    => 'CakePHP',
                'version' => '1.2.0.x',
                'tag'     => 'CakePHP Rocks!'],
            'Plain_multi_cookies' => [
                'name'    => 'CakePHP',
                'version' => '1.2.0.x',
                'tag'     => 'CakePHP Rocks!']];
        $this->assertEquals($expected, $data);
    }

    /**
     * testDeleteCookieValue
     */
    public function testDeleteCookieValue()
    {
        $this->_setCookieData();
        $this->Cookie->delete('Encrytped_multi_cookies.name');
        $data = $this->Cookie->read('Encrytped_multi_cookies');
        $expected = ['version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $this->Cookie->delete('Encrytped_array');
        $data = $this->Cookie->read('Encrytped_array');
        $this->assertNull($data);

        $this->Cookie->delete('Plain_multi_cookies.name');
        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = ['version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $this->Cookie->delete('Plain_array');
        $data = $this->Cookie->read('Plain_array');
        $this->assertNull($data);
    }

    /**
     * test delete() on corrupted/truncated cookie data.
     */
    public function testDeleteCorruptedCookieData()
    {
        $this->Cookie->type('aes');
        $this->Cookie->key = sha1('some bad key');

        $data = $this->_implode(['name' => 'jill', 'age' => 24]);
        // Corrupt the cookie data by slicing some bytes off.
        $_COOKIE['CakeTestCookie'] = [
            'BadData' => substr(Security::encrypt($data, $this->Cookie->key), 0, -5)
        ];

        $this->assertNull($this->Cookie->delete('BadData.name'));
        $this->assertNull($this->Cookie->read('BadData.name'));
    }

    /**
     * testReadingCookieArray
     */
    public function testReadingCookieArray()
    {
        $this->_setCookieData();

        $data = $this->Cookie->read('Encrytped_array.name');
        $expected = 'CakePHP';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_array.version');
        $expected = '1.2.0.x';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_array.tag');
        $expected = 'CakePHP Rocks!';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies.name');
        $expected = 'CakePHP';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies.version');
        $expected = '1.2.0.x';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies.tag');
        $expected = 'CakePHP Rocks!';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array.name');
        $expected = 'CakePHP';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array.version');
        $expected = '1.2.0.x';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array.tag');
        $expected = 'CakePHP Rocks!';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies.name');
        $expected = 'CakePHP';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies.version');
        $expected = '1.2.0.x';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies.tag');
        $expected = 'CakePHP Rocks!';
        $this->assertEquals($expected, $data);
    }

    /**
     * testReadingCookieDataOnStartup
     */
    public function testReadingCookieDataOnStartup()
    {
        $data = $this->Cookie->read('Encrytped_array');
        $this->assertNull($data);

        $data = $this->Cookie->read('Encrytped_multi_cookies');
        $this->assertNull($data);

        $data = $this->Cookie->read('Plain_array');
        $this->assertNull($data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $this->assertNull($data);

        $_COOKIE['CakeTestCookie'] = [
            'Encrytped_array'         => $this->_encrypt(['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!']),
            'Encrytped_multi_cookies' => [
                'name'    => $this->_encrypt('CakePHP'),
                'version' => $this->_encrypt('1.2.0.x'),
                'tag'     => $this->_encrypt('CakePHP Rocks!')],
            'Plain_array'         => '{"name":"CakePHP","version":"1.2.0.x","tag":"CakePHP Rocks!"}',
            'Plain_multi_cookies' => [
                'name'    => 'CakePHP',
                'version' => '1.2.0.x',
                'tag'     => 'CakePHP Rocks!']];

        $this->Cookie->startup(new CookieComponentTestController());

        $data = $this->Cookie->read('Encrytped_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);
        $this->Cookie->destroy();
        unset($_COOKIE['CakeTestCookie']);
    }

    /**
     * testReadingCookieDataWithoutStartup
     */
    public function testReadingCookieDataWithoutStartup()
    {
        $data = $this->Cookie->read('Encrytped_array');
        $expected = null;
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies');
        $expected = null;
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array');
        $expected = null;
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = null;
        $this->assertEquals($expected, $data);

        $_COOKIE['CakeTestCookie'] = [
            'Encrytped_array'         => $this->_encrypt(['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!']),
            'Encrytped_multi_cookies' => [
                'name'    => $this->_encrypt('CakePHP'),
                'version' => $this->_encrypt('1.2.0.x'),
                'tag'     => $this->_encrypt('CakePHP Rocks!')],
            'Plain_array'         => '{"name":"CakePHP","version":"1.2.0.x","tag":"CakePHP Rocks!"}',
            'Plain_multi_cookies' => [
                'name'    => 'CakePHP',
                'version' => '1.2.0.x',
                'tag'     => 'CakePHP Rocks!']];

        $data = $this->Cookie->read('Encrytped_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);
        $this->Cookie->destroy();
        unset($_COOKIE['CakeTestCookie']);
    }

    /**
     * Test Reading legacy cookie values.
     */
    public function testReadLegacyCookieValue()
    {
        $_COOKIE['CakeTestCookie'] = [
            'Legacy' => ['value' => $this->_oldImplode([1, 2, 3])]
        ];
        $result = $this->Cookie->read('Legacy.value');
        $expected = [1, 2, 3];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test reading empty values.
     */
    public function testReadEmpty()
    {
        $_COOKIE['CakeTestCookie'] = [
            'JSON'   => '{"name":"value"}',
            'Empty'  => '',
            'String' => '{"somewhat:"broken"}',
            'Array'  => '{}'
        ];
        $this->assertEquals(['name' => 'value'], $this->Cookie->read('JSON'));
        $this->assertEquals('value', $this->Cookie->read('JSON.name'));
        $this->assertEquals('', $this->Cookie->read('Empty'));
        $this->assertEquals('{"somewhat:"broken"}', $this->Cookie->read('String'));
        $this->assertEquals([], $this->Cookie->read('Array'));
    }

    /**
     * Test reading empty key
     */
    public function testReadEmptyKey()
    {
        $_COOKIE['CakeTestCookie'] = [
            '0'   => '{"name":"value"}',
            'foo' => ['bar'],
        ];
        $this->assertEquals('value', $this->Cookie->read('0.name'));
        $this->assertEquals('bar', $this->Cookie->read('foo.0'));
    }

    /**
     * test that no error is issued for non array data.
     */
    public function testNoErrorOnNonArrayData()
    {
        $this->Cookie->destroy();
        $_COOKIE['CakeTestCookie'] = 'kaboom';

        $this->assertNull($this->Cookie->read('value'));
    }

    /**
     * testCheck method
     */
    public function testCheck()
    {
        $this->Cookie->write('CookieComponentTestCase', 'value');
        $this->assertTrue($this->Cookie->check('CookieComponentTestCase'));

        $this->assertFalse($this->Cookie->check('NotExistingCookieComponentTestCase'));
    }

    /**
     * testCheckingSavedEmpty method
     */
    public function testCheckingSavedEmpty()
    {
        $this->Cookie->write('CookieComponentTestCase', 0);
        $this->assertTrue($this->Cookie->check('CookieComponentTestCase'));

        $this->Cookie->write('CookieComponentTestCase', '0');
        $this->assertTrue($this->Cookie->check('CookieComponentTestCase'));

        $this->Cookie->write('CookieComponentTestCase', false);
        $this->assertTrue($this->Cookie->check('CookieComponentTestCase'));

        $this->Cookie->write('CookieComponentTestCase', null);
        $this->assertFalse($this->Cookie->check('CookieComponentTestCase'));
    }

    /**
     * testCheckKeyWithSpaces method
     */
    public function testCheckKeyWithSpaces()
    {
        $this->Cookie->write('CookieComponent Test', 'test');
        $this->assertTrue($this->Cookie->check('CookieComponent Test'));
        $this->Cookie->delete('CookieComponent Test');

        $this->Cookie->write('CookieComponent Test.Test Case', 'test');
        $this->assertTrue($this->Cookie->check('CookieComponent Test.Test Case'));
    }

    /**
     * testCheckEmpty
     */
    public function testCheckEmpty()
    {
        $this->assertFalse($this->Cookie->check());
    }

    /**
     * test that deleting a top level keys kills the child elements too.
     */
    public function testDeleteRemovesChildren()
    {
        $_COOKIE['CakeTestCookie'] = [
            'User'  => ['email' => 'example@example.com', 'name' => 'mark'],
            'other' => 'value'
        ];
        $this->assertEquals('mark', $this->Cookie->read('User.name'));

        $this->Cookie->delete('User');
        $this->assertNull($this->Cookie->read('User.email'));
        $this->Cookie->destroy();
    }

    /**
     * Test deleting recursively with keys that don't exist.
     */
    public function testDeleteChildrenNotExist()
    {
        $this->assertNull($this->Cookie->delete('NotFound'));
        $this->assertNull($this->Cookie->delete('Not.Found'));
    }

    /**
     * Test deleting deep child elements sends correct cookies.
     */
    public function testDeleteDeepChildren()
    {
        $_COOKIE = [
            'CakeTestCookie' => [
                'foo' => $this->_encrypt([
                    'bar' => [
                        'baz' => 'value',
                    ],
                ]),
            ],
        ];

        $this->Cookie->delete('foo.bar.baz');

        $cookies = $this->Controller->response->cookie();
        $expected = [
            'CakeTestCookie[foo]' => [
                'name'     => 'CakeTestCookie[foo]',
                'value'    => '{"bar":[]}',
                'path'     => '/',
                'domain'   => '',
                'secure'   => false,
                'httpOnly' => false,
                'sameSite' => 'None'
            ],
        ];

        $expires = Hash::combine($cookies, '{*}.name', '{*}.expire');
        $cookies = Hash::remove($cookies, '{*}.expire');
        $this->assertEquals($expected, $cookies);

        $this->assertWithinMargin($expires['CakeTestCookie[foo]'], time() + 10, 2);
    }

    /**
     * Test destroy works.
     */
    public function testDestroy()
    {
        $_COOKIE = [
            'CakeTestCookie' => [
                'foo' => $this->_encrypt([
                    'bar' => [
                        'baz' => 'value',
                    ],
                ]),
                'other' => 'value',
            ],
        ];

        $this->Cookie->destroy();

        $cookies = $this->Controller->response->cookie();
        $expected = [
            'CakeTestCookie[foo]' => [
                'name'     => 'CakeTestCookie[foo]',
                'value'    => '',
                'path'     => '/',
                'domain'   => '',
                'secure'   => false,
                'httpOnly' => false,
                'sameSite' => 'None'
            ],
            'CakeTestCookie[other]' => [
                'name'     => 'CakeTestCookie[other]',
                'value'    => '',
                'path'     => '/',
                'domain'   => '',
                'secure'   => false,
                'httpOnly' => false,
                'sameSite' => 'None'
            ],
        ];

        $expires = Hash::combine($cookies, '{*}.name', '{*}.expire');
        $cookies = Hash::remove($cookies, '{*}.expire');
        $this->assertEquals($expected, $cookies);
        $this->assertWithinMargin($expires['CakeTestCookie[foo]'], time() - 42000, 2);
        $this->assertWithinMargin($expires['CakeTestCookie[other]'], time() - 42000, 2);
    }

    /**
     * Helper method for generating old style encoded cookie values.
     *
     * @return string.
     */
    protected function _oldImplode(array $array)
    {
        $string = '';
        foreach ($array as $key => $value) {
            $string .= ',' . $key . '|' . $value;
        }

        return substr($string, 1);
    }

    /**
     * Implode method to keep keys are multidimensional arrays
     *
     * @param array $array Map of key and values
     *
     * @return string String in the form key1|value1,key2|value2
     */
    protected function _implode(array $array)
    {
        return json_encode($array);
    }

    /**
     * encrypt method
     *
     * @param array|string $value
     *
     * @return string
     */
    protected function _encrypt($value)
    {
        if (is_array($value)) {
            $value = $this->_implode($value);
        }

        return 'Q2FrZQ==.' . base64_encode(Security::cipher($value, $this->Cookie->key));
    }
}
