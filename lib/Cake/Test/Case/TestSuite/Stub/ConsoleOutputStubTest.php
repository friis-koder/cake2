<?php
/**
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
 * @since         2.8
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('ConsoleOutputStub', 'TestSuite/Stub');

/*
 * ConsoleOutputStub test
 */
class ConsoleOutputStubTest extends CakeTestCase
{
    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();

        $this->stub = new ConsoleOutputStub();
    }

    /**
     * Test that stub can be used as an instance of ConsoleOutput
     */
    public function testCanActAsConsoleOutput()
    {
        $this->assertInstanceOf('ConsoleOutput', $this->stub);
    }

    /**
     * Test write method
     */
    public function testWrite()
    {
        $this->stub->write(['foo', 'bar', 'baz']);
        $this->assertEquals(['foo', 'bar', 'baz'], $this->stub->messages());
    }

    /**
     * Test overwrite method
     */
    public function testOverwrite()
    {
        $this->stub->write(['foo', 'bar', 'baz']);
        $this->stub->overwrite('bat');
        $this->assertEquals(['foo', 'bar', 'baz', '', 'bat'], $this->stub->messages());
    }
}
