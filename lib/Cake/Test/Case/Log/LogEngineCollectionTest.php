<?php
/**
 * LogEngineCollectionTest file
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
 * @package       Cake.Test.Case.Log
 *
 * @since         CakePHP(tm) v 2.4
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('LogEngineCollection', 'Log');
App::uses('FileLog', 'Log/Engine');

/**
 * LoggerEngineLog class
 */
class LoggerEngineLog extends FileLog
{
}

/**
 * LogEngineCollectionTest class
 *
 * @package       Cake.Test.Case.Log
 */
class LogEngineCollectionTest extends CakeTestCase
{
    public $Collection;

    /**
     * Start test callback
     */
    public function setUp()
    {
        parent::setUp();

        $this->Collection = new LogEngineCollection();
    }

    /**
     * test load
     */
    public function testLoad()
    {
        $result = $this->Collection->load('key', ['engine' => 'File']);
        $this->assertInstanceOf('CakeLogInterface', $result);
    }

    /**
     * test load with deprecated Log suffix
     */
    public function testLoadWithSuffix()
    {
        $result = $this->Collection->load('key', ['engine' => 'FileLog']);
        $this->assertInstanceOf('CakeLogInterface', $result);
    }

    /**
     * test that engines starting with Log also work properly
     */
    public function testLoadWithSuffixAtBeginning()
    {
        $result = $this->Collection->load('key', ['engine' => 'LoggerEngine']);
        $this->assertInstanceOf('CakeLogInterface', $result);
    }

    /**
     * test load with invalid Log
     *
     * @expectedException CakeLogException
     */
    public function testLoadInvalid()
    {
        $result = $this->Collection->load('key', ['engine' => 'ImaginaryFile']);
        $this->assertInstanceOf('CakeLogInterface', $result);
    }
}
