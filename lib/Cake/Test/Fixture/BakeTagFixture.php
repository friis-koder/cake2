<?php
/**
 * BakeTagFixture
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
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 *
 * @package       Cake.Test.Fixture
 *
 * @since         CakePHP(tm) v 2.0
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class BakeTagFixture extends CakeTestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id'      => ['type' => 'integer', 'key' => 'primary'],
        'tag'     => ['type' => 'string', 'null' => false],
        'created' => 'datetime',
        'updated' => 'datetime'
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [];
}
