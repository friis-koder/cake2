<?php
/**
 * CakeFixtureManager file
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
 * @link          https://cakephp.org CakePHP Project
 *
 * @package       Cake.Test.Case.TestSuite.Fixture
 *
 * @since         CakePHP v 2.5
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('DboSource', 'Model/Datasource');
App::uses('CakeFixtureManager', 'TestSuite/Fixture');
App::uses('UuidFixture', 'Test/Fixture');

/**
 * Test Case for CakeFixtureManager class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeFixtureManagerTest extends CakeTestCase
{
    /**
     * reset environment.
     */
    public function setUp()
    {
        parent::setUp();
        $this->fixtureManager = new CakeFixtureManager();
    }

    /**
     * tearDown
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->fixtureManager);
    }

    /**
     * testLoadTruncatesTable
     */
    public function testLoadTruncatesTable()
    {
        $MockFixture = $this->getMock('UuidFixture', ['truncate']);
        $MockFixture
            ->expects($this->once())
            ->method('truncate')
            ->will($this->returnValue(true));

        $fixtureManager = $this->fixtureManager;
        $fixtureManagerReflection = new ReflectionClass($fixtureManager);

        $loadedProperty = $fixtureManagerReflection->getProperty('_loaded');
        $loadedProperty->setAccessible(true);
        $loadedProperty->setValue($fixtureManager, ['core.uuid' => $MockFixture]);

        $TestCase = $this->getMock('CakeTestCase');
        $TestCase->fixtures = ['core.uuid'];
        $TestCase->autoFixtures = true;
        $TestCase->dropTables = false;

        $fixtureManager->load($TestCase);
    }

    /**
     * testLoadSingleTruncatesTable
     */
    public function testLoadSingleTruncatesTable()
    {
        $MockFixture = $this->getMock('UuidFixture', ['truncate']);
        $MockFixture
            ->expects($this->once())
            ->method('truncate')
            ->will($this->returnValue(true));

        $fixtureManager = $this->fixtureManager;
        $fixtureManagerReflection = new ReflectionClass($fixtureManager);

        $fixtureMapProperty = $fixtureManagerReflection->getProperty('_fixtureMap');
        $fixtureMapProperty->setAccessible(true);
        $fixtureMapProperty->setValue($fixtureManager, ['UuidFixture' => $MockFixture]);

        $dboMethods = array_diff(get_class_methods('DboSource'), ['enabled']);
        $dboMethods[] = 'connect';
        $db = $this->getMock('DboSource', $dboMethods);
        $db->config['prefix'] = '';

        $fixtureManager->loadSingle('Uuid', $db, false);
    }
}
