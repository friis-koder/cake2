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
 * @since         CakePHP(tm) v 1.2.0.5669
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TranslateTableFixture
 *
 * @package       Cake.Test.Fixture
 */
class TranslateTableFixture extends CakeTestFixture
{
    /**
     * table property
     *
     * @var string
     */
    public $table = 'another_i18n';

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id'          => ['type' => 'integer', 'key' => 'primary'],
        'locale'      => ['type' => 'string', 'length' => 6, 'null' => false],
        'model'       => ['type' => 'string', 'null' => false],
        'foreign_key' => ['type' => 'integer', 'null' => false],
        'field'       => ['type' => 'string', 'null' => false],
        'content'     => ['type' => 'text']];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['locale' => 'eng', 'model' => 'TranslatedItemWithTable', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Another Title #1'],
        ['locale' => 'eng', 'model' => 'TranslatedItemWithTable', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Another Content #1']
    ];
}
