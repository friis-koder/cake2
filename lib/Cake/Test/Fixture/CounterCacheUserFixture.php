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
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class CounterCacheUserFixture extends CakeTestFixture
{
    public $fields = [
        'id'              => ['type' => 'integer', 'key' => 'primary'],
        'name'            => ['type' => 'string', 'length' => 255, 'null' => false],
        'post_count'      => ['type' => 'integer', 'null' => true],
        'posts_published' => ['type' => 'integer', 'null' => true]
    ];

    public $records = [
        ['id' => 66, 'name' => 'Alexander', 'post_count' => 2, 'posts_published' => 1],
        ['id' => 301, 'name' => 'Steven', 'post_count' => 1, 'posts_published' => 1],
    ];
}
