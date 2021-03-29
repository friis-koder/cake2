<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 2.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Fixture class for the default session configuration
 *
 * @package       Cake.Test.Fixture
 */
class CakeSessionFixture extends CakeTestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id'      => ['type' => 'string', 'length' => 128, 'key' => 'primary'],
        'data'    => ['type' => 'text', 'null' => true],
        'expires' => ['type' => 'integer', 'length' => 11, 'null' => true]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [];
}
