<?php
/**
 * SocketTest file
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
 * @package       Cake.Test.Case.Network
 *
 * @since         CakePHP(tm) v 1.2.0.4206
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('CakeSocket', 'Network');

/**
 * SocketTest class
 *
 * @package       Cake.Test.Case.Network
 */
class CakeSocketTest extends CakeTestCase
{
    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();
        $this->Socket = new CakeSocket(['timeout' => 1]);
    }

    /**
     * tearDown method
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Socket);
    }

    /**
     * testConstruct method
     */
    public function testConstruct()
    {
        $this->Socket = new CakeSocket();
        $config = $this->Socket->config;
        $this->assertSame($config, [
            'persistent' => false,
            'host'       => 'localhost',
            'protocol'   => 'tcp',
            'port'       => 80,
            'timeout'    => 30,
            'cryptoType' => 'tls',
        ]);

        $this->Socket->reset();
        $this->Socket->__construct(['host' => 'foo-bar']);
        $config['host'] = 'foo-bar';
        $this->assertSame($this->Socket->config, $config);

        $this->Socket = new CakeSocket(['host' => 'www.cakephp.org', 'port' => 23, 'protocol' => 'udp']);
        $config = $this->Socket->config;

        $config['host'] = 'www.cakephp.org';
        $config['port'] = 23;
        $config['protocol'] = 'udp';

        $this->assertSame($this->Socket->config, $config);
    }

    /**
     * testSocketConnection method
     */
    public function testSocketConnection()
    {
        $this->assertFalse($this->Socket->connected);
        $this->Socket->disconnect();
        $this->assertFalse($this->Socket->connected);

        try {
            $this->Socket->connect();
            $this->assertTrue($this->Socket->connected);
            $this->Socket->connect();
            $this->assertTrue($this->Socket->connected);

            $this->Socket->disconnect();
            $config = ['persistent' => true];
            $this->Socket = new CakeSocket($config);
            $this->Socket->connect();
            $this->assertTrue($this->Socket->connected);
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * data provider function for testInvalidConnection
     *
     * @return array
     */
    public static function invalidConnections()
    {
        return [
            [['host' => 'invalid.host', 'port' => 9999, 'timeout' => 1]],
            [['host' => '127.0.0.1', 'port' => '70000', 'timeout' => 1]]
        ];
    }

    /**
     * testInvalidConnection method
     *
     * @dataProvider invalidConnections
     * @expectedException SocketException
     */
    public function testInvalidConnection($data)
    {
        $this->Socket->config = array_merge($this->Socket->config, $data);
        $this->Socket->connect();
    }

    /**
     * testSocketHost method
     */
    public function testSocketHost()
    {
        try {
            $this->Socket = new CakeSocket();
            $this->Socket->connect();
            $this->assertEquals('127.0.0.1', $this->Socket->address());
            $this->assertEquals(gethostbyaddr('127.0.0.1'), $this->Socket->host());
            $this->assertEquals(null, $this->Socket->lastError());
            $this->assertTrue(in_array('127.0.0.1', $this->Socket->addresses()));

            $this->Socket = new CakeSocket(['host' => '127.0.0.1']);
            $this->Socket->connect();
            $this->assertEquals('127.0.0.1', $this->Socket->address());
            $this->assertEquals(gethostbyaddr('127.0.0.1'), $this->Socket->host());
            $this->assertEquals(null, $this->Socket->lastError());
            $this->assertTrue(in_array('127.0.0.1', $this->Socket->addresses()));
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testSocketWriting method
     */
    public function testSocketWriting()
    {
        try {
            $request = "GET / HTTP/1.1\r\nConnection: close\r\n\r\n";
            $this->assertTrue((bool)$this->Socket->write($request));
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testSocketReading method
     */
    public function testSocketReading()
    {
        $this->Socket = new CakeSocket(['timeout' => 5]);

        try {
            $this->Socket->connect();
            $this->assertEquals(null, $this->Socket->read(26));

            $config = ['host' => 'google.com', 'port' => 80, 'timeout' => 1];
            $this->Socket = new CakeSocket($config);
            $this->assertTrue($this->Socket->connect());
            $this->assertEquals(null, $this->Socket->read(26));
            $this->assertEquals('2: ' . __d('cake_dev', 'Connection timed out'), $this->Socket->lastError());
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testTimeOutConnection method
     */
    public function testTimeOutConnection()
    {
        $config = ['host' => '127.0.0.1', 'timeout' => 0.5];
        $this->Socket = new CakeSocket($config);

        try {
            $this->assertTrue($this->Socket->connect());

            $config = ['host' => '127.0.0.1', 'timeout' => 0.00001];
            $this->Socket = new CakeSocket($config);
            $this->assertFalse($this->Socket->read(1024 * 1024));
            $this->assertEquals('2: ' . __d('cake_dev', 'Connection timed out'), $this->Socket->lastError());
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testLastError method
     */
    public function testLastError()
    {
        $this->Socket = new CakeSocket();
        $this->Socket->setLastError(4, 'some error here');
        $this->assertEquals('4: some error here', $this->Socket->lastError());
    }

    /**
     * testReset method
     */
    public function testReset()
    {
        $config = [
            'persistent' => true,
            'host'       => '127.0.0.1',
            'protocol'   => 'udp',
            'port'       => 80,
            'timeout'    => 20
        ];
        $anotherSocket = new CakeSocket($config);
        $anotherSocket->reset();
        $this->assertEquals([], $anotherSocket->config);
    }

    /**
     * testEncrypt
     *
     * @expectedException SocketException
     */
    public function testEnableCryptoSocketExceptionNoSsl()
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $configNoSslOrTls = ['host' => 'localhost', 'port' => 80, 'timeout' => 0.1];

        // testing exception on no ssl socket server for ssl and tls methods
        $this->Socket = new CakeSocket($configNoSslOrTls);
        $this->Socket->connect();
        $this->Socket->enableCrypto('sslv3', 'client');
    }

    /**
     * testEnableCryptoSocketExceptionNoTls
     *
     * @expectedException SocketException
     */
    public function testEnableCryptoSocketExceptionNoTls()
    {
        $configNoSslOrTls = ['host' => 'localhost', 'port' => 80, 'timeout' => 0.1];

        // testing exception on no ssl socket server for ssl and tls methods
        $this->Socket = new CakeSocket($configNoSslOrTls);
        $this->Socket->connect();
        $this->Socket->enableCrypto('tls', 'client');
    }

    /**
     * Test that protocol in the host doesn't cause cert errors.
     */
    public function testConnectProtocolInHost()
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $configSslTls = ['host' => 'ssl://smtp.gmail.com', 'port' => 465, 'timeout' => 5];
        $socket = new CakeSocket($configSslTls);

        try {
            $socket->connect();
            $this->assertEquals('smtp.gmail.com', $socket->config['host']);
            $this->assertEquals('ssl', $socket->config['protocol']);
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * _connectSocketToSslTls
     */
    protected function _connectSocketToSslTls()
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $configSslTls = ['host' => 'smtp.gmail.com', 'port' => 465, 'timeout' => 5];
        $this->Socket = new CakeSocket($configSslTls);

        try {
            $this->Socket->connect();
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testEnableCryptoBadMode
     *
     * @expectedException InvalidArgumentException
     */
    public function testEnableCryptoBadMode()
    {
        // testing wrong encryption mode
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('doesntExistMode', 'server');
        $this->Socket->disconnect();
    }

    /**
     * testEnableCrypto
     */
    public function testEnableCrypto()
    {
        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->assertTrue($this->Socket->enableCrypto('tls', 'client'));
        $this->Socket->disconnect();
    }

    /**
     * testEnableCrypto tlsv1_1
     */
    public function testEnableCryptoTlsV11()
    {
        $this->skipIf(!defined('STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT'), 'TLS1.1 is not supported on this system');

        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->assertTrue($this->Socket->enableCrypto('tlsv1_1', 'client'));
        $this->Socket->disconnect();
    }

    /**
     * testEnableCryptoExceptionEnableTwice
     *
     * @expectedException SocketException
     */
    public function testEnableCryptoExceptionEnableTwice()
    {
        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('tls', 'client');
        $this->Socket->enableCrypto('tls', 'client');
    }

    /**
     * testEnableCryptoExceptionDisableTwice
     *
     * @expectedException SocketException
     */
    public function testEnableCryptoExceptionDisableTwice()
    {
        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('tls', 'client', false);
    }

    /**
     * testEnableCryptoEnableStatus
     */
    public function testEnableCryptoEnableStatus()
    {
        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->assertFalse($this->Socket->encrypted);
        $this->Socket->enableCrypto('tls', 'client', true);
        $this->assertTrue($this->Socket->encrypted);
    }

    /**
     * test getting the context for a socket.
     */
    public function testGetContext()
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $config = [
            'host'    => 'smtp.gmail.com',
            'port'    => 465,
            'timeout' => 5,
            'context' => [
                'ssl' => ['capture_peer' => true]
            ]
        ];
        $this->Socket = new CakeSocket($config);
        $this->Socket->connect();
        $result = $this->Socket->context();
        $this->assertSame($config['context']['ssl']['capture_peer'], $result['ssl']['capture_peer']);
    }

    /**
     * test configuring the context from the flat keys.
     */
    public function testConfigContext()
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $config = [
            'host'                  => 'smtp.gmail.com',
            'port'                  => 465,
            'timeout'               => 5,
            'ssl_verify_peer'       => true,
            'ssl_allow_self_signed' => false,
            'ssl_verify_depth'      => 5,
            'ssl_verify_host'       => true,
        ];
        $this->Socket = new CakeSocket($config);

        $this->Socket->connect();
        $result = $this->Socket->context();

        $this->assertTrue($result['ssl']['verify_peer']);
        $this->assertFalse($result['ssl']['allow_self_signed']);
        $this->assertEquals(5, $result['ssl']['verify_depth']);
        $this->assertEquals('smtp.gmail.com', $result['ssl']['CN_match']);
        $this->assertArrayNotHasKey('ssl_verify_peer', $this->Socket->config);
        $this->assertArrayNotHasKey('ssl_allow_self_signed', $this->Socket->config);
        $this->assertArrayNotHasKey('ssl_verify_host', $this->Socket->config);
        $this->assertArrayNotHasKey('ssl_verify_depth', $this->Socket->config);
    }
}
