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
 * @since         CakePHP(tm) v 1.2.0.6317
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * JoinCFixture
 *
 * @package       Cake.Test.Fixture
 */
class JoinCFixture extends CakeTestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id'      => ['type' => 'integer', 'key' => 'primary'],
        'name'    => ['type' => 'string', 'default' => ''],
        'created' => ['type' => 'datetime', 'null' => true],
        'updated' => ['type' => 'datetime', 'null' => true]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['name' => 'Join C 1', 'created' => '2008-01-03 10:56:11', 'updated' => '2008-01-03 10:56:11'],
        ['name' => 'Join C 2', 'created' => '2008-01-03 10:56:12', 'updated' => '2008-01-03 10:56:12'],
        ['name' => 'Join C 3', 'created' => '2008-01-03 10:56:13', 'updated' => '2008-01-03 10:56:13']
    ];
}
