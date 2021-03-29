<?php
/**
 * AllTestSuiteTest file
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
 * @package       Cake.Test.Case
 *
 * @since         CakePHP(tm) v 2.0
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * AllTestSuiteTest class
 *
 * This test group will run all test suite tests.
 *
 * @package       Cake.Test.Case
 */
class AllTestSuiteTest extends PHPUnit_Framework_TestSuite
{
    /**
     * suite method, defines tests for this suite.
     */
    public static function suite()
    {
        $suite = new CakeTestSuite('All Test Suite classes tests');

        $suite->addTestDirectory(CORE_TEST_CASES . DS . 'TestSuite');

        return $suite;
    }
}
