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
 * @since         CakePHP(tm) v 1.2.0.4667
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * UnderscoreFieldFixture class
 *
 * @package       Cake.Test.Fixture
 */
class UnderscoreFieldFixture extends CakeTestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id'                   => ['type' => 'integer', 'key' => 'primary'],
        'user_id'              => ['type' => 'integer', 'null' => false],
        'my_model_has_a_field' => ['type' => 'string', 'null' => false],
        'body_field'           => 'text',
        'published'            => ['type' => 'string', 'length' => 1, 'default' => 'N'],
        'another_field'        => ['type' => 'integer', 'length' => 3],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['user_id' => 1, 'my_model_has_a_field' => 'First Article', 'body_field' => 'First Article Body', 'published' => 'Y', 'another_field' => 2],
        ['user_id' => 3, 'my_model_has_a_field' => 'Second Article', 'body_field' => 'Second Article Body', 'published' => 'Y', 'another_field' => 3],
        ['user_id' => 1, 'my_model_has_a_field' => 'Third Article', 'body_field' => 'Third Article Body', 'published' => 'Y', 'another_field' => 5],
    ];
}
