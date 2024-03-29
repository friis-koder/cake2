<?php
/**
 * MemcacheEngineTest file
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
 * @package       Cake.Test.Case.Cache.Engine
 *
 * @since         CakePHP(tm) v 1.2.0.5434
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Cache', 'Cache');
App::uses('MemcacheEngine', 'Cache/Engine');

/**
 * TestMemcacheEngine
 *
 * @package       Cake.Test.Case.Cache.Engine
 */
class TestMemcacheEngine extends MemcacheEngine
{
    /**
     * public accessor to _parseServerString
     *
     * @param string $server
     *
     * @return array
     */
    public function parseServerString($server)
    {
        return $this->_parseServerString($server);
    }

    public function setMemcache($memcache)
    {
        $this->_Memcache = $memcache;
    }
}

/**
 * MemcacheEngineTest class
 *
 * @package       Cake.Test.Case.Cache.Engine
 */
class MemcacheEngineTest extends CakeTestCase
{
    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();
        $this->skipIf(!class_exists('Memcache'), 'Memcache is not installed or configured properly.');

        $this->_cacheDisable = Configure::read('Cache.disable');
        Configure::write('Cache.disable', false);
        Cache::config('memcache', [
            'engine'   => 'Memcache',
            'prefix'   => 'cake_',
            'duration' => 3600
        ]);
    }

    /**
     * tearDown method
     */
    public function tearDown()
    {
        parent::tearDown();
        Configure::write('Cache.disable', $this->_cacheDisable);
        Cache::drop('memcache');
        Cache::drop('memcache_groups');
        Cache::drop('memcache_helper');
        Cache::config('default');
    }

    /**
     * testSettings method
     */
    public function testSettings()
    {
        $settings = Cache::settings('memcache');
        unset($settings['serialize'], $settings['path']);
        $expecting = [
            'prefix'      => 'cake_',
            'duration'    => 3600,
            'probability' => 100,
            'servers'     => ['127.0.0.1'],
            'persistent'  => true,
            'compress'    => false,
            'engine'      => 'Memcache',
            'groups'      => []
        ];
        $this->assertEquals($expecting, $settings);
    }

    /**
     * testSettings method
     */
    public function testMultipleServers()
    {
        $servers = ['127.0.0.1:11211', '127.0.0.1:11222'];
        $available = true;
        $Memcache = new Memcache();

        foreach ($servers as $server) {
            list($host, $port) = explode(':', $server);
            //@codingStandardsIgnoreStart
            if (!@$Memcache->connect($host, $port)) {
                $available = false;
            }
            //@codingStandardsIgnoreEnd
        }

        $this->skipIf(!$available, 'Need memcache servers at ' . implode(', ', $servers) . ' to run this test.');

        $Memcache = new MemcacheEngine();
        $Memcache->init(['engine' => 'Memcache', 'servers' => $servers]);

        $settings = $Memcache->settings();
        $this->assertEquals($settings['servers'], $servers);
        Cache::drop('dual_server');
    }

    /**
     * testConnect method
     */
    public function testConnect()
    {
        $Memcache = new MemcacheEngine();
        $Memcache->init(Cache::settings('memcache'));
        $result = $Memcache->connect('127.0.0.1');
        $this->assertTrue($result);
    }

    /**
     * test connecting to an ipv6 server.
     */
    public function testConnectIpv6()
    {
        $Memcache = new MemcacheEngine();
        $result = $Memcache->init([
            'prefix'   => 'cake_',
            'duration' => 200,
            'engine'   => 'Memcache',
            'servers'  => [
                '[::1]:11211'
            ]
        ]);
        $this->assertTrue($result);
    }

    /**
     * test domain starts with u
     */
    public function testParseServerStringWithU()
    {
        $Memcached = new TestMemcachedEngine();
        $result = $Memcached->parseServerString('udomain.net:13211');
        $this->assertEquals(['udomain.net', '13211'], $result);
    }

    /**
     * test non latin domains.
     */
    public function testParseServerStringNonLatin()
    {
        $Memcache = new TestMemcacheEngine();
        $result = $Memcache->parseServerString('schülervz.net:13211');
        $this->assertEquals(['schülervz.net', '13211'], $result);

        $result = $Memcache->parseServerString('sülül:1111');
        $this->assertEquals(['sülül', '1111'], $result);
    }

    /**
     * test unix sockets.
     */
    public function testParseServerStringUnix()
    {
        $Memcache = new TestMemcacheEngine();
        $result = $Memcache->parseServerString('unix:///path/to/memcached.sock');
        $this->assertEquals(['unix:///path/to/memcached.sock', 0], $result);
    }

    /**
     * testReadAndWriteCache method
     */
    public function testReadAndWriteCache()
    {
        Cache::set(['duration' => 1], null, 'memcache');

        $result = Cache::read('test', 'memcache');
        $expecting = '';
        $this->assertEquals($expecting, $result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('test', $data, 'memcache');
        $this->assertTrue($result);

        $result = Cache::read('test', 'memcache');
        $expecting = $data;
        $this->assertEquals($expecting, $result);

        Cache::delete('test', 'memcache');
    }

    /**
     * testExpiry method
     */
    public function testExpiry()
    {
        Cache::set(['duration' => 1], 'memcache');

        $result = Cache::read('test', 'memcache');
        $this->assertFalse($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'memcache');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'memcache');
        $this->assertFalse($result);

        Cache::set(['duration' => '+1 second'], 'memcache');

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'memcache');
        $this->assertTrue($result);

        sleep(3);
        $result = Cache::read('other_test', 'memcache');
        $this->assertFalse($result);

        Cache::config('memcache', ['duration' => '+1 second']);

        $result = Cache::read('other_test', 'memcache');
        $this->assertFalse($result);

        Cache::config('memcache', ['duration' => '+29 days']);
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('long_expiry_test', $data, 'memcache');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('long_expiry_test', 'memcache');
        $expecting = $data;
        $this->assertEquals($expecting, $result);

        Cache::config('memcache', ['duration' => 3600]);
    }

    /**
     * testDeleteCache method
     */
    public function testDeleteCache()
    {
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('delete_test', $data, 'memcache');
        $this->assertTrue($result);

        $result = Cache::delete('delete_test', 'memcache');
        $this->assertTrue($result);
    }

    /**
     * testDecrement method
     */
    public function testDecrement()
    {
        $result = Cache::write('test_decrement', 5, 'memcache');
        $this->assertTrue($result);

        $result = Cache::decrement('test_decrement', 1, 'memcache');
        $this->assertEquals(4, $result);

        $result = Cache::read('test_decrement', 'memcache');
        $this->assertEquals(4, $result);

        $result = Cache::decrement('test_decrement', 2, 'memcache');
        $this->assertEquals(2, $result);

        $result = Cache::read('test_decrement', 'memcache');
        $this->assertEquals(2, $result);
    }

    /**
     * testIncrement method
     */
    public function testIncrement()
    {
        $result = Cache::write('test_increment', 5, 'memcache');
        $this->assertTrue($result);

        $result = Cache::increment('test_increment', 1, 'memcache');
        $this->assertEquals(6, $result);

        $result = Cache::read('test_increment', 'memcache');
        $this->assertEquals(6, $result);

        $result = Cache::increment('test_increment', 2, 'memcache');
        $this->assertEquals(8, $result);

        $result = Cache::read('test_increment', 'memcache');
        $this->assertEquals(8, $result);
    }

    /**
     * test that configurations don't conflict, when a file engine is declared after a memcache one.
     */
    public function testConfigurationConflict()
    {
        Cache::config('long_memcache', [
            'engine'   => 'Memcache',
            'duration' => '+2 seconds',
            'servers'  => ['127.0.0.1:11211'],
        ]);
        Cache::config('short_memcache', [
            'engine'   => 'Memcache',
            'duration' => '+1 seconds',
            'servers'  => ['127.0.0.1:11211'],
        ]);
        Cache::config('some_file', ['engine' => 'File']);

        $this->assertTrue(Cache::write('duration_test', 'yay', 'long_memcache'));
        $this->assertTrue(Cache::write('short_duration_test', 'boo', 'short_memcache'));

        $this->assertEquals('yay', Cache::read('duration_test', 'long_memcache'), 'Value was not read %s');
        $this->assertEquals('boo', Cache::read('short_duration_test', 'short_memcache'), 'Value was not read %s');

        sleep(1);
        $this->assertEquals('yay', Cache::read('duration_test', 'long_memcache'), 'Value was not read %s');

        sleep(2);
        $this->assertFalse(Cache::read('short_duration_test', 'short_memcache'), 'Cache was not invalidated %s');
        $this->assertFalse(Cache::read('duration_test', 'long_memcache'), 'Value did not expire %s');

        Cache::delete('duration_test', 'long_memcache');
        Cache::delete('short_duration_test', 'short_memcache');
    }

    /**
     * test clearing memcache.
     */
    public function testClear()
    {
        Cache::config('memcache2', [
            'engine'   => 'Memcache',
            'prefix'   => 'cake2_',
            'duration' => 3600
        ]);

        Cache::write('some_value', 'cache1', 'memcache');
        $result = Cache::clear(true, 'memcache');
        $this->assertTrue($result);
        $this->assertEquals('cache1', Cache::read('some_value', 'memcache'));

        Cache::write('some_value', 'cache2', 'memcache2');
        $result = Cache::clear(false, 'memcache');
        $this->assertTrue($result);
        $this->assertFalse(Cache::read('some_value', 'memcache'));
        $this->assertEquals('cache2', Cache::read('some_value', 'memcache2'));

        Cache::clear(false, 'memcache2');
    }

    /**
     * test that a 0 duration can successfully write.
     */
    public function testZeroDuration()
    {
        Cache::config('memcache', ['duration' => 0]);
        $result = Cache::write('test_key', 'written!', 'memcache');

        $this->assertTrue($result);
        $result = Cache::read('test_key', 'memcache');
        $this->assertEquals('written!', $result);
    }

    /**
     * test that durations greater than 30 days never expire
     */
    public function testLongDurationEqualToZero()
    {
        $memcache = new TestMemcacheEngine();
        $memcache->settings['compress'] = false;

        $mock = $this->getMock('Memcache');
        $memcache->setMemcache($mock);
        $mock->expects($this->once())
            ->method('set')
            ->with('key', 'value', false, 0);

        $value = 'value';
        $memcache->write('key', $value, 50 * DAY);
    }

    /**
     * Tests that configuring groups for stored keys return the correct values when read/written
     * Shows that altering the group value is equivalent to deleting all keys under the same
     * group
     */
    public function testGroupReadWrite()
    {
        Cache::config('memcache_groups', [
            'engine'   => 'Memcache',
            'duration' => 3600,
            'groups'   => ['group_a', 'group_b'],
            'prefix'   => 'test_'
        ]);
        Cache::config('memcache_helper', [
            'engine'   => 'Memcache',
            'duration' => 3600,
            'prefix'   => 'test_'
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'memcache_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'memcache_groups'));

        Cache::increment('group_a', 1, 'memcache_helper');
        $this->assertFalse(Cache::read('test_groups', 'memcache_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value2', 'memcache_groups'));
        $this->assertEquals('value2', Cache::read('test_groups', 'memcache_groups'));

        Cache::increment('group_b', 1, 'memcache_helper');
        $this->assertFalse(Cache::read('test_groups', 'memcache_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value3', 'memcache_groups'));
        $this->assertEquals('value3', Cache::read('test_groups', 'memcache_groups'));
    }

    /**
     * Tests that deleteing from a groups-enabled config is possible
     */
    public function testGroupDelete()
    {
        Cache::config('memcache_groups', [
            'engine'   => 'Memcache',
            'duration' => 3600,
            'groups'   => ['group_a', 'group_b']
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'memcache_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'memcache_groups'));
        $this->assertTrue(Cache::delete('test_groups', 'memcache_groups'));

        $this->assertFalse(Cache::read('test_groups', 'memcache_groups'));
    }

    /**
     * Test clearing a cache group
     */
    public function testGroupClear()
    {
        Cache::config('memcache_groups', [
            'engine'   => 'Memcache',
            'duration' => 3600,
            'groups'   => ['group_a', 'group_b']
        ]);

        $this->assertTrue(Cache::write('test_groups', 'value', 'memcache_groups'));
        $this->assertTrue(Cache::clearGroup('group_a', 'memcache_groups'));
        $this->assertFalse(Cache::read('test_groups', 'memcache_groups'));

        $this->assertTrue(Cache::write('test_groups', 'value2', 'memcache_groups'));
        $this->assertTrue(Cache::clearGroup('group_b', 'memcache_groups'));
        $this->assertFalse(Cache::read('test_groups', 'memcache_groups'));
    }

    /**
     * Test that failed add write return false.
     */
    public function testAdd()
    {
        Cache::delete('test_add_key', 'memcache');

        $result = Cache::add('test_add_key', 'test data', 'memcache');
        $this->assertTrue($result);

        $expected = 'test data';
        $result = Cache::read('test_add_key', 'memcache');
        $this->assertEquals($expected, $result);

        $result = Cache::add('test_add_key', 'test data 2', 'memcache');
        $this->assertFalse($result);
    }
}
