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
 * @since         CakePHP(tm) v 2.1
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * GuildsPlayerFixture
 *
 * @package       Cake.Test.Fixture
 */
class GuildsPlayerFixture extends CakeTestFixture
{
    public $useDbConfig = 'test2';

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id'        => ['type' => 'integer', 'key' => 'primary'],
        'player_id' => ['type' => 'integer', 'null' => false],
        'guild_id'  => ['type' => 'integer', 'null' => false],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['player_id' => 1, 'guild_id' => 1],
        ['player_id' => 1, 'guild_id' => 2],
        ['player_id' => 4, 'guild_id' => 3],
    ];
}
