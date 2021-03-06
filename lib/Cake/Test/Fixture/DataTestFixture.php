<?php
/**
 * Short description for file.
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
 * @package       Cake.Test.Fixture
 *
 * @since         CakePHP(tm) v 1.2.0.6700
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class DataTestFixture extends CakeTestFixture
{
    /**
     * Fields property
     *
     * @var array
     */
    public $fields = [
        'id'      => ['type' => 'integer', 'key' => 'primary'],
        'count'   => ['type' => 'integer', 'default' => 0],
        'float'   => ['type' => 'float', 'default' => 0],
        'created' => ['type' => 'datetime', 'default' => null],
        'updated' => ['type' => 'datetime', 'default' => null]
    ];

    /**
     * Records property
     *
     * @var array
     */
    public $records = [
        [
            'count'   => 2,
            'float'   => 2.4,
            'created' => '2010-09-06 12:28:00',
            'updated' => '2010-09-06 12:28:00'
        ]
    ];
}
