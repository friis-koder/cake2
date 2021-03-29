<?php
/**
 * CacheSessionTest
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
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
 * @package       Cake.Test.Case.Model.Datasource.Session
 *
 * @since         CakePHP(tm) v 2.0
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('CakeSession', 'Model/Datasource');
App::uses('CacheSession', 'Model/Datasource/Session');
class_exists('CakeSession');

/**
 * CacheSessionTest
 *
 * @package       Cake.Test.Case.Model.Datasource.Session
 */
class CacheSessionTest extends CakeTestCase
{
    protected static $_sessionBackup;

    /**
     * test case startup
     */
    public static function setupBeforeClass()
    {
        Cache::config('session_test', [
            'engine' => 'File',
            'prefix' => 'session_test_'
        ]);
        static::$_sessionBackup = Configure::read('Session');

        Configure::write('Session.handler.config', 'session_test');
    }

    /**
     * cleanup after test case.
     */
    public static function teardownAfterClass()
    {
        Cache::clear(false, 'session_test');
        Cache::drop('session_test');

        Configure::write('Session', static::$_sessionBackup);
    }

    /**
     * setup
     */
    public function setUp()
    {
        parent::setUp();
        $this->storage = new CacheSession();
    }

    /**
     * tearDown
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->storage);
    }

    /**
     * test open
     */
    public function testOpen()
    {
        $this->assertTrue($this->storage->open());
    }

    /**
     * test write()
     */
    public function testWrite()
    {
        $this->storage->write('abc', 'Some value');
        $this->assertEquals('Some value', Cache::read('abc', 'session_test'), 'Value was not written.');
        $this->assertFalse(Cache::read('abc', 'default'), 'Cache should only write to the given config.');
    }

    /**
     * test reading.
     */
    public function testRead()
    {
        $this->storage->write('test_one', 'Some other value');
        $this->assertEquals('Some other value', $this->storage->read('test_one'), 'Incorrect value.');
        $this->storage->write('test_two', 0);
        $this->assertEquals(0, $this->storage->read('test_two'));
    }

    /**
     * test destroy
     */
    public function testDestroy()
    {
        $this->storage->write('test_one', 'Some other value');
        $this->assertTrue($this->storage->destroy('test_one'), 'Value was not deleted.');

        $this->assertFalse(Cache::read('test_one', 'session_test'), 'Value stuck around.');
    }
}
