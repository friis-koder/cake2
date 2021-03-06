<?php
/**
 * DboMysqlTest file
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
 * @package       Cake.Test.Case.Model.Datasource.Database
 *
 * @since         CakePHP(tm) v 1.2.0
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('Mysql', 'Model/Datasource/Database');
App::uses('CakeSchema', 'Model');

require_once dirname(dirname(dirname(__FILE__))) . DS . 'models.php';

/**
 * DboMysqlTest class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class MysqlTest extends CakeTestCase
{
    /**
     * autoFixtures property
     *
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = [
        'core.apple', 'core.article', 'core.articles_tag', 'core.attachment', 'core.comment',
        'core.sample', 'core.tag', 'core.user', 'core.post', 'core.author', 'core.data_test',
        'core.binary_test', 'core.inno', 'core.unsigned'
    ];

    /**
     * The Dbo instance to be tested
     *
     * @var DboSource
     */
    public $Dbo = null;

    /**
     * Sets up a Dbo class instance for testing
     */
    public function setUp()
    {
        parent::setUp();
        $this->Dbo = ConnectionManager::getDataSource('test');
        if (!($this->Dbo instanceof Mysql)) {
            $this->markTestSkipped('The MySQL extension is not available.');
        }
        $this->_debug = Configure::read('debug');
        Configure::write('debug', 1);
        $this->model = ClassRegistry::init('MysqlTestModel');
    }

    /**
     * Sets up a Dbo class instance for testing
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->model);
        ClassRegistry::flush();
        Configure::write('debug', $this->_debug);
    }

    /**
     * Test Dbo value method
     *
     * @group quoting
     */
    public function testQuoting()
    {
        $result = $this->Dbo->fields($this->model);
        $expected = [
            '`MysqlTestModel`.`id`',
            '`MysqlTestModel`.`client_id`',
            '`MysqlTestModel`.`name`',
            '`MysqlTestModel`.`login`',
            '`MysqlTestModel`.`passwd`',
            '`MysqlTestModel`.`addr_1`',
            '`MysqlTestModel`.`addr_2`',
            '`MysqlTestModel`.`zip_code`',
            '`MysqlTestModel`.`city`',
            '`MysqlTestModel`.`country`',
            '`MysqlTestModel`.`phone`',
            '`MysqlTestModel`.`fax`',
            '`MysqlTestModel`.`url`',
            '`MysqlTestModel`.`email`',
            '`MysqlTestModel`.`comments`',
            '`MysqlTestModel`.`last_login`',
            '`MysqlTestModel`.`created`',
            '`MysqlTestModel`.`updated`'
        ];
        $this->assertEquals($expected, $result);

        $expected = 1.2;
        $result = $this->Dbo->value(1.2, 'float');
        $this->assertEquals($expected, $result);

        $expected = '\'1,2\'';
        $result = $this->Dbo->value('1,2', 'float');
        $this->assertEquals($expected, $result);

        $expected = '\'4713e29446\'';
        $result = $this->Dbo->value('4713e29446');

        $this->assertEquals($expected, $result);

        $expected = 'NULL';
        $result = $this->Dbo->value('', 'integer');
        $this->assertEquals($expected, $result);

        $expected = '\'0\'';
        $result = $this->Dbo->value('', 'boolean');
        $this->assertEquals($expected, $result);

        $expected = 10010001;
        $result = $this->Dbo->value(10010001);
        $this->assertEquals($expected, $result);

        $expected = '\'00010010001\'';
        $result = $this->Dbo->value('00010010001');
        $this->assertEquals($expected, $result);
    }

    /**
     * test that localized floats don't cause trouble.
     *
     * @group quoting
     */
    public function testLocalizedFloats()
    {
        $this->skipIf(DS === '\\', 'The locale is not supported in Windows and affect the others tests.');

        $restore = setlocale(LC_NUMERIC, 0);

        $this->skipIf(setlocale(LC_NUMERIC, 'de_DE') === false, 'The German locale isn\'t available.');

        $result = $this->Dbo->value(3.141593);
        $this->assertEquals('3.141593', $result);

        $result = $this->db->value(3.141593, 'float');
        $this->assertEquals('3.141593', $result);

        $result = $this->db->value(1234567.11, 'float');
        $this->assertEquals('1234567.11', $result);

        $result = $this->db->value(123456.45464748, 'float');
        $this->assertContains('123456.454647', $result);

        $result = $this->db->value(0.987654321, 'float');
        $this->assertEquals('0.987654321', (string)$result);

        $result = $this->db->value(2.2E-54, 'float');
        $this->assertEquals('2.2E-54', (string)$result);

        $result = $this->db->value(2.2E-54);
        $this->assertEquals('2.2E-54', (string)$result);

        setlocale(LC_NUMERIC, $restore);
    }

    /**
     * test that scientific notations are working correctly
     */
    public function testScientificNotation()
    {
        $result = $this->db->value(2.2E-54, 'float');
        $this->assertEquals('2.2E-54', (string)$result);

        $result = $this->db->value(2.2E-54);
        $this->assertEquals('2.2E-54', (string)$result);
    }

    /**
     * testTinyintCasting method
     */
    public function testTinyintCasting()
    {
        $this->Dbo->cacheSources = false;
        $tableName = 'tinyint_' . uniqid();
        $this->Dbo->rawQuery('CREATE TABLE ' . $this->Dbo->fullTableName($tableName) . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), tiny_int tinyint(2), primary key(id));');

        $this->model = new CakeTestModel([
            'name' => 'Tinyint', 'table' => $tableName, 'ds' => 'test'
        ]);

        $result = $this->model->schema();
        $this->assertEquals('boolean', $result['bool']['type']);
        $this->assertEquals('tinyinteger', $result['tiny_int']['type']);

        $this->assertTrue((bool)$this->model->save(['bool' => 5, 'tiny_int' => 5]));
        $result = $this->model->find('first');
        $this->assertTrue($result['Tinyint']['bool']);
        $this->assertSame($result['Tinyint']['tiny_int'], '5');
        $this->model->deleteAll(true);

        $this->assertTrue((bool)$this->model->save(['bool' => 0, 'tiny_int' => 100]));
        $result = $this->model->find('first');
        $this->assertFalse($result['Tinyint']['bool']);
        $this->assertSame($result['Tinyint']['tiny_int'], '100');
        $this->model->deleteAll(true);

        $this->assertTrue((bool)$this->model->save(['bool' => true, 'tiny_int' => 0]));
        $result = $this->model->find('first');
        $this->assertTrue($result['Tinyint']['bool']);
        $this->assertSame($result['Tinyint']['tiny_int'], '0');
        $this->model->deleteAll(true);

        $this->Dbo->rawQuery('DROP TABLE ' . $this->Dbo->fullTableName($tableName));
    }

    /**
     * testLastAffected method
     */
    public function testLastAffected()
    {
        $this->Dbo->cacheSources = false;
        $tableName = 'tinyint_' . uniqid();
        $this->Dbo->rawQuery('CREATE TABLE ' . $this->Dbo->fullTableName($tableName) . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id));');

        $this->model = new CakeTestModel([
            'name' => 'Tinyint', 'table' => $tableName, 'ds' => 'test'
        ]);

        $this->assertTrue((bool)$this->model->save(['bool' => 5, 'small_int' => 5]));
        $this->assertEquals(1, $this->model->find('count'));
        $this->model->deleteAll(true);
        $result = $this->Dbo->lastAffected();
        $this->assertEquals(1, $result);
        $this->assertEquals(0, $this->model->find('count'));

        $this->Dbo->rawQuery('DROP TABLE ' . $this->Dbo->fullTableName($tableName));
    }

    /**
     * testIndexDetection method
     *
     * @group indices
     */
    public function testIndexDetection()
    {
        $this->Dbo->cacheSources = false;

        $name = $this->Dbo->fullTableName('simple');
        $this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id));');
        $expected = ['PRIMARY' => ['column' => 'id', 'unique' => 1]];
        $result = $this->Dbo->index('simple', false);
        $this->Dbo->rawQuery('DROP TABLE ' . $name);
        $this->assertEquals($expected, $result);

        $name = $this->Dbo->fullTableName('bigint');
        $this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id bigint(20) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id));');
        $expected = ['PRIMARY' => ['column' => 'id', 'unique' => 1]];
        $result = $this->Dbo->index('bigint', false);
        $this->Dbo->rawQuery('DROP TABLE ' . $name);
        $this->assertEquals($expected, $result);

        $name = $this->Dbo->fullTableName('with_a_key');
        $this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id), KEY `pointless_bool` ( `bool` ));');
        $expected = [
            'PRIMARY'        => ['column' => 'id', 'unique' => 1],
            'pointless_bool' => ['column' => 'bool', 'unique' => 0],
        ];
        $result = $this->Dbo->index('with_a_key', false);
        $this->Dbo->rawQuery('DROP TABLE ' . $name);
        $this->assertEquals($expected, $result);

        $name = $this->Dbo->fullTableName('with_two_keys');
        $this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id), KEY `pointless_bool` ( `bool` ), KEY `pointless_small_int` ( `small_int` ));');
        $expected = [
            'PRIMARY'             => ['column' => 'id', 'unique' => 1],
            'pointless_bool'      => ['column' => 'bool', 'unique' => 0],
            'pointless_small_int' => ['column' => 'small_int', 'unique' => 0],
        ];
        $result = $this->Dbo->index('with_two_keys', false);
        $this->Dbo->rawQuery('DROP TABLE ' . $name);
        $this->assertEquals($expected, $result);

        $name = $this->Dbo->fullTableName('with_compound_keys');
        $this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id), KEY `pointless_bool` ( `bool` ), KEY `pointless_small_int` ( `small_int` ), KEY `one_way` ( `bool`, `small_int` ));');
        $expected = [
            'PRIMARY'             => ['column' => 'id', 'unique' => 1],
            'pointless_bool'      => ['column' => 'bool', 'unique' => 0],
            'pointless_small_int' => ['column' => 'small_int', 'unique' => 0],
            'one_way'             => ['column' => ['bool', 'small_int'], 'unique' => 0],
        ];
        $result = $this->Dbo->index('with_compound_keys', false);
        $this->Dbo->rawQuery('DROP TABLE ' . $name);
        $this->assertEquals($expected, $result);

        $name = $this->Dbo->fullTableName('with_multiple_compound_keys');
        $this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id), KEY `pointless_bool` ( `bool` ), KEY `pointless_small_int` ( `small_int` ), KEY `one_way` ( `bool`, `small_int` ), KEY `other_way` ( `small_int`, `bool` ));');
        $expected = [
            'PRIMARY'             => ['column' => 'id', 'unique' => 1],
            'pointless_bool'      => ['column' => 'bool', 'unique' => 0],
            'pointless_small_int' => ['column' => 'small_int', 'unique' => 0],
            'one_way'             => ['column' => ['bool', 'small_int'], 'unique' => 0],
            'other_way'           => ['column' => ['small_int', 'bool'], 'unique' => 0],
        ];
        $result = $this->Dbo->index('with_multiple_compound_keys', false);
        $this->Dbo->rawQuery('DROP TABLE ' . $name);
        $this->assertEquals($expected, $result);

        $name = $this->Dbo->fullTableName('with_fulltext');
        $this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, name varchar(255), description text, primary key(id), FULLTEXT KEY `MyFtIndex` ( `name`, `description` )) ENGINE=MyISAM;');
        $expected = [
            'PRIMARY'   => ['column' => 'id', 'unique' => 1],
            'MyFtIndex' => ['column' => ['name', 'description'], 'type' => 'fulltext']
        ];
        $result = $this->Dbo->index('with_fulltext', false);
        $this->Dbo->rawQuery('DROP TABLE ' . $name);
        $this->assertEquals($expected, $result);

        $name = $this->Dbo->fullTableName('with_text_index');
        $this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, text_field text, primary key(id), KEY `text_index` ( `text_field`(20) ));');
        $expected = [
            'PRIMARY'    => ['column' => 'id', 'unique' => 1],
            'text_index' => ['column' => 'text_field', 'unique' => 0, 'length' => ['text_field' => 20]],
        ];
        $result = $this->Dbo->index('with_text_index', false);
        $this->Dbo->rawQuery('DROP TABLE ' . $name);
        $this->assertEquals($expected, $result);

        $name = $this->Dbo->fullTableName('with_compound_text_index');
        $this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, text_field1 text, text_field2 text, primary key(id), KEY `text_index` ( `text_field1`(20), `text_field2`(20) ));');
        $expected = [
            'PRIMARY'    => ['column' => 'id', 'unique' => 1],
            'text_index' => ['column' => ['text_field1', 'text_field2'], 'unique' => 0, 'length' => ['text_field1' => 20, 'text_field2' => 20]],
        ];
        $result = $this->Dbo->index('with_compound_text_index', false);
        $this->Dbo->rawQuery('DROP TABLE ' . $name);
        $this->assertEquals($expected, $result);
    }

    /**
     * MySQL 4.x returns index data in a different format,
     * Using a mock ensure that MySQL 4.x output is properly parsed.
     *
     * @group indices
     */
    public function testIndexOnMySQL4Output()
    {
        $name = $this->Dbo->fullTableName('simple');

        $mockDbo = $this->getMock('Mysql', ['connect', '_execute', 'getVersion']);
        $columnData = [
            ['0' => [
                'Table'        => 'with_compound_keys',
                'Non_unique'   => '0',
                'Key_name'     => 'PRIMARY',
                'Seq_in_index' => '1',
                'Column_name'  => 'id',
                'Collation'    => 'A',
                'Cardinality'  => '0',
                'Sub_part'     => null,
                'Packed'       => null,
                'Null'         => '',
                'Index_type'   => 'BTREE',
                'Comment'      => ''
            ]],
            ['0' => [
                'Table'        => 'with_compound_keys',
                'Non_unique'   => '1',
                'Key_name'     => 'pointless_bool',
                'Seq_in_index' => '1',
                'Column_name'  => 'bool',
                'Collation'    => 'A',
                'Cardinality'  => null,
                'Sub_part'     => null,
                'Packed'       => null,
                'Null'         => 'YES',
                'Index_type'   => 'BTREE',
                'Comment'      => ''
            ]],
            ['0' => [
                'Table'        => 'with_compound_keys',
                'Non_unique'   => '1',
                'Key_name'     => 'pointless_small_int',
                'Seq_in_index' => '1',
                'Column_name'  => 'small_int',
                'Collation'    => 'A',
                'Cardinality'  => null,
                'Sub_part'     => null,
                'Packed'       => null,
                'Null'         => 'YES',
                'Index_type'   => 'BTREE',
                'Comment'      => ''
            ]],
            ['0' => [
                'Table'        => 'with_compound_keys',
                'Non_unique'   => '1',
                'Key_name'     => 'one_way',
                'Seq_in_index' => '1',
                'Column_name'  => 'bool',
                'Collation'    => 'A',
                'Cardinality'  => null,
                'Sub_part'     => null,
                'Packed'       => null,
                'Null'         => 'YES',
                'Index_type'   => 'BTREE',
                'Comment'      => ''
            ]],
            ['0' => [
                'Table'        => 'with_compound_keys',
                'Non_unique'   => '1',
                'Key_name'     => 'one_way',
                'Seq_in_index' => '2',
                'Column_name'  => 'small_int',
                'Collation'    => 'A',
                'Cardinality'  => null,
                'Sub_part'     => null,
                'Packed'       => null,
                'Null'         => 'YES',
                'Index_type'   => 'BTREE',
                'Comment'      => ''
            ]]
        ];

        $mockDbo->expects($this->once())->method('getVersion')->will($this->returnValue('4.1'));
        $resultMock = $this->getMock('PDOStatement', ['fetch']);
        $mockDbo->expects($this->once())
            ->method('_execute')
            ->with('SHOW INDEX FROM ' . $name)
            ->will($this->returnValue($resultMock));

        foreach ($columnData as $i => $data) {
            $resultMock->expects($this->at($i))->method('fetch')->will($this->returnValue((object)$data));
        }

        $result = $mockDbo->index($name, false);
        $expected = [
            'PRIMARY'             => ['column' => 'id', 'unique' => 1],
            'pointless_bool'      => ['column' => 'bool', 'unique' => 0],
            'pointless_small_int' => ['column' => 'small_int', 'unique' => 0],
            'one_way'             => ['column' => ['bool', 'small_int'], 'unique' => 0],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testColumn method
     */
    public function testColumn()
    {
        $result = $this->Dbo->column('varchar(50)');
        $expected = 'string';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('text');
        $expected = 'text';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('int(11)');
        $expected = 'integer';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('int(11) unsigned');
        $expected = 'integer';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('bigint(20)');
        $expected = 'biginteger';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('tinyint(1)');
        $expected = 'boolean';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('tinyint');
        $expected = 'tinyinteger';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('smallint');
        $expected = 'smallinteger';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('boolean');
        $expected = 'boolean';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('float');
        $expected = 'float';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('float unsigned');
        $expected = 'float';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('double unsigned');
        $expected = 'float';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('decimal');
        $expected = 'decimal';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('numeric');
        $expected = 'decimal';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('decimal(14,7) unsigned');
        $expected = 'decimal';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->column('set(\'a\',\'b\',\'c\')');
        $expected = 'set(\'a\',\'b\',\'c\')';
        $this->assertEquals($expected, $result);
    }

    /**
     * testAlterSchemaIndexes method
     *
     * @group indices
     */
    public function testAlterSchemaIndexes()
    {
        $this->Dbo->cacheSources = $this->Dbo->testing = false;
        $table = $this->Dbo->fullTableName('altertest');

        $schemaA = new CakeSchema([
            'name'       => 'AlterTest1',
            'connection' => 'test',
            'altertest'  => [
                'id'     => ['type' => 'integer', 'null' => false, 'default' => 0],
                'name'   => ['type' => 'string', 'null' => false, 'length' => 50],
                'group1' => ['type' => 'integer', 'null' => true],
                'group2' => ['type' => 'integer', 'null' => true]
            ]]);
        $result = $this->Dbo->createSchema($schemaA);
        $this->assertContains('`id` int(11) DEFAULT 0 NOT NULL,', $result);
        $this->assertContains('`name` varchar(50) NOT NULL,', $result);
        $this->assertContains('`group1` int(11) DEFAULT NULL', $result);
        $this->assertContains('`group2` int(11) DEFAULT NULL', $result);

        //Test that the string is syntactically correct
        $query = $this->Dbo->getConnection()->prepare($result);
        $this->assertEquals($query->queryString, $result);

        $schemaB = new CakeSchema([
            'name'       => 'AlterTest2',
            'connection' => 'test',
            'altertest'  => [
                'id'      => ['type' => 'integer', 'null' => false, 'default' => 0],
                'name'    => ['type' => 'string', 'null' => false, 'length' => 50],
                'group1'  => ['type' => 'integer', 'null' => true],
                'group2'  => ['type' => 'integer', 'null' => true],
                'indexes' => [
                    'name_idx'     => ['column' => 'name', 'unique' => 0],
                    'group_idx'    => ['column' => 'group1', 'unique' => 0],
                    'compound_idx' => ['column' => ['group1', 'group2'], 'unique' => 0],
                    'PRIMARY'      => ['column' => 'id', 'unique' => 1]]
            ]]);

        $result = $this->Dbo->alterSchema($schemaB->compare($schemaA));
        $this->assertContains("ALTER TABLE $table", $result);
        $this->assertContains('ADD KEY `name_idx` (`name`),', $result);
        $this->assertContains('ADD KEY `group_idx` (`group1`),', $result);
        $this->assertContains('ADD KEY `compound_idx` (`group1`, `group2`),', $result);
        $this->assertContains('ADD PRIMARY KEY  (`id`);', $result);

        //Test that the string is syntactically correct
        $query = $this->Dbo->getConnection()->prepare($result);
        $this->assertEquals($query->queryString, $result);

        // Change three indexes, delete one and add another one
        $schemaC = new CakeSchema([
            'name'       => 'AlterTest3',
            'connection' => 'test',
            'altertest'  => [
                'id'      => ['type' => 'integer', 'null' => false, 'default' => 0],
                'name'    => ['type' => 'string', 'null' => false, 'length' => 50],
                'group1'  => ['type' => 'integer', 'null' => true],
                'group2'  => ['type' => 'integer', 'null' => true],
                'indexes' => [
                    'name_idx'     => ['column' => 'name', 'unique' => 1],
                    'group_idx'    => ['column' => 'group2', 'unique' => 0],
                    'compound_idx' => ['column' => ['group2', 'group1'], 'unique' => 0],
                    'id_name_idx'  => ['column' => ['id', 'name'], 'unique' => 0]]
            ]]);

        $result = $this->Dbo->alterSchema($schemaC->compare($schemaB));
        $this->assertContains("ALTER TABLE $table", $result);
        $this->assertContains('DROP PRIMARY KEY,', $result);
        $this->assertContains('DROP KEY `name_idx`,', $result);
        $this->assertContains('DROP KEY `group_idx`,', $result);
        $this->assertContains('DROP KEY `compound_idx`,', $result);
        $this->assertContains('ADD KEY `id_name_idx` (`id`, `name`),', $result);
        $this->assertContains('ADD UNIQUE KEY `name_idx` (`name`),', $result);
        $this->assertContains('ADD KEY `group_idx` (`group2`),', $result);
        $this->assertContains('ADD KEY `compound_idx` (`group2`, `group1`);', $result);

        $query = $this->Dbo->getConnection()->prepare($result);
        $this->assertEquals($query->queryString, $result);

        // Compare us to ourself.
        $this->assertEquals([], $schemaC->compare($schemaC));

        // Drop the indexes
        $result = $this->Dbo->alterSchema($schemaA->compare($schemaC));

        $this->assertContains("ALTER TABLE $table", $result);
        $this->assertContains('DROP KEY `name_idx`,', $result);
        $this->assertContains('DROP KEY `group_idx`,', $result);
        $this->assertContains('DROP KEY `compound_idx`,', $result);
        $this->assertContains('DROP KEY `id_name_idx`;', $result);

        $query = $this->Dbo->getConnection()->prepare($result);
        $this->assertEquals($query->queryString, $result);
    }

    /**
     * test saving and retrieval of blobs
     */
    public function testBlobSaving()
    {
        $this->loadFixtures('BinaryTest');
        $this->Dbo->cacheSources = false;
        $data = file_get_contents(CAKE . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'img' . DS . 'cake.power.gif');

        $model = new CakeTestModel(['name' => 'BinaryTest', 'ds' => 'test']);
        $model->save(compact('data'));

        $result = $model->find('first');
        $this->assertEquals($data, $result['BinaryTest']['data']);
    }

    /**
     * test altering the table settings with schema.
     */
    public function testAlteringTableParameters()
    {
        $this->Dbo->cacheSources = $this->Dbo->testing = false;

        $schemaA = new CakeSchema([
            'name'       => 'AlterTest1',
            'connection' => 'test',
            'altertest'  => [
                'id'              => ['type' => 'integer', 'null' => false, 'default' => 0],
                'name'            => ['type' => 'string', 'null' => false, 'length' => 50],
                'tableParameters' => [
                    'charset' => 'latin1',
                    'collate' => 'latin1_general_ci',
                    'engine'  => 'MyISAM'
                ]
            ]
        ]);
        $this->Dbo->rawQuery($this->Dbo->createSchema($schemaA));
        $schemaB = new CakeSchema([
            'name'       => 'AlterTest1',
            'connection' => 'test',
            'altertest'  => [
                'id'              => ['type' => 'integer', 'null' => false, 'default' => 0],
                'name'            => ['type' => 'string', 'null' => false, 'length' => 50],
                'tableParameters' => [
                    'charset' => 'utf8',
                    'collate' => 'utf8_general_ci',
                    'engine'  => 'InnoDB',
                    'comment' => 'Newly table added comment.',
                ]
            ]
        ]);
        $result = $this->Dbo->alterSchema($schemaB->compare($schemaA));
        $this->assertContains('DEFAULT CHARSET=utf8', $result);
        $this->assertContains('ENGINE=InnoDB', $result);
        $this->assertContains('COLLATE=utf8_general_ci', $result);
        $this->assertContains('COMMENT=\'Newly table added comment.\'', $result);

        $this->Dbo->rawQuery($result);
        $result = $this->Dbo->listDetailedSources($this->Dbo->fullTableName('altertest', false, false));
        $this->assertEquals('utf8_general_ci', $result['Collation']);
        $this->assertEquals('InnoDB', $result['Engine']);
        $this->assertEquals('utf8', $result['charset']);

        $this->Dbo->rawQuery($this->Dbo->dropSchema($schemaA));
    }

    /**
     * test alterSchema on two tables.
     */
    public function testAlteringTwoTables()
    {
        $schema1 = new CakeSchema([
            'name'       => 'AlterTest1',
            'connection' => 'test',
            'altertest'  => [
                'id'   => ['type' => 'integer', 'null' => false, 'default' => 0],
                'name' => ['type' => 'string', 'null' => false, 'length' => 50],
            ],
            'other_table' => [
                'id'   => ['type' => 'integer', 'null' => false, 'default' => 0],
                'name' => ['type' => 'string', 'null' => false, 'length' => 50],
            ]
        ]);
        $schema2 = new CakeSchema([
            'name'       => 'AlterTest1',
            'connection' => 'test',
            'altertest'  => [
                'id'        => ['type' => 'integer', 'null' => false, 'default' => 0],
                'field_two' => ['type' => 'string', 'null' => false, 'length' => 50],
            ],
            'other_table' => [
                'id'        => ['type' => 'integer', 'null' => false, 'default' => 0],
                'field_two' => ['type' => 'string', 'null' => false, 'length' => 50],
            ]
        ]);
        $result = $this->Dbo->alterSchema($schema2->compare($schema1));
        $this->assertEquals(2, substr_count($result, 'field_two'), 'Too many fields');
    }

    /**
     * testReadTableParameters method
     */
    public function testReadTableParameters()
    {
        $this->Dbo->cacheSources = $this->Dbo->testing = false;
        $tableName = 'tinyint_' . uniqid();
        $table = $this->Dbo->fullTableName($tableName);
        $this->Dbo->rawQuery('CREATE TABLE ' . $table . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');
        $result = $this->Dbo->readTableParameters($this->Dbo->fullTableName($tableName, false, false));
        $this->Dbo->rawQuery('DROP TABLE ' . $table);
        $expected = [
            'charset' => 'utf8',
            'collate' => 'utf8_unicode_ci',
            'engine'  => 'InnoDB'];
        $this->assertEquals($expected, $result);

        $table = $this->Dbo->fullTableName($tableName);
        $this->Dbo->rawQuery('CREATE TABLE ' . $table . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=cp1250 COLLATE=cp1250_general_ci COMMENT=\'Table\'\'s comment\';');
        $result = $this->Dbo->readTableParameters($this->Dbo->fullTableName($tableName, false, false));
        $this->Dbo->rawQuery('DROP TABLE ' . $table);
        $expected = [
            'charset' => 'cp1250',
            'collate' => 'cp1250_general_ci',
            'engine'  => 'MyISAM',
            'comment' => 'Table\'s comment',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testBuildTableParameters method
     */
    public function testBuildTableParameters()
    {
        $this->Dbo->cacheSources = $this->Dbo->testing = false;
        $data = [
            'charset' => 'utf8',
            'collate' => 'utf8_unicode_ci',
            'engine'  => 'InnoDB'];
        $result = $this->Dbo->buildTableParameters($data);
        $expected = [
            'DEFAULT CHARSET=utf8',
            'COLLATE=utf8_unicode_ci',
            'ENGINE=InnoDB'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testGetCharsetName method
     */
    public function testGetCharsetName()
    {
        $this->Dbo->cacheSources = $this->Dbo->testing = false;
        $result = $this->Dbo->getCharsetName('utf8_unicode_ci');
        $this->assertEquals('utf8', $result);
        $result = $this->Dbo->getCharsetName('cp1250_general_ci');
        $this->assertEquals('cp1250', $result);
    }

    /**
     * testGetCharsetNameCaching method
     */
    public function testGetCharsetNameCaching()
    {
        $db = $this->getMock('Mysql', ['connect', '_execute', 'getVersion']);
        $queryResult = $this->getMock('PDOStatement');

        $db->expects($this->exactly(2))->method('getVersion')->will($this->returnValue('5.1'));

        $db->expects($this->exactly(1))
            ->method('_execute')
            ->with('SELECT CHARACTER_SET_NAME FROM INFORMATION_SCHEMA.COLLATIONS WHERE COLLATION_NAME = ?', ['utf8_unicode_ci'])
            ->will($this->returnValue($queryResult));

        $queryResult->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->will($this->returnValue(['CHARACTER_SET_NAME' => 'utf8']));

        $result = $db->getCharsetName('utf8_unicode_ci');
        $this->assertEquals('utf8', $result);

        $result = $db->getCharsetName('utf8_unicode_ci');
        $this->assertEquals('utf8', $result);
    }

    /**
     * test that changing the virtualFieldSeparator allows for __ fields.
     */
    public function testVirtualFieldSeparators()
    {
        $this->loadFixtures('BinaryTest');
        $model = new CakeTestModel(['table' => 'binary_tests', 'ds' => 'test', 'name' => 'BinaryTest']);
        $model->virtualFields = [
            'other__field' => 'SUM(id)'
        ];

        $this->Dbo->virtualFieldSeparator = '_$_';
        $result = $this->Dbo->fields($model, null, ['data', 'other__field']);

        $expected = ['`BinaryTest`.`data`', '(SUM(id)) AS  `BinaryTest_$_other__field`'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test describe() on a fixture.
     */
    public function testDescribe()
    {
        $this->loadFixtures('Apple');

        $model = new Apple();
        $result = $this->Dbo->describe($model);

        $this->assertTrue(isset($result['id']));
        $this->assertTrue(isset($result['color']));

        $result = $this->Dbo->describe($model->useTable);

        $this->assertTrue(isset($result['id']));
        $this->assertTrue(isset($result['color']));
    }

    /**
     * Test that describe() ignores `default current_timestamp` in timestamp columns.
     */
    public function testDescribeHandleCurrentTimestamp()
    {
        $name = $this->Dbo->fullTableName('timestamp_default_values');
        $sql = <<<SQL
CREATE TABLE $name (
	id INT(11) NOT NULL AUTO_INCREMENT,
	phone VARCHAR(10),
	limit_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(id)
);
SQL;
        $this->Dbo->execute($sql);
        $model = new Model([
            'table' => 'timestamp_default_values',
            'ds'    => 'test',
            'alias' => 'TimestampDefaultValue'
        ]);
        $result = $this->Dbo->describe($model);
        $this->Dbo->execute('DROP TABLE ' . $name);

        $this->assertNull($result['limit_date']['default']);

        $schema = new CakeSchema([
            'connection'    => 'test',
            'testdescribes' => $result
        ]);
        $result = $this->Dbo->createSchema($schema);
        $this->assertContains('`limit_date` timestamp NOT NULL,', $result);
    }

    /**
     * Test that describe() ignores `default current_timestamp` in datetime columns.
     * This is for MySQL >= 5.6.
     */
    public function testDescribeHandleCurrentTimestampDatetime()
    {
        $mysqlVersion = $this->Dbo->query('SELECT VERSION() as version', ['log' => false]);
        $this->skipIf(version_compare($mysqlVersion[0][0]['version'], '5.6.0', '<'));

        $name = $this->Dbo->fullTableName('timestamp_default_values');
        $sql = <<<SQL
CREATE TABLE $name (
	id INT(11) NOT NULL AUTO_INCREMENT,
	phone VARCHAR(10),
	limit_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(id)
);
SQL;
        $this->Dbo->execute($sql);
        $model = new Model([
            'table' => 'timestamp_default_values',
            'ds'    => 'test',
            'alias' => 'TimestampDefaultValue'
        ]);
        $result = $this->Dbo->describe($model);
        $this->Dbo->execute('DROP TABLE ' . $name);

        $this->assertNull($result['limit_date']['default']);

        $schema = new CakeSchema([
            'connection'    => 'test',
            'testdescribes' => $result
        ]);
        $result = $this->Dbo->createSchema($schema);
        $this->assertContains('`limit_date` datetime NOT NULL,', $result);
    }

    /**
     * test that a describe() gets additional fieldParameters
     */
    public function testDescribeGettingFieldParameters()
    {
        $schema = new CakeSchema([
            'connection'    => 'test',
            'testdescribes' => [
                'id'      => ['type' => 'integer', 'key' => 'primary'],
                'stringy' => [
                    'type'    => 'string',
                    'null'    => true,
                    'charset' => 'cp1250',
                    'collate' => 'cp1250_general_ci',
                ],
                'other_col' => [
                    'type'    => 'string',
                    'null'    => false,
                    'charset' => 'latin1',
                    'comment' => 'Test Comment'
                ]
            ]
        ]);

        $this->Dbo->execute($this->Dbo->createSchema($schema));
        $model = new CakeTestModel(['table' => 'testdescribes', 'name' => 'Testdescribes']);
        $result = $model->getDataSource()->describe($model);
        $this->Dbo->execute($this->Dbo->dropSchema($schema));

        $this->assertEquals('cp1250_general_ci', $result['stringy']['collate']);
        $this->assertEquals('cp1250', $result['stringy']['charset']);
        $this->assertEquals('Test Comment', $result['other_col']['comment']);
    }

    /**
     * Test that two columns with key => primary doesn't create invalid sql.
     */
    public function testTwoColumnsWithPrimaryKey()
    {
        $schema = new CakeSchema([
            'connection'  => 'test',
            'roles_users' => [
                'role_id' => [
                    'type'    => 'integer',
                    'null'    => false,
                    'default' => null,
                    'key'     => 'primary'
                ],
                'user_id' => [
                    'type'    => 'integer',
                    'null'    => false,
                    'default' => null,
                    'key'     => 'primary'
                ],
                'indexes' => [
                    'user_role_index' => [
                        'column' => ['role_id', 'user_id'],
                        'unique' => 1
                    ],
                    'user_index' => [
                        'column' => 'user_id',
                        'unique' => 0
                    ]
                ],
            ]
        ]);

        $result = $this->Dbo->createSchema($schema);
        $this->assertContains('`role_id` int(11) NOT NULL,', $result);
        $this->assertContains('`user_id` int(11) NOT NULL,', $result);
    }

    /**
     * Test that the primary flag is handled correctly.
     */
    public function testCreateSchemaAutoPrimaryKey()
    {
        $schema = new CakeSchema();
        $schema->tables = [
            'no_indexes' => [
                'id'      => ['type' => 'integer', 'null' => false, 'key' => 'primary'],
                'data'    => ['type' => 'integer', 'null' => false],
                'indexes' => [],
            ]
        ];
        $result = $this->Dbo->createSchema($schema, 'no_indexes');
        $this->assertContains('PRIMARY KEY  (`id`)', $result);
        $this->assertNotContains('UNIQUE KEY', $result);

        $schema->tables = [
            'primary_index' => [
                'id'      => ['type' => 'integer', 'null' => false],
                'data'    => ['type' => 'integer', 'null' => false],
                'indexes' => [
                    'PRIMARY'    => ['column' => 'id', 'unique' => 1],
                    'some_index' => ['column' => 'data', 'unique' => 1]
                ],
            ]
        ];
        $result = $this->Dbo->createSchema($schema, 'primary_index');
        $this->assertContains('PRIMARY KEY  (`id`)', $result);
        $this->assertContains('UNIQUE KEY `some_index` (`data`)', $result);

        $schema->tables = [
            'primary_flag_has_index' => [
                'id'      => ['type' => 'integer', 'null' => false, 'key' => 'primary'],
                'data'    => ['type' => 'integer', 'null' => false],
                'indexes' => [
                    'some_index' => ['column' => 'data', 'unique' => 1]
                ],
            ]
        ];
        $result = $this->Dbo->createSchema($schema, 'primary_flag_has_index');
        $this->assertContains('PRIMARY KEY  (`id`)', $result);
        $this->assertContains('UNIQUE KEY `some_index` (`data`)', $result);
    }

    /**
     * Tests that listSources method sends the correct query and parses the result accordingly
     */
    public function testListSources()
    {
        $db = $this->getMock('Mysql', ['connect', '_execute']);
        $queryResult = $this->getMock('PDOStatement');
        $db->expects($this->once())
            ->method('_execute')
            ->with('SHOW TABLES FROM `cake`')
            ->will($this->returnValue($queryResult));
        $queryResult->expects($this->at(0))
            ->method('fetch')
            ->will($this->returnValue(['cake_table']));
        $queryResult->expects($this->at(1))
            ->method('fetch')
            ->will($this->returnValue(['another_table']));
        $queryResult->expects($this->at(2))
            ->method('fetch')
            ->will($this->returnValue(null));

        $tables = $db->listSources();
        $this->assertEquals(['cake_table', 'another_table'], $tables);
    }

    /**
     * test that listDetailedSources with a named table that doesn't exist.
     */
    public function testListDetailedSourcesNamed()
    {
        $this->loadFixtures('Apple');

        $result = $this->Dbo->listDetailedSources('imaginary');
        $this->assertEquals([], $result, 'Should be empty when table does not exist.');

        $result = $this->Dbo->listDetailedSources();
        $tableName = $this->Dbo->fullTableName('apples', false, false);
        $this->assertTrue(isset($result[$tableName]), 'Key should exist');
    }

    /**
     * Tests that getVersion method sends the correct query for getting the mysql version
     */
    public function testGetVersion()
    {
        $version = $this->Dbo->getVersion();
        $this->assertTrue(is_string($version));
    }

    /**
     * Tests that getVersion method sends the correct query for getting the client encoding
     */
    public function testGetEncoding()
    {
        $db = $this->getMock('Mysql', ['connect', '_execute']);
        $queryResult = $this->getMock('PDOStatement');

        $db->expects($this->once())
            ->method('_execute')
            ->with('SHOW VARIABLES LIKE ?', ['character_set_client'])
            ->will($this->returnValue($queryResult));
        $result = new StdClass();
        $result->Value = 'utf-8';
        $queryResult->expects($this->once())
            ->method('fetchObject')
            ->will($this->returnValue($result));

        $encoding = $db->getEncoding();
        $this->assertEquals('utf-8', $encoding);
    }

    /**
     * testFieldDoubleEscaping method
     */
    public function testFieldDoubleEscaping()
    {
        $db = $this->Dbo->config['database'];
        $test = $this->getMock('Mysql', ['connect', '_execute', 'execute']);
        $test->config['database'] = $db;

        $this->Model = $this->getMock('Article2', ['getDataSource']);
        $this->Model->alias = 'Article';
        $this->Model->expects($this->any())
            ->method('getDataSource')
            ->will($this->returnValue($test));

        $this->assertEquals('`Article`.`id`', $this->Model->escapeField());
        $result = $test->fields($this->Model, null, $this->Model->escapeField());
        $this->assertEquals(['`Article`.`id`'], $result);

        $test->expects($this->at(0))->method('execute')
            ->with('SELECT `Article`.`id` FROM ' . $test->fullTableName('articles') . ' AS `Article`   WHERE 1 = 1');

        $result = $test->read($this->Model, [
            'fields'     => $this->Model->escapeField(),
            'conditions' => null,
            'recursive'  => -1
        ]);

        $test->startQuote = '[';
        $test->endQuote = ']';
        $this->assertEquals('[Article].[id]', $this->Model->escapeField());

        $result = $test->fields($this->Model, null, $this->Model->escapeField());
        $this->assertEquals(['[Article].[id]'], $result);

        $test->expects($this->at(0))->method('execute')
            ->with('SELECT [Article].[id] FROM ' . $test->fullTableName('articles') . ' AS [Article]   WHERE 1 = 1');
        $result = $test->read($this->Model, [
            'fields'     => $this->Model->escapeField(),
            'conditions' => null,
            'recursive'  => -1
        ]);
    }

    /**
     * testGenerateAssociationQuerySelfJoin method
     */
    public function testGenerateAssociationQuerySelfJoin()
    {
        $this->Dbo = $this->getMock('Mysql', ['connect', '_execute', 'execute']);
        $this->startTime = microtime(true);
        $this->Model = new Article2();
        $this->_buildRelatedModels($this->Model);
        $this->_buildRelatedModels($this->Model->Category2);
        $this->Model->Category2->ChildCat = new Category2();
        $this->Model->Category2->ParentCat = new Category2();

        $queryData = [];

        foreach ($this->Model->Category2->associations() as $type) {
            foreach ($this->Model->Category2->{$type} as $assoc => $assocData) {
                $linkModel = $this->Model->Category2->{$assoc};
                $external = isset($assocData['external']);

                if ($this->Model->Category2->alias === $linkModel->alias &&
                    $type !== 'hasAndBelongsToMany' &&
                    $type !== 'hasMany'
                ) {
                    $result = $this->Dbo->generateAssociationQuery($this->Model->Category2, $linkModel, $type, $assoc, $assocData, $queryData, $external);
                    $this->assertFalse(empty($result));
                } else {
                    if ($this->Model->Category2->useDbConfig === $linkModel->useDbConfig) {
                        $result = $this->Dbo->generateAssociationQuery($this->Model->Category2, $linkModel, $type, $assoc, $assocData, $queryData, $external);
                        $this->assertFalse(empty($result));
                    }
                }
            }
        }

        $query = $this->Dbo->buildAssociationQuery($this->Model->Category2, $queryData);
        $this->assertRegExp('/^SELECT\s+(.+)FROM(.+)`Category2`\.`group_id`\s+=\s+`Group`\.`id`\)\s+LEFT JOIN(.+)WHERE\s+1 = 1\s*$/', $query);

        $this->Model = new TestModel4();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'belongsTo', 'model' => 'TestModel4Parent'];
        $queryData = [];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $_queryData = $queryData;
        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertTrue($result);

        $expected = [
            'conditions' => [],
            'fields'     => [
                '`TestModel4`.`id`',
                '`TestModel4`.`name`',
                '`TestModel4`.`created`',
                '`TestModel4`.`updated`',
                '`TestModel4Parent`.`id`',
                '`TestModel4Parent`.`name`',
                '`TestModel4Parent`.`created`',
                '`TestModel4Parent`.`updated`'
            ],
            'joins' => [
                [
                    'table'      => $this->Dbo->fullTableName($this->Model),
                    'alias'      => 'TestModel4Parent',
                    'type'       => 'LEFT',
                    'conditions' => '`TestModel4`.`parent_id` = `TestModel4Parent`.`id`'
                ]
            ],
            'order'     => [],
            'limit'     => [],
            'offset'    => [],
            'group'     => [],
            'having'    => null,
            'lock'      => null,
            'callbacks' => null
        ];
        $queryData['joins'][0]['table'] = $this->Dbo->fullTableName($queryData['joins'][0]['table']);
        $this->assertEquals($expected, $queryData);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel4Parent`\.`id`, `TestModel4Parent`\.`name`, `TestModel4Parent`\.`created`, `TestModel4Parent`\.`updated`\s+/', $result);
        $this->assertRegExp('/FROM\s+\S+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+\S+`test_model4` AS `TestModel4Parent`/', $result);
        $this->assertRegExp('/\s+ON\s+\(`TestModel4`.`parent_id` = `TestModel4Parent`.`id`\)\s+WHERE/', $result);
        $this->assertRegExp('/\s+WHERE\s+1 = 1$/', $result);

        $params['assocData']['type'] = 'INNER';
        $this->Model->belongsTo['TestModel4Parent']['type'] = 'INNER';
        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $_queryData, $params['external']);
        $this->assertTrue($result);
        $this->assertEquals('INNER', $_queryData['joins'][0]['type']);
    }

    /**
     * buildRelatedModels method
     *
     * @param Model $model
     */
    protected function _buildRelatedModels(Model $model)
    {
        foreach ($model->associations() as $type) {
            foreach ($model->{$type} as $assocData) {
                if (is_string($assocData)) {
                    $className = $assocData;
                } elseif (isset($assocData['className'])) {
                    $className = $assocData['className'];
                }
                $model->$className = new $className();
                $model->$className->schema();
            }
        }
    }

    /**
     * &_prepareAssociationQuery method
     *
     * @param Model $model
     * @param array $queryData
     * @param array $binding
     *
     * @return array The prepared association query
     */
    protected function &_prepareAssociationQuery(Model $model, &$queryData, $binding)
    {
        $type = $binding['type'];
        $assoc = $binding['model'];
        $assocData = $model->{$type}[$assoc];
        $className = $assocData['className'];

        $linkModel = $model->{$className};
        $external = isset($assocData['external']);
        $queryData = $this->_scrubQueryData($queryData);

        $result = array_merge(['linkModel' => &$linkModel], compact('type', 'assoc', 'assocData', 'external'));

        return $result;
    }

    /**
     * Helper method copied from DboSource::_scrubQueryData()
     *
     * @param array $data
     *
     * @return array
     */
    protected function _scrubQueryData($data)
    {
        static $base = null;
        if ($base === null) {
            $base = array_fill_keys(['conditions', 'fields', 'joins', 'order', 'limit', 'offset', 'group'], []);
            $base['callbacks'] = null;
        }

        return (array)$data + $base;
    }

    /**
     * test that read() places provided joins after the generated ones.
     */
    public function testReadCustomJoinsAfterGeneratedJoins()
    {
        $db = $this->Dbo->config['database'];
        $test = $this->getMock('Mysql', ['connect', '_execute', 'execute']);
        $test->config['database'] = $db;

        $this->Model = $this->getMock('TestModel9', ['getDataSource']);
        $this->Model->expects($this->any())
            ->method('getDataSource')
            ->will($this->returnValue($test));

        $this->Model->TestModel8 = $this->getMock('TestModel8', ['getDataSource']);
        $this->Model->TestModel8->expects($this->any())
            ->method('getDataSource')
            ->will($this->returnValue($test));

        $model8Table = $test->fullTableName($this->Model->TestModel8);
        $usersTable = $test->fullTableName('users');

        $search = "LEFT JOIN $model8Table AS `TestModel8` ON " .
            '(`TestModel8`.`name` != \'larry\' AND `TestModel9`.`test_model8_id` = `TestModel8`.`id`) ' .
            "LEFT JOIN $usersTable AS `User` ON (`TestModel9`.`id` = `User`.`test_id`)";

        $test->expects($this->at(0))->method('execute')
            ->with($this->stringContains($search));

        $test->read($this->Model, [
            'joins' => [
                [
                    'table'      => 'users',
                    'alias'      => 'User',
                    'type'       => 'LEFT',
                    'conditions' => ['TestModel9.id = User.test_id']
                ]
            ],
            'recursive' => 1
        ]);
    }

    /**
     * testGenerateInnerJoinAssociationQuery method
     */
    public function testGenerateInnerJoinAssociationQuery()
    {
        $db = $this->Dbo->config['database'];
        $test = $this->getMock('Mysql', ['connect', '_execute', 'execute']);
        $test->config['database'] = $db;

        $this->Model = $this->getMock('TestModel9', ['getDataSource']);
        $this->Model->expects($this->any())
            ->method('getDataSource')
            ->will($this->returnValue($test));

        $this->Model->TestModel8 = $this->getMock('TestModel8', ['getDataSource']);
        $this->Model->TestModel8->expects($this->any())
            ->method('getDataSource')
            ->will($this->returnValue($test));

        $testModel8Table = $this->Model->TestModel8->getDataSource()->fullTableName($this->Model->TestModel8);

        $test->expects($this->at(0))->method('execute')
            ->with($this->stringContains('`TestModel9` LEFT JOIN ' . $testModel8Table));

        $test->expects($this->at(1))->method('execute')
            ->with($this->stringContains('TestModel9` INNER JOIN ' . $testModel8Table));

        $test->read($this->Model, ['recursive' => 1]);
        $this->Model->belongsTo['TestModel8']['type'] = 'INNER';
        $test->read($this->Model, ['recursive' => 1]);
    }

    /**
     * testGenerateAssociationQuerySelfJoinWithConditionsInHasOneBinding method
     */
    public function testGenerateAssociationQuerySelfJoinWithConditionsInHasOneBinding()
    {
        $this->Model = new TestModel8();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'hasOne', 'model' => 'TestModel9'];
        $queryData = [];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);
        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertTrue($result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel8`\.`id`, `TestModel8`\.`test_model9_id`, `TestModel8`\.`name`, `TestModel8`\.`created`, `TestModel8`\.`updated`, `TestModel9`\.`id`, `TestModel9`\.`test_model8_id`, `TestModel9`\.`name`, `TestModel9`\.`created`, `TestModel9`\.`updated`\s+/', $result);
        $this->assertRegExp('/FROM\s+\S+`test_model8` AS `TestModel8`\s+LEFT JOIN\s+\S+`test_model9` AS `TestModel9`/', $result);
        $this->assertRegExp('/\s+ON\s+\(`TestModel9`\.`name` != \'mariano\'\s+AND\s+`TestModel9`.`test_model8_id` = `TestModel8`.`id`\)\s+WHERE/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);
    }

    /**
     * testGenerateAssociationQuerySelfJoinWithConditionsInBelongsToBinding method
     */
    public function testGenerateAssociationQuerySelfJoinWithConditionsInBelongsToBinding()
    {
        $this->Model = new TestModel9();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'belongsTo', 'model' => 'TestModel8'];
        $queryData = [];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);
        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertTrue($result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel9`\.`id`, `TestModel9`\.`test_model8_id`, `TestModel9`\.`name`, `TestModel9`\.`created`, `TestModel9`\.`updated`, `TestModel8`\.`id`, `TestModel8`\.`test_model9_id`, `TestModel8`\.`name`, `TestModel8`\.`created`, `TestModel8`\.`updated`\s+/', $result);
        $this->assertRegExp('/FROM\s+\S+`test_model9` AS `TestModel9`\s+LEFT JOIN\s+\S+`test_model8` AS `TestModel8`/', $result);
        $this->assertRegExp('/\s+ON\s+\(`TestModel8`\.`name` != \'larry\'\s+AND\s+`TestModel9`.`test_model8_id` = `TestModel8`.`id`\)\s+WHERE/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);
    }

    /**
     * testGenerateAssociationQuerySelfJoinWithConditions method
     */
    public function testGenerateAssociationQuerySelfJoinWithConditions()
    {
        $this->Model = new TestModel4();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'belongsTo', 'model' => 'TestModel4Parent'];
        $queryData = ['conditions' => ['TestModel4Parent.name !=' => 'mariano']];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertTrue($result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel4Parent`\.`id`, `TestModel4Parent`\.`name`, `TestModel4Parent`\.`created`, `TestModel4Parent`\.`updated`\s+/', $result);
        $this->assertRegExp('/FROM\s+\S+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+\S+`test_model4` AS `TestModel4Parent`/', $result);
        $this->assertRegExp('/\s+ON\s+\(`TestModel4`.`parent_id` = `TestModel4Parent`.`id`\)\s+WHERE/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?`TestModel4Parent`.`name`\s+!=\s+\'mariano\'(?:\))?\s*$/', $result);

        $this->Featured2 = new Featured2();
        $this->Featured2->schema();

        $this->Featured2->bindModel([
            'belongsTo' => [
                'ArticleFeatured2' => [
                    'conditions' => 'ArticleFeatured2.published = \'Y\'',
                    'fields'     => 'id, title, user_id, published'
                ]
            ]
        ]);

        $this->_buildRelatedModels($this->Featured2);

        $binding = ['type' => 'belongsTo', 'model' => 'ArticleFeatured2'];
        $queryData = ['conditions' => []];

        $params = &$this->_prepareAssociationQuery($this->Featured2, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Featured2, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertTrue($result);
        $result = $this->Dbo->buildAssociationQuery($this->Featured2, $queryData);

        $this->assertRegExp(
            '/^SELECT\s+`Featured2`\.`id`, `Featured2`\.`article_id`, `Featured2`\.`category_id`, `Featured2`\.`name`,\s+' .
            '`ArticleFeatured2`\.`id`, `ArticleFeatured2`\.`title`, `ArticleFeatured2`\.`user_id`, `ArticleFeatured2`\.`published`\s+' .
            'FROM\s+\S+`featured2` AS `Featured2`\s+LEFT JOIN\s+\S+`article_featured` AS `ArticleFeatured2`' .
            '\s+ON\s+\(`ArticleFeatured2`.`published` = \'Y\'\s+AND\s+`Featured2`\.`article_featured2_id` = `ArticleFeatured2`\.`id`\)' .
            '\s+WHERE\s+1\s+=\s+1\s*$/',
            $result
        );
    }

    /**
     * testGenerateAssociationQueryHasOne method
     */
    public function testGenerateAssociationQueryHasOne()
    {
        $this->Model = new TestModel4();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'hasOne', 'model' => 'TestModel5'];

        $queryData = [];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertTrue($result);

        $testModel5Table = $this->Dbo->fullTableName($this->Model->TestModel5);
        $result = $this->Dbo->buildJoinStatement($queryData['joins'][0]);
        $expected = ' LEFT JOIN ' . $testModel5Table . ' AS `TestModel5` ON (`TestModel5`.`test_model4_id` = `TestModel4`.`id`)';
        $this->assertEquals(trim($expected), trim($result));

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+/', $result);
        $this->assertRegExp('/`test_model5` AS `TestModel5`\s+ON\s+\(`TestModel5`.`test_model4_id` = `TestModel4`.`id`\)\s+WHERE/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?\s*1 = 1\s*(?:\))?\s*$/', $result);
    }

    /**
     * testGenerateAssociationQueryHasOneWithConditions method
     */
    public function testGenerateAssociationQueryHasOneWithConditions()
    {
        $this->Model = new TestModel4();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'hasOne', 'model' => 'TestModel5'];

        $queryData = ['conditions' => ['TestModel5.name !=' => 'mariano']];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertTrue($result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);

        $this->assertRegExp('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+\S+`test_model5` AS `TestModel5`/', $result);
        $this->assertRegExp('/\s+ON\s+\(`TestModel5`.`test_model4_id`\s+=\s+`TestModel4`.`id`\)\s+WHERE/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?\s*`TestModel5`.`name`\s+!=\s+\'mariano\'\s*(?:\))?\s*$/', $result);
    }

    /**
     * testGenerateAssociationQueryBelongsTo method
     */
    public function testGenerateAssociationQueryBelongsTo()
    {
        $this->Model = new TestModel5();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'belongsTo', 'model' => 'TestModel4'];
        $queryData = [];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertTrue($result);

        $testModel4Table = $this->Dbo->fullTableName($this->Model->TestModel4, true, true);
        $result = $this->Dbo->buildJoinStatement($queryData['joins'][0]);
        $expected = ' LEFT JOIN ' . $testModel4Table . ' AS `TestModel4` ON (`TestModel5`.`test_model4_id` = `TestModel4`.`id`)';
        $this->assertEquals(trim($expected), trim($result));

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`, `TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model5` AS `TestModel5`\s+LEFT JOIN\s+\S+`test_model4` AS `TestModel4`/', $result);
        $this->assertRegExp('/\s+ON\s+\(`TestModel5`.`test_model4_id` = `TestModel4`.`id`\)\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?\s*1 = 1\s*(?:\))?\s*$/', $result);
    }

    /**
     * testGenerateAssociationQueryBelongsToWithConditions method
     */
    public function testGenerateAssociationQueryBelongsToWithConditions()
    {
        $this->Model = new TestModel5();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'belongsTo', 'model' => 'TestModel4'];
        $queryData = ['conditions' => ['TestModel5.name !=' => 'mariano']];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertTrue($result);

        $testModel4Table = $this->Dbo->fullTableName($this->Model->TestModel4, true, true);
        $result = $this->Dbo->buildJoinStatement($queryData['joins'][0]);
        $expected = ' LEFT JOIN ' . $testModel4Table . ' AS `TestModel4` ON (`TestModel5`.`test_model4_id` = `TestModel4`.`id`)';
        $this->assertEquals(trim($expected), trim($result));

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`, `TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model5` AS `TestModel5`\s+LEFT JOIN\s+\S+`test_model4` AS `TestModel4`/', $result);
        $this->assertRegExp('/\s+ON\s+\(`TestModel5`.`test_model4_id` = `TestModel4`.`id`\)\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+WHERE\s+`TestModel5`.`name` != \'mariano\'\s*$/', $result);
    }

    /**
     * testGenerateAssociationQueryHasMany method
     */
    public function testGenerateAssociationQueryHasMany()
    {
        $this->Model = new TestModel5();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'hasMany', 'model' => 'TestModel6'];
        $queryData = [];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);

        $this->assertRegExp('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model6` AS `TestModel6`\s+WHERE/', $result);
        $this->assertRegExp('/\s+WHERE\s+`TestModel6`.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?\s*1 = 1\s*(?:\))?\s*$/', $result);
    }

    /**
     * testGenerateAssociationQueryHasManyWithLimit method
     */
    public function testGenerateAssociationQueryHasManyWithLimit()
    {
        $this->Model = new TestModel5();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $this->Model->hasMany['TestModel6']['limit'] = 2;

        $binding = ['type' => 'hasMany', 'model' => 'TestModel6'];
        $queryData = [];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertRegExp(
            '/^SELECT\s+' .
            '`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+' .
            'FROM\s+\S+`test_model6` AS `TestModel6`\s+WHERE\s+' .
            '`TestModel6`.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)\s*' .
            'LIMIT \d*' .
            '\s*$/',
            $result
        );

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp(
            '/^SELECT\s+' .
            '`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+' .
            'FROM\s+\S+`test_model5` AS `TestModel5`\s+WHERE\s+' .
            '(?:\()?\s*1 = 1\s*(?:\))?' .
            '\s*$/',
            $result
        );
    }

    /**
     * testGenerateAssociationQueryHasManyWithConditions method
     */
    public function testGenerateAssociationQueryHasManyWithConditions()
    {
        $this->Model = new TestModel5();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'hasMany', 'model' => 'TestModel6'];
        $queryData = ['conditions' => ['TestModel5.name !=' => 'mariano']];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertRegExp('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
        $this->assertRegExp('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?`TestModel5`.`name`\s+!=\s+\'mariano\'(?:\))?\s*$/', $result);
    }

    /**
     * testGenerateAssociationQueryHasManyWithOffsetAndLimit method
     */
    public function testGenerateAssociationQueryHasManyWithOffsetAndLimit()
    {
        $this->Model = new TestModel5();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $backup = $this->Model->hasMany['TestModel6'];

        $this->Model->hasMany['TestModel6']['offset'] = 2;
        $this->Model->hasMany['TestModel6']['limit'] = 5;

        $binding = ['type' => 'hasMany', 'model' => 'TestModel6'];
        $queryData = [];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);

        $this->assertRegExp('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
        $this->assertRegExp('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);
        $this->assertRegExp('/\s+LIMIT 2,\s*5\s*$/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

        $this->Model->hasMany['TestModel6'] = $backup;
    }

    /**
     * testGenerateAssociationQueryHasManyWithPageAndLimit method
     */
    public function testGenerateAssociationQueryHasManyWithPageAndLimit()
    {
        $this->Model = new TestModel5();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $backup = $this->Model->hasMany['TestModel6'];

        $this->Model->hasMany['TestModel6']['page'] = 2;
        $this->Model->hasMany['TestModel6']['limit'] = 5;

        $binding = ['type' => 'hasMany', 'model' => 'TestModel6'];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertRegExp('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
        $this->assertRegExp('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);
        $this->assertRegExp('/\s+LIMIT 5,\s*5\s*$/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

        $this->Model->hasMany['TestModel6'] = $backup;
    }

    /**
     * testGenerateAssociationQueryHasManyWithFields method
     */
    public function testGenerateAssociationQueryHasManyWithFields()
    {
        $this->Model = new TestModel5();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'hasMany', 'model' => 'TestModel6'];
        $queryData = ['fields' => ['`TestModel5`.`name`']];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertRegExp('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
        $this->assertRegExp('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel5`\.`name`, `TestModel5`\.`id`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

        $binding = ['type' => 'hasMany', 'model' => 'TestModel6'];
        $queryData = ['fields' => ['`TestModel5`.`id`, `TestModel5`.`name`']];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertRegExp('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
        $this->assertRegExp('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

        $binding = ['type' => 'hasMany', 'model' => 'TestModel6'];
        $queryData = ['fields' => ['`TestModel5`.`name`', '`TestModel5`.`created`']];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertRegExp('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
        $this->assertRegExp('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`id`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

        $this->Model->hasMany['TestModel6']['fields'] = ['name'];

        $binding = ['type' => 'hasMany', 'model' => 'TestModel6'];
        $queryData = ['fields' => ['`TestModel5`.`id`', '`TestModel5`.`name`']];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertRegExp('/^SELECT\s+`TestModel6`\.`name`, `TestModel6`\.`test_model5_id`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
        $this->assertRegExp('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

        unset($this->Model->hasMany['TestModel6']['fields']);

        $this->Model->hasMany['TestModel6']['fields'] = ['id', 'name'];

        $binding = ['type' => 'hasMany', 'model' => 'TestModel6'];
        $queryData = ['fields' => ['`TestModel5`.`id`', '`TestModel5`.`name`']];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertRegExp('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`name`, `TestModel6`\.`test_model5_id`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
        $this->assertRegExp('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

        unset($this->Model->hasMany['TestModel6']['fields']);

        $this->Model->hasMany['TestModel6']['fields'] = ['test_model5_id', 'name'];

        $binding = ['type' => 'hasMany', 'model' => 'TestModel6'];
        $queryData = ['fields' => ['`TestModel5`.`id`', '`TestModel5`.`name`']];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertRegExp('/^SELECT\s+`TestModel6`\.`test_model5_id`, `TestModel6`\.`name`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
        $this->assertRegExp('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

        unset($this->Model->hasMany['TestModel6']['fields']);
    }

    /**
     * test generateAssociationQuery with a hasMany and an aggregate function.
     */
    public function testGenerateAssociationQueryHasManyAndAggregateFunction()
    {
        $this->Model = new TestModel5();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'hasMany', 'model' => 'TestModel6'];
        $queryData = ['fields' => ['MIN(`TestModel5`.`test_model4_id`)']];
        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);
        $this->Model->recursive = 0;

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+MIN\(`TestModel5`\.`test_model4_id`\)\s+FROM/', $result);
    }

    /**
     * testGenerateAssociationQueryHasAndBelongsToMany method
     */
    public function testGenerateAssociationQueryHasAndBelongsToMany()
    {
        $this->Model = new TestModel4();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'hasAndBelongsToMany', 'model' => 'TestModel7'];
        $queryData = [];

        $params = $this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $assocTable = $this->Dbo->fullTableName($this->Model->TestModel4TestModel7, true, true);
        $this->assertRegExp('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`, `TestModel4TestModel7`\.`test_model4_id`, `TestModel4TestModel7`\.`test_model7_id`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model7` AS `TestModel7`\s+JOIN\s+' . $assocTable . '/', $result);
        $this->assertRegExp('/\s+ON\s+\(`TestModel4TestModel7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}\s+AND/', $result);
        $this->assertRegExp('/\s+AND\s+`TestModel4TestModel7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`\)/', $result);
        $this->assertRegExp('/WHERE\s+(?:\()?1 = 1(?:\))?\s*$/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model4` AS `TestModel4`\s+WHERE/', $result);
        $this->assertRegExp('/\s+WHERE\s+(?:\()?1 = 1(?:\))?\s*$/', $result);
    }

    /**
     * testGenerateAssociationQueryHasAndBelongsToManyWithConditions method
     */
    public function testGenerateAssociationQueryHasAndBelongsToManyWithConditions()
    {
        $this->Model = new TestModel4();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $binding = ['type' => 'hasAndBelongsToMany', 'model' => 'TestModel7'];
        $queryData = ['conditions' => ['TestModel4.name !=' => 'mariano']];

        $params = $this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertRegExp('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`, `TestModel4TestModel7`\.`test_model4_id`, `TestModel4TestModel7`\.`test_model7_id`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model7`\s+AS\s+`TestModel7`\s+JOIN\s+\S+`test_model4_test_model7`\s+AS\s+`TestModel4TestModel7`/', $result);
        $this->assertRegExp('/\s+ON\s+\(`TestModel4TestModel7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}/', $result);
        $this->assertRegExp('/\s+AND\s+`TestModel4TestModel7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`\)\s+WHERE\s+/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model4` AS `TestModel4`\s+WHERE\s+(?:\()?`TestModel4`.`name`\s+!=\s+\'mariano\'(?:\))?\s*$/', $result);
    }

    /**
     * testGenerateAssociationQueryHasAndBelongsToManyWithOffsetAndLimit method
     */
    public function testGenerateAssociationQueryHasAndBelongsToManyWithOffsetAndLimit()
    {
        $this->Model = new TestModel4();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $backup = $this->Model->hasAndBelongsToMany['TestModel7'];

        $this->Model->hasAndBelongsToMany['TestModel7']['offset'] = 2;
        $this->Model->hasAndBelongsToMany['TestModel7']['limit'] = 5;

        $binding = ['type' => 'hasAndBelongsToMany', 'model' => 'TestModel7'];
        $queryData = [];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertRegExp('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`, `TestModel4TestModel7`\.`test_model4_id`, `TestModel4TestModel7`\.`test_model7_id`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model7`\s+AS\s+`TestModel7`\s+JOIN\s+\S+`test_model4_test_model7`\s+AS\s+`TestModel4TestModel7`/', $result);
        $this->assertRegExp('/\s+ON\s+\(`TestModel4TestModel7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}\s+/', $result);
        $this->assertRegExp('/\s+AND\s+`TestModel4TestModel7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`\)\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+(?:\()?1\s+=\s+1(?:\))?\s*\s+LIMIT 2,\s*5\s*$/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model4` AS `TestModel4`\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

        $this->Model->hasAndBelongsToMany['TestModel7'] = $backup;
    }

    /**
     * testGenerateAssociationQueryHasAndBelongsToManyWithPageAndLimit method
     */
    public function testGenerateAssociationQueryHasAndBelongsToManyWithPageAndLimit()
    {
        $this->Model = new TestModel4();
        $this->Model->schema();
        $this->_buildRelatedModels($this->Model);

        $backup = $this->Model->hasAndBelongsToMany['TestModel7'];

        $this->Model->hasAndBelongsToMany['TestModel7']['page'] = 2;
        $this->Model->hasAndBelongsToMany['TestModel7']['limit'] = 5;

        $binding = ['type' => 'hasAndBelongsToMany', 'model' => 'TestModel7'];
        $queryData = [];

        $params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

        $result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external']);
        $this->assertRegExp('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`, `TestModel4TestModel7`\.`test_model4_id`, `TestModel4TestModel7`\.`test_model7_id`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model7`\s+AS\s+`TestModel7`\s+JOIN\s+\S+`test_model4_test_model7`\s+AS\s+`TestModel4TestModel7`/', $result);
        $this->assertRegExp('/\s+ON\s+\(`TestModel4TestModel7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}/', $result);
        $this->assertRegExp('/\s+AND\s+`TestModel4TestModel7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`\)\s+WHERE\s+/', $result);
        $this->assertRegExp('/\s+(?:\()?1\s+=\s+1(?:\))?\s*\s+LIMIT 5,\s*5\s*$/', $result);

        $result = $this->Dbo->buildAssociationQuery($this->Model, $queryData);
        $this->assertRegExp('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
        $this->assertRegExp('/\s+FROM\s+\S+`test_model4` AS `TestModel4`\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

        $this->Model->hasAndBelongsToMany['TestModel7'] = $backup;
    }

    /**
     * testSelectDistict method
     */
    public function testSelectDistict()
    {
        $this->Model = new TestModel4();
        $result = $this->Dbo->fields($this->Model, 'Vendor', 'DISTINCT Vendor.id, Vendor.name');
        $expected = ['DISTINCT `Vendor`.`id`', '`Vendor`.`name`'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testStringConditionsParsing method
     */
    public function testStringConditionsParsing()
    {
        $result = $this->Dbo->conditions('ProjectBid.project_id = Project.id');
        $expected = ' WHERE `ProjectBid`.`project_id` = `Project`.`id`';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('Candy.name LIKE \'a\' AND HardCandy.name LIKE \'c\'');
        $expected = ' WHERE `Candy`.`name` LIKE \'a\' AND `HardCandy`.`name` LIKE \'c\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('HardCandy.name LIKE \'a\' AND Candy.name LIKE \'c\'');
        $expected = ' WHERE `HardCandy`.`name` LIKE \'a\' AND `Candy`.`name` LIKE \'c\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('Post.title = \'1.1\'');
        $expected = ' WHERE `Post`.`title` = \'1.1\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('User.id != 0 AND User.user LIKE \'%arr%\'');
        $expected = ' WHERE `User`.`id` != 0 AND `User`.`user` LIKE \'%arr%\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('SUM(Post.comments_count) > 500');
        $expected = ' WHERE SUM(`Post`.`comments_count`) > 500';
        $this->assertEquals($expected, $result);

        $date = date('Y-m-d H:i');
        $result = $this->Dbo->conditions('(Post.created < \'' . $date . '\') GROUP BY YEAR(Post.created), MONTH(Post.created)');
        $expected = ' WHERE (`Post`.`created` < \'' . $date . '\') GROUP BY YEAR(`Post`.`created`), MONTH(`Post`.`created`)';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('score BETWEEN 90.1 AND 95.7');
        $expected = ' WHERE score BETWEEN 90.1 AND 95.7';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['score' => [2 => 1, 2, 10]]);
        $expected = ' WHERE `score` IN (1, 2, 10)';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('Aro.rght = Aro.lft + 1.1');
        $expected = ' WHERE `Aro`.`rght` = `Aro`.`lft` + 1.1';
        $this->assertEquals($expected, $result);

        $date = date('Y-m-d H:i:s');
        $result = $this->Dbo->conditions('(Post.created < \'' . $date . '\') GROUP BY YEAR(Post.created), MONTH(Post.created)');
        $expected = ' WHERE (`Post`.`created` < \'' . $date . '\') GROUP BY YEAR(`Post`.`created`), MONTH(`Post`.`created`)';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('Sportstaette.sportstaette LIKE "%ru%" AND Sportstaette.sportstaettenart_id = 2');
        $expected = ' WHERE `Sportstaette`.`sportstaette` LIKE "%ru%" AND `Sportstaette`.`sportstaettenart_id` = 2';
        $this->assertRegExp('/\s*WHERE\s+`Sportstaette`\.`sportstaette`\s+LIKE\s+"%ru%"\s+AND\s+`Sports/', $result);
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('Sportstaette.sportstaettenart_id = 2 AND Sportstaette.sportstaette LIKE "%ru%"');
        $expected = ' WHERE `Sportstaette`.`sportstaettenart_id` = 2 AND `Sportstaette`.`sportstaette` LIKE "%ru%"';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('SUM(Post.comments_count) > 500 AND NOT Post.title IS NULL AND NOT Post.extended_title IS NULL');
        $expected = ' WHERE SUM(`Post`.`comments_count`) > 500 AND NOT `Post`.`title` IS NULL AND NOT `Post`.`extended_title` IS NULL';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('NOT Post.title IS NULL AND NOT Post.extended_title IS NULL AND SUM(Post.comments_count) > 500');
        $expected = ' WHERE NOT `Post`.`title` IS NULL AND NOT `Post`.`extended_title` IS NULL AND SUM(`Post`.`comments_count`) > 500';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('NOT Post.extended_title IS NULL AND NOT Post.title IS NULL AND Post.title != "" AND SPOON(SUM(Post.comments_count) + 1.1) > 500');
        $expected = ' WHERE NOT `Post`.`extended_title` IS NULL AND NOT `Post`.`title` IS NULL AND `Post`.`title` != "" AND SPOON(SUM(`Post`.`comments_count`) + 1.1) > 500';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('NOT Post.title_extended IS NULL AND NOT Post.title IS NULL AND Post.title_extended != Post.title');
        $expected = ' WHERE NOT `Post`.`title_extended` IS NULL AND NOT `Post`.`title` IS NULL AND `Post`.`title_extended` != `Post`.`title`';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('Comment.id = \'a\'');
        $expected = ' WHERE `Comment`.`id` = \'a\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('lower(Article.title) LIKE \'a%\'');
        $expected = ' WHERE lower(`Article`.`title`) LIKE \'a%\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('((MATCH(Video.title) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 2) + (MATCH(Video.description) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 0.4) + (MATCH(Video.tags) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 1.5))');
        $expected = ' WHERE ((MATCH(`Video`.`title`) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 2) + (MATCH(`Video`.`description`) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 0.4) + (MATCH(`Video`.`tags`) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 1.5))';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('DATEDIFF(NOW(),Article.published) < 1 && Article.live=1');
        $expected = ' WHERE DATEDIFF(NOW(),`Article`.`published`) < 1 && `Article`.`live`=1';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('file = "index.html"');
        $expected = ' WHERE file = "index.html"';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('file = \'index.html\'');
        $expected = ' WHERE file = \'index.html\'';
        $this->assertEquals($expected, $result);

        $letter = $letter = 'd.a';
        $conditions = ['Company.name like ' => $letter . '%'];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE `Company`.`name` like \'d.a%\'';
        $this->assertEquals($expected, $result);

        $conditions = ['Artist.name' => 'JUDY and MARY'];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE `Artist`.`name` = \'JUDY and MARY\'';
        $this->assertEquals($expected, $result);

        $conditions = ['Artist.name' => 'JUDY AND MARY'];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE `Artist`.`name` = \'JUDY AND MARY\'';
        $this->assertEquals($expected, $result);

        $conditions = ['Company.name similar to ' => 'a word'];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE `Company`.`name` similar to \'a word\'';
        $this->assertEquals($expected, $result);
    }

    /**
     * testQuotesInStringConditions method
     */
    public function testQuotesInStringConditions()
    {
        $result = $this->Dbo->conditions('Member.email = \'mariano@cricava.com\'');
        $expected = ' WHERE `Member`.`email` = \'mariano@cricava.com\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('Member.email = "mariano@cricava.com"');
        $expected = ' WHERE `Member`.`email` = "mariano@cricava.com"';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('Member.email = \'mariano@cricava.com\' AND Member.user LIKE \'mariano.iglesias%\'');
        $expected = ' WHERE `Member`.`email` = \'mariano@cricava.com\' AND `Member`.`user` LIKE \'mariano.iglesias%\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions('Member.email = "mariano@cricava.com" AND Member.user LIKE "mariano.iglesias%"');
        $expected = ' WHERE `Member`.`email` = "mariano@cricava.com" AND `Member`.`user` LIKE "mariano.iglesias%"';
        $this->assertEquals($expected, $result);
    }

    /**
     * test that - in conditions and field names works
     */
    public function testHypenInStringConditionsAndFieldNames()
    {
        $result = $this->Dbo->conditions('I18n__title_pt-br.content = "test"');
        $this->assertEquals(' WHERE `I18n__title_pt-br`.`content` = "test"', $result);

        $result = $this->Dbo->conditions('Model.field=NOW()-3600');
        $this->assertEquals(' WHERE `Model`.`field`=NOW()-3600', $result);

        $result = $this->Dbo->conditions('NOW() - Model.created < 7200');
        $this->assertEquals(' WHERE NOW() - `Model`.`created` < 7200', $result);

        $result = $this->Dbo->conditions('NOW()-Model.created < 7200');
        $this->assertEquals(' WHERE NOW()-`Model`.`created` < 7200', $result);
    }

    /**
     * testParenthesisInStringConditions method
     */
    public function testParenthesisInStringConditions()
    {
        $result = $this->Dbo->conditions('Member.name = \'(lu\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(lu\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \')lu\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\)lu\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \'va(lu\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \'va)lu\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\)lu\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \'va(lu)\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \'va(lu)e\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)e\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \'(mariano)\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \'(mariano)iglesias\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)iglesias\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \'(mariano) iglesias\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\) iglesias\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \'(mariano word) iglesias\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano word\) iglesias\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \'(mariano.iglesias)\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\)\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \'Mariano Iglesias (mariano.iglesias)\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\)\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \'Mariano Iglesias (mariano.iglesias) CakePHP\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\) CakePHP\'$/', $result);

        $result = $this->Dbo->conditions('Member.name = \'(mariano.iglesias) CakePHP\'');
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\) CakePHP\'$/', $result);
    }

    /**
     * testParenthesisInArrayConditions method
     */
    public function testParenthesisInArrayConditions()
    {
        $result = $this->Dbo->conditions(['Member.name' => '(lu']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(lu\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => ')lu']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\)lu\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => 'va(lu']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => 'va)lu']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\)lu\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => 'va(lu)']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => 'va(lu)e']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)e\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => '(mariano)']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => '(mariano)iglesias']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)iglesias\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => '(mariano) iglesias']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\) iglesias\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => '(mariano word) iglesias']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano word\) iglesias\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => '(mariano.iglesias)']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\)\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => 'Mariano Iglesias (mariano.iglesias)']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\)\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => 'Mariano Iglesias (mariano.iglesias) CakePHP']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\) CakePHP\'$/', $result);

        $result = $this->Dbo->conditions(['Member.name' => '(mariano.iglesias) CakePHP']);
        $this->assertRegExp('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\) CakePHP\'$/', $result);
    }

    /**
     * testArrayConditionsParsing method
     */
    public function testArrayConditionsParsing()
    {
        $this->loadFixtures('Post', 'Author');
        $result = $this->Dbo->conditions(['Stereo.type' => 'in dash speakers']);
        $this->assertRegExp("/^\s+WHERE\s+`Stereo`.`type`\s+=\s+'in dash speakers'/", $result);

        $result = $this->Dbo->conditions(['Candy.name LIKE' => 'a', 'HardCandy.name LIKE' => 'c']);
        $this->assertRegExp("/^\s+WHERE\s+`Candy`.`name` LIKE\s+'a'\s+AND\s+`HardCandy`.`name`\s+LIKE\s+'c'/", $result);

        $result = $this->Dbo->conditions(['HardCandy.name LIKE' => 'a', 'Candy.name LIKE' => 'c']);
        $expected = ' WHERE `HardCandy`.`name` LIKE \'a\' AND `Candy`.`name` LIKE \'c\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['HardCandy.name LIKE' => 'a%', 'Candy.name LIKE' => '%c%']);
        $expected = ' WHERE `HardCandy`.`name` LIKE \'a%\' AND `Candy`.`name` LIKE \'%c%\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['HardCandy.name LIKE' => 'to be or%', 'Candy.name LIKE' => '%not to be%']);
        $expected = ' WHERE `HardCandy`.`name` LIKE \'to be or%\' AND `Candy`.`name` LIKE \'%not to be%\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions([
            'Person.name || \' \' || Person.surname ILIKE' => '%mark%'
        ]);
        $expected = ' WHERE `Person`.`name` || \' \' || `Person`.`surname` ILIKE \'%mark%\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['score BETWEEN ? AND ?' => [90.1, 95.7]]);
        $expected = ' WHERE `score` BETWEEN 90.1 AND 95.7';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Post.title' => 1.1]);
        $expected = ' WHERE `Post`.`title` = 1.1';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Post.title' => 1.1], true, true, new Post());
        $expected = ' WHERE `Post`.`title` = \'1.1\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['SUM(Post.comments_count) >' => '500']);
        $expected = ' WHERE SUM(`Post`.`comments_count`) > \'500\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['MAX(Post.rating) >' => '50']);
        $expected = ' WHERE MAX(`Post`.`rating`) > \'50\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['lower(Article.title)' => 'secrets']);
        $expected = ' WHERE lower(`Article`.`title`) = \'secrets\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['title LIKE' => '%hello']);
        $expected = ' WHERE `title` LIKE \'%hello\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Post.name' => 'mad(g)ik']);
        $expected = ' WHERE `Post`.`name` = \'mad(g)ik\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['score' => [1, 2, 10]]);
        $expected = ' WHERE `score` IN (1, 2, 10)';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['score' => []]);
        $expected = ' WHERE `score` IS NULL';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['score !=' => []]);
        $expected = ' WHERE `score` IS NOT NULL';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['score !=' => '20']);
        $expected = ' WHERE `score` != \'20\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['score >' => '20']);
        $expected = ' WHERE `score` > \'20\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['client_id >' => '20'], true, true, new TestModel());
        $expected = ' WHERE `client_id` > 20';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['OR' => [
            ['User.user' => 'mariano'],
            ['User.user' => 'nate']
        ]]);

        $expected = ' WHERE ((`User`.`user` = \'mariano\') OR (`User`.`user` = \'nate\'))';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['User.user RLIKE' => 'mariano|nate']);
        $expected = ' WHERE `User`.`user` RLIKE \'mariano|nate\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['or' => [
            'score BETWEEN ? AND ?' => ['4', '5'], 'rating >' => '20'
        ]]);
        $expected = ' WHERE ((`score` BETWEEN \'4\' AND \'5\') OR (`rating` > \'20\'))';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['or' => [
            'score BETWEEN ? AND ?' => ['4', '5'], ['score >' => '20']
        ]]);
        $expected = ' WHERE ((`score` BETWEEN \'4\' AND \'5\') OR (`score` > \'20\'))';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['and' => [
            'score BETWEEN ? AND ?' => ['4', '5'], ['score >' => '20']
        ]]);
        $expected = ' WHERE ((`score` BETWEEN \'4\' AND \'5\') AND (`score` > \'20\'))';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions([
            'published' => 1, 'or' => ['score >' => '2', ['score >' => '20']]
        ]);
        $expected = ' WHERE `published` = 1 AND ((`score` > \'2\') OR (`score` > \'20\'))';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions([['Project.removed' => false]]);
        $expected = ' WHERE `Project`.`removed` = \'0\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions([['Project.removed' => true]]);
        $expected = ' WHERE `Project`.`removed` = \'1\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions([['Project.removed' => null]]);
        $expected = ' WHERE `Project`.`removed` IS NULL';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions([['Project.removed !=' => null]]);
        $expected = ' WHERE `Project`.`removed` IS NOT NULL';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['(Usergroup.permissions) & 4' => 4]);
        $expected = ' WHERE (`Usergroup`.`permissions`) & 4 = 4';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['((Usergroup.permissions) & 4)' => 4]);
        $expected = ' WHERE ((`Usergroup`.`permissions`) & 4) = 4';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Post.modified >=' => 'DATE_SUB(NOW(), INTERVAL 7 DAY)']);
        $expected = ' WHERE `Post`.`modified` >= \'DATE_SUB(NOW(), INTERVAL 7 DAY)\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Post.modified >= DATE_SUB(NOW(), INTERVAL 7 DAY)']);
        $expected = ' WHERE `Post`.`modified` >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(
            [
                'NOT'                        => ['Course.id' => null, 'Course.vet' => 'N', 'level_of_education_id' => [912, 999]],
                'Enrollment.yearcompleted >' => '0']
        );
        $this->assertRegExp('/^\s*WHERE\s+\(NOT\s+\(`Course`\.`id` IS NULL\)\s+AND NOT\s+\(`Course`\.`vet`\s+=\s+\'N\'\)\s+AND NOT\s+\(`level_of_education_id` IN \(912, 999\)\)\)\s+AND\s+`Enrollment`\.`yearcompleted`\s+>\s+\'0\'\s*$/', $result);

        $result = $this->Dbo->conditions(['id <>' => '8']);
        $this->assertRegExp('/^\s*WHERE\s+`id`\s+<>\s+\'8\'\s*$/', $result);

        $result = $this->Dbo->conditions(['TestModel.field =' => 'gribe$@()lu']);
        $expected = ' WHERE `TestModel`.`field` = \'gribe$@()lu\'';
        $this->assertEquals($expected, $result);

        $conditions['NOT'] = ['Listing.expiration BETWEEN ? AND ?' => ['1', '100']];
        $conditions[0]['OR'] = [
            'Listing.title LIKE'       => '%term%',
            'Listing.description LIKE' => '%term%'
        ];
        $conditions[1]['OR'] = [
            'Listing.title LIKE'       => '%term_2%',
            'Listing.description LIKE' => '%term_2%'
        ];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE NOT (`Listing`.`expiration` BETWEEN \'1\' AND \'100\') AND' .
        ' ((`Listing`.`title` LIKE \'%term%\') OR (`Listing`.`description` LIKE \'%term%\')) AND' .
        ' ((`Listing`.`title` LIKE \'%term_2%\') OR (`Listing`.`description` LIKE \'%term_2%\'))';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['MD5(CONCAT(Reg.email,Reg.id))' => 'blah']);
        $expected = ' WHERE MD5(CONCAT(`Reg`.`email`,`Reg`.`id`)) = \'blah\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions([
            'MD5(CONCAT(Reg.email,Reg.id))' => ['blah', 'blahblah']
        ]);
        $expected = ' WHERE MD5(CONCAT(`Reg`.`email`,`Reg`.`id`)) IN (\'blah\', \'blahblah\')';
        $this->assertEquals($expected, $result);

        $conditions = ['id' => [2, 5, 6, 9, 12, 45, 78, 43, 76]];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE `id` IN (2, 5, 6, 9, 12, 45, 78, 43, 76)';
        $this->assertEquals($expected, $result);

        $conditions = ['`Correction`.`source` collate utf8_bin' => ['kiwi', 'pear']];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE `Correction`.`source` collate utf8_bin IN (\'kiwi\', \'pear\')';
        $this->assertEquals($expected, $result);

        $conditions = ['title' => 'user(s)'];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE `title` = \'user(s)\'';
        $this->assertEquals($expected, $result);

        $conditions = ['title' => 'user(s) data'];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE `title` = \'user(s) data\'';
        $this->assertEquals($expected, $result);

        $conditions = ['title' => 'user(s,arg) data'];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE `title` = \'user(s,arg) data\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Book.book_name' => 'Java(TM)']);
        $expected = ' WHERE `Book`.`book_name` = \'Java(TM)\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Book.book_name' => 'Java(TM) ']);
        $expected = ' WHERE `Book`.`book_name` = \'Java(TM) \'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Book.id' => 0]);
        $expected = ' WHERE `Book`.`id` = 0';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Book.id' => null]);
        $expected = ' WHERE `Book`.`id` IS NULL';
        $this->assertEquals($expected, $result);

        $conditions = ['MysqlModel.id' => ''];
        $result = $this->Dbo->conditions($conditions, true, true, $this->model);
        $expected = ' WHERE `MysqlModel`.`id` IS NULL';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Listing.beds >=' => 0]);
        $expected = ' WHERE `Listing`.`beds` >= 0';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions([
            'ASCII(SUBSTRING(keyword, 1, 1)) BETWEEN ? AND ?' => [65, 90]
        ]);
        $expected = ' WHERE ASCII(SUBSTRING(keyword, 1, 1)) BETWEEN 65 AND 90';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['or' => [
            '? BETWEEN Model.field1 AND Model.field2' => '2009-03-04'
        ]]);
        $expected = ' WHERE \'2009-03-04\' BETWEEN Model.field1 AND Model.field2';
        $this->assertEquals($expected, $result);
    }

    /**
     * test conditions() with replacements.
     */
    public function testConditionsWithReplacements()
    {
        $result = $this->Dbo->conditions([
            'score BETWEEN :0 AND :1' => [90.1, 95.7]
        ]);
        $expected = ' WHERE `score` BETWEEN 90.1 AND 95.7';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions([
            'score BETWEEN ? AND ?' => [90.1, 95.7]
        ]);
        $expected = ' WHERE `score` BETWEEN 90.1 AND 95.7';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that array conditions with only one element work.
     */
    public function testArrayConditionsOneElement()
    {
        $conditions = ['id' => [1]];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE id = (1)';
        $this->assertEquals($expected, $result);

        $conditions = ['id NOT' => [1]];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE NOT (id = (1))';
        $this->assertEquals($expected, $result);
    }

    /**
     * testArrayConditionsParsingComplexKeys method
     */
    public function testArrayConditionsParsingComplexKeys()
    {
        $result = $this->Dbo->conditions([
            'CAST(Book.created AS DATE)' => '2008-08-02'
        ]);
        $expected = ' WHERE CAST(`Book`.`created` AS DATE) = \'2008-08-02\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions([
            'CAST(Book.created AS DATE) <=' => '2008-08-02'
        ]);
        $expected = ' WHERE CAST(`Book`.`created` AS DATE) <= \'2008-08-02\'';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions([
            '(Stats.clicks * 100) / Stats.views >' => 50
        ]);
        $expected = ' WHERE (`Stats`.`clicks` * 100) / `Stats`.`views` > 50';
        $this->assertEquals($expected, $result);
    }

    /**
     * testMixedConditionsParsing method
     */
    public function testMixedConditionsParsing()
    {
        $conditions[] = 'User.first_name = \'Firstname\'';
        $conditions[] = ['User.last_name' => 'Lastname'];
        $result = $this->Dbo->conditions($conditions);
        $expected = ' WHERE `User`.`first_name` = \'Firstname\' AND `User`.`last_name` = \'Lastname\'';
        $this->assertEquals($expected, $result);

        $conditions = [
            'Thread.project_id' => 5,
            'Thread.buyer_id'   => 14,
            '1=1 GROUP BY Thread.project_id'
        ];
        $result = $this->Dbo->conditions($conditions);
        $this->assertRegExp('/^\s*WHERE\s+`Thread`.`project_id`\s*=\s*5\s+AND\s+`Thread`.`buyer_id`\s*=\s*14\s+AND\s+1\s*=\s*1\s+GROUP BY `Thread`.`project_id`$/', $result);
    }

    /**
     * testConditionsOptionalArguments method
     */
    public function testConditionsOptionalArguments()
    {
        $result = $this->Dbo->conditions(['Member.name' => 'Mariano'], true, false);
        $this->assertRegExp('/^\s*`Member`.`name`\s*=\s*\'Mariano\'\s*$/', $result);

        $result = $this->Dbo->conditions([], true, false);
        $this->assertRegExp('/^\s*1\s*=\s*1\s*$/', $result);
    }

    /**
     * testConditionsWithModel
     */
    public function testConditionsWithModel()
    {
        $this->Model = new Article2();

        $result = $this->Dbo->conditions(['Article2.viewed >=' => 0], true, true, $this->Model);
        $expected = ' WHERE `Article2`.`viewed` >= 0';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Article2.viewed >=' => '0'], true, true, $this->Model);
        $expected = ' WHERE `Article2`.`viewed` >= 0';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Article2.viewed >=' => '1'], true, true, $this->Model);
        $expected = ' WHERE `Article2`.`viewed` >= 1';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Article2.rate_sum BETWEEN ? AND ?' => [0, 10]], true, true, $this->Model);
        $expected = ' WHERE `Article2`.`rate_sum` BETWEEN 0 AND 10';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Article2.rate_sum BETWEEN ? AND ?' => ['0', '10']], true, true, $this->Model);
        $expected = ' WHERE `Article2`.`rate_sum` BETWEEN 0 AND 10';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->conditions(['Article2.rate_sum BETWEEN ? AND ?' => ['1', '10']], true, true, $this->Model);
        $expected = ' WHERE `Article2`.`rate_sum` BETWEEN 1 AND 10';
        $this->assertEquals($expected, $result);
    }

    /**
     * testFieldParsing method
     */
    public function testFieldParsing()
    {
        $this->Model = new TestModel();
        $result = $this->Dbo->fields($this->Model, 'Vendor', 'Vendor.id, COUNT(Model.vendor_id) AS `Vendor`.`count`');
        $expected = ['`Vendor`.`id`', 'COUNT(`Model`.`vendor_id`) AS `Vendor`.`count`'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, 'Vendor', '`Vendor`.`id`, COUNT(`Model`.`vendor_id`) AS `Vendor`.`count`');
        $expected = ['`Vendor`.`id`', 'COUNT(`Model`.`vendor_id`) AS `Vendor`.`count`'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, 'Post', 'CONCAT(REPEAT(\' \', COUNT(Parent.name) - 1), Node.name) AS name, Node.created');
        $expected = ['CONCAT(REPEAT(\' \', COUNT(`Parent`.`name`) - 1), Node.name) AS name', '`Node`.`created`'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, null, 'round( (3.55441 * fooField), 3 ) AS test');
        $this->assertEquals(['round( (3.55441 * fooField), 3 ) AS test'], $result);

        $result = $this->Dbo->fields($this->Model, null, 'ROUND(`Rating`.`rate_total` / `Rating`.`rate_count`,2) AS rating');
        $this->assertEquals(['ROUND(`Rating`.`rate_total` / `Rating`.`rate_count`,2) AS rating'], $result);

        $result = $this->Dbo->fields($this->Model, null, 'ROUND(Rating.rate_total / Rating.rate_count,2) AS rating');
        $this->assertEquals(['ROUND(Rating.rate_total / Rating.rate_count,2) AS rating'], $result);

        $result = $this->Dbo->fields($this->Model, 'Post', 'Node.created, CONCAT(REPEAT(\' \', COUNT(Parent.name) - 1), Node.name) AS name');
        $expected = ['`Node`.`created`', 'CONCAT(REPEAT(\' \', COUNT(`Parent`.`name`) - 1), Node.name) AS name'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, 'Post', '2.2,COUNT(*), SUM(Something.else) as sum, Node.created, CONCAT(REPEAT(\' \', COUNT(Parent.name) - 1), Node.name) AS name,Post.title,Post.1,1.1');
        $expected = [
            '2.2', 'COUNT(*)', 'SUM(`Something`.`else`) as sum', '`Node`.`created`',
            'CONCAT(REPEAT(\' \', COUNT(`Parent`.`name`) - 1), Node.name) AS name', '`Post`.`title`', '`Post`.`1`', '1.1'
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, null, '(`Provider`.`star_total` / `Provider`.`total_ratings`) as `rating`');
        $expected = ['(`Provider`.`star_total` / `Provider`.`total_ratings`) as `rating`'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, 'Post');
        $expected = [
            '`Post`.`id`', '`Post`.`client_id`', '`Post`.`name`', '`Post`.`login`',
            '`Post`.`passwd`', '`Post`.`addr_1`', '`Post`.`addr_2`', '`Post`.`zip_code`',
            '`Post`.`city`', '`Post`.`country`', '`Post`.`phone`', '`Post`.`fax`',
            '`Post`.`url`', '`Post`.`email`', '`Post`.`comments`', '`Post`.`last_login`',
            '`Post`.`created`', '`Post`.`updated`'
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, 'Other');
        $expected = [
            '`Other`.`id`', '`Other`.`client_id`', '`Other`.`name`', '`Other`.`login`',
            '`Other`.`passwd`', '`Other`.`addr_1`', '`Other`.`addr_2`', '`Other`.`zip_code`',
            '`Other`.`city`', '`Other`.`country`', '`Other`.`phone`', '`Other`.`fax`',
            '`Other`.`url`', '`Other`.`email`', '`Other`.`comments`', '`Other`.`last_login`',
            '`Other`.`created`', '`Other`.`updated`'
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, null, [], false);
        $expected = ['id', 'client_id', 'name', 'login', 'passwd', 'addr_1', 'addr_2', 'zip_code', 'city', 'country', 'phone', 'fax', 'url', 'email', 'comments', 'last_login', 'created', 'updated'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, null, 'COUNT(*)');
        $expected = ['COUNT(*)'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, null, 'SUM(Thread.unread_buyer) AS ' . $this->Dbo->name('sum_unread_buyer'));
        $expected = ['SUM(`Thread`.`unread_buyer`) AS `sum_unread_buyer`'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, null, 'name, count(*)');
        $expected = ['`TestModel`.`name`', 'count(*)'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, null, 'count(*), name');
        $expected = ['count(*)', '`TestModel`.`name`'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields(
            $this->Model,
            null,
            'field1, field2, field3, count(*), name'
        );
        $expected = [
            '`TestModel`.`field1`', '`TestModel`.`field2`',
            '`TestModel`.`field3`', 'count(*)', '`TestModel`.`name`'
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, null, ['dayofyear(now())']);
        $expected = ['dayofyear(now())'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, null, ['MAX(Model.field) As Max']);
        $expected = ['MAX(`Model`.`field`) As Max'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, null, ['Model.field AS AnotherName']);
        $expected = ['`Model`.`field` AS `AnotherName`'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, null, ['field AS AnotherName']);
        $expected = ['`field` AS `AnotherName`'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, null, [
            'TestModel.field AS AnotherName'
        ]);
        $expected = ['`TestModel`.`field` AS `AnotherName`'];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($this->Model, 'Foo', [
            'id', 'title', '(user_count + discussion_count + post_count) AS score'
        ]);
        $expected = [
            '`Foo`.`id`',
            '`Foo`.`title`',
            '(user_count + discussion_count + post_count) AS score'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that fields() will accept objects made from DboSource::expression
     */
    public function testFieldsWithExpression()
    {
        $this->Model = new TestModel();
        $expression = $this->Dbo->expression('CASE Sample.id WHEN 1 THEN \'Id One\' ELSE \'Other Id\' END AS case_col');
        $result = $this->Dbo->fields($this->Model, null, ['id', $expression]);
        $expected = [
            '`TestModel`.`id`',
            'CASE Sample.id WHEN 1 THEN \'Id One\' ELSE \'Other Id\' END AS case_col'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testRenderStatement method
     */
    public function testRenderStatement()
    {
        $result = $this->Dbo->renderStatement('select', [
            'fields' => 'id', 'table' => 'table', 'conditions' => 'WHERE 1=1',
            'alias'  => '', 'joins' => '', 'order' => '', 'limit' => '', 'group' => ''
        ]);
        $this->assertRegExp('/^\s*SELECT\s+id\s+FROM\s+table\s+WHERE\s+1=1\s*$/', $result);

        $result = $this->Dbo->renderStatement('update', ['fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => '']);
        $this->assertRegExp('/^\s*UPDATE\s+table\s+SET\s+value=2\s+WHERE\s+1=1\s*$/', $result);

        $result = $this->Dbo->renderStatement('update', ['fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => 'alias', 'joins' => '']);
        $this->assertRegExp('/^\s*UPDATE\s+table\s+AS\s+alias\s+SET\s+value=2\s+WHERE\s+1=1\s*$/', $result);

        $result = $this->Dbo->renderStatement('delete', ['fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => '']);
        $this->assertRegExp('/^\s*DELETE\s+FROM\s+table\s+WHERE\s+1=1\s*$/', $result);

        $result = $this->Dbo->renderStatement('delete', ['fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => 'alias', 'joins' => '']);
        $this->assertRegExp('/^\s*DELETE\s+alias\s+FROM\s+table\s+AS\s+alias\s+WHERE\s+1=1\s*$/', $result);
    }

    /**
     * testSchema method
     */
    public function testSchema()
    {
        $Schema = new CakeSchema();
        $Schema->tables = ['table' => [], 'anotherTable' => []];

        $result = $this->Dbo->dropSchema($Schema, 'non_existing');
        $this->assertTrue(empty($result));

        $result = $this->Dbo->dropSchema($Schema, 'table');
        $this->assertRegExp('/^\s*DROP TABLE IF EXISTS\s+' . $this->Dbo->fullTableName('table') . ';\s*$/s', $result);
    }

    /**
     * testDropSchemaNoSchema method
     *
     * @expectedException PHPUnit_Framework_Error
     *
     * @throws PHPUnit_Framework_Error
     */
    public function testDropSchemaNoSchema()
    {
        try {
            $this->Dbo->dropSchema(null);
            $this->fail('No exception');
        } catch (TypeError $e) {
            throw new PHPUnit_Framework_Error('Raised an error', 100, __FILE__, __LINE__);
        }
    }

    /**
     * testOrderParsing method
     */
    public function testOrderParsing()
    {
        $result = $this->Dbo->order('ADDTIME(Event.time_begin, \'-06:00:00\') ASC');
        $expected = ' ORDER BY ADDTIME(`Event`.`time_begin`, \'-06:00:00\') ASC';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->order('title, id');
        $this->assertRegExp('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

        $result = $this->Dbo->order('title desc, id desc');
        $this->assertRegExp('/^\s*ORDER BY\s+`title`\s+desc,\s+`id`\s+desc\s*$/', $result);

        $result = $this->Dbo->order(['title desc, id desc']);
        $this->assertRegExp('/^\s*ORDER BY\s+`title`\s+desc,\s+`id`\s+desc\s*$/', $result);

        $result = $this->Dbo->order(['title', 'id']);
        $this->assertRegExp('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

        $result = $this->Dbo->order([['title'], ['id']]);
        $this->assertRegExp('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

        $result = $this->Dbo->order(['Post.title' => 'asc', 'Post.id' => 'desc']);
        $this->assertRegExp('/^\s*ORDER BY\s+`Post`.`title`\s+asc,\s+`Post`.`id`\s+desc\s*$/', $result);

        $result = $this->Dbo->order([['Post.title' => 'asc', 'Post.id' => 'desc']]);
        $this->assertRegExp('/^\s*ORDER BY\s+`Post`.`title`\s+asc,\s+`Post`.`id`\s+desc\s*$/', $result);

        $result = $this->Dbo->order(['title']);
        $this->assertRegExp('/^\s*ORDER BY\s+`title`\s+ASC\s*$/', $result);

        $result = $this->Dbo->order([['title']]);
        $this->assertRegExp('/^\s*ORDER BY\s+`title`\s+ASC\s*$/', $result);

        $result = $this->Dbo->order('Dealer.id = 7 desc, Dealer.id = 3 desc, Dealer.title asc');
        $expected = ' ORDER BY `Dealer`.`id` = 7 desc, `Dealer`.`id` = 3 desc, `Dealer`.`title` asc';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->order(['Page.name' => '=\'test\' DESC']);
        $this->assertRegExp("/^\s*ORDER BY\s+`Page`\.`name`\s*='test'\s+DESC\s*$/", $result);

        $result = $this->Dbo->order('Page.name = \'view\' DESC');
        $this->assertRegExp("/^\s*ORDER BY\s+`Page`\.`name`\s*=\s*'view'\s+DESC\s*$/", $result);

        $result = $this->Dbo->order('(Post.views)');
        $this->assertRegExp("/^\s*ORDER BY\s+\(`Post`\.`views`\)\s+ASC\s*$/", $result);

        $result = $this->Dbo->order('(Post.views)*Post.views');
        $this->assertRegExp("/^\s*ORDER BY\s+\(`Post`\.`views`\)\*`Post`\.`views`\s+ASC\s*$/", $result);

        $result = $this->Dbo->order('(Post.views) * Post.views');
        $this->assertRegExp("/^\s*ORDER BY\s+\(`Post`\.`views`\) \* `Post`\.`views`\s+ASC\s*$/", $result);

        $result = $this->Dbo->order('(Model.field1 + Model.field2) * Model.field3');
        $this->assertRegExp("/^\s*ORDER BY\s+\(`Model`\.`field1` \+ `Model`\.`field2`\) \* `Model`\.`field3`\s+ASC\s*$/", $result);

        $result = $this->Dbo->order('Model.name+0 ASC');
        $this->assertRegExp("/^\s*ORDER BY\s+`Model`\.`name`\+0\s+ASC\s*$/", $result);

        $result = $this->Dbo->order('Anuncio.destaque & 2 DESC');
        $expected = ' ORDER BY `Anuncio`.`destaque` & 2 DESC';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->order('3963.191 * id');
        $expected = ' ORDER BY 3963.191 * id ASC';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->order(['Property.sale_price IS NULL']);
        $expected = ' ORDER BY `Property`.`sale_price` IS NULL ASC';
        $this->assertEquals($expected, $result);
    }

    /**
     * testComplexSortExpression method
     */
    public function testComplexSortExpression()
    {
        $result = $this->Dbo->order(['(Model.field > 100) DESC', 'Model.field ASC']);
        $this->assertRegExp("/^\s*ORDER BY\s+\(`Model`\.`field`\s+>\s+100\)\s+DESC,\s+`Model`\.`field`\s+ASC\s*$/", $result);
    }

    /**
     * testCalculations method
     */
    public function testCalculations()
    {
        $this->Model = new TestModel();
        $result = $this->Dbo->calculate($this->Model, 'count');
        $this->assertEquals('COUNT(*) AS `count`', $result);

        $result = $this->Dbo->calculate($this->Model, 'count', ['id']);
        $this->assertEquals('COUNT(`id`) AS `count`', $result);

        $result = $this->Dbo->calculate(
            $this->Model,
            'count',
            [$this->Dbo->expression('DISTINCT id')]
        );
        $this->assertEquals('COUNT(DISTINCT id) AS `count`', $result);

        $result = $this->Dbo->calculate($this->Model, 'count', ['id', 'id_count']);
        $this->assertEquals('COUNT(`id`) AS `id_count`', $result);

        $result = $this->Dbo->calculate($this->Model, 'count', ['Model.id', 'id_count']);
        $this->assertEquals('COUNT(`Model`.`id`) AS `id_count`', $result);

        $result = $this->Dbo->calculate($this->Model, 'max', ['id']);
        $this->assertEquals('MAX(`id`) AS `id`', $result);

        $result = $this->Dbo->calculate($this->Model, 'max', ['Model.id', 'id']);
        $this->assertEquals('MAX(`Model`.`id`) AS `id`', $result);

        $result = $this->Dbo->calculate($this->Model, 'max', ['`Model`.`id`', 'id']);
        $this->assertEquals('MAX(`Model`.`id`) AS `id`', $result);

        $result = $this->Dbo->calculate($this->Model, 'min', ['`Model`.`id`', 'id']);
        $this->assertEquals('MIN(`Model`.`id`) AS `id`', $result);

        $result = $this->Dbo->calculate($this->Model, 'min', 'left');
        $this->assertEquals('MIN(`left`) AS `left`', $result);
    }

    /**
     * testLength method
     */
    public function testLength()
    {
        $result = $this->Dbo->length('varchar(255)');
        $expected = 255;
        $this->assertSame($expected, $result);

        $result = $this->Dbo->length('int(11)');
        $expected = 11;
        $this->assertSame($expected, $result);

        $result = $this->Dbo->length('float(5,3)');
        $expected = '5,3';
        $this->assertSame($expected, $result);

        $result = $this->Dbo->length('decimal(5,2)');
        $expected = '5,2';
        $this->assertSame($expected, $result);

        $result = $this->Dbo->length(false);
        $this->assertNull($result);

        $result = $this->Dbo->length('datetime');
        $expected = null;
        $this->assertSame($expected, $result);

        $result = $this->Dbo->length('text');
        $expected = null;
        $this->assertSame($expected, $result);
    }

    /**
     * Tests the length of enum column.
     */
    public function testLengthEnum()
    {
        $result = $this->Dbo->length('enum(\'test\',\'me\',\'now\')');
        $this->assertNull($result);
    }

    /**
     * Tests the length of set column.
     */
    public function testLengthSet()
    {
        $result = $this->Dbo->length('set(\'a\',\'b\',\'cd\')');
        $this->assertNull($result);
    }

    /**
     * testBuildIndex method
     */
    public function testBuildIndex()
    {
        $data = [
            'PRIMARY' => ['column' => 'id']
        ];
        $result = $this->Dbo->buildIndex($data);
        $expected = ['PRIMARY KEY  (`id`)'];
        $this->assertSame($expected, $result);

        $data = [
            'MyIndex' => ['column' => 'id', 'unique' => true]
        ];
        $result = $this->Dbo->buildIndex($data);
        $expected = ['UNIQUE KEY `MyIndex` (`id`)'];
        $this->assertEquals($expected, $result);

        $data = [
            'MyIndex' => ['column' => ['id', 'name'], 'unique' => true]
        ];
        $result = $this->Dbo->buildIndex($data);
        $expected = ['UNIQUE KEY `MyIndex` (`id`, `name`)'];
        $this->assertEquals($expected, $result);

        $data = [
            'MyFtIndex' => ['column' => ['name', 'description'], 'type' => 'fulltext']
        ];
        $result = $this->Dbo->buildIndex($data);
        $expected = ['FULLTEXT KEY `MyFtIndex` (`name`, `description`)'];
        $this->assertEquals($expected, $result);

        $data = [
            'MyTextIndex' => ['column' => 'text_field', 'length' => ['text_field' => 20]]
        ];
        $result = $this->Dbo->buildIndex($data);
        $expected = ['KEY `MyTextIndex` (`text_field`(20))'];
        $this->assertEquals($expected, $result);

        $data = [
            'MyMultiTextIndex' => ['column' => ['text_field1', 'text_field2'], 'length' => ['text_field1' => 20, 'text_field2' => 20]]
        ];
        $result = $this->Dbo->buildIndex($data);
        $expected = ['KEY `MyMultiTextIndex` (`text_field1`(20), `text_field2`(20))'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testBuildColumn method
     */
    public function testBuildColumn()
    {
        $data = [
            'name'   => 'testName',
            'type'   => 'string',
            'length' => 255,
            'default',
            'null' => true,
            'key'
        ];
        $result = $this->Dbo->buildColumn($data);
        $expected = '`testName` varchar(255) DEFAULT NULL';
        $this->assertEquals($expected, $result);

        $data = [
            'name'    => 'int_field',
            'type'    => 'integer',
            'default' => '',
            'null'    => false,
        ];
        $restore = $this->Dbo->columns;

        $this->Dbo->columns = ['integer' => ['name' => 'int', 'limit' => '11', 'formatter' => 'intval'], ];
        $result = $this->Dbo->buildColumn($data);
        $expected = '`int_field` int(11) NOT NULL';
        $this->assertEquals($expected, $result);

        $this->Dbo->fieldParameters['param'] = [
            'value'    => 'COLLATE',
            'quote'    => false,
            'join'     => ' ',
            'column'   => 'Collate',
            'position' => 'beforeDefault',
            'options'  => ['GOOD', 'OK']
        ];
        $data = [
            'name'    => 'int_field',
            'type'    => 'integer',
            'default' => '',
            'null'    => false,
            'param'   => 'BAD'
        ];
        $result = $this->Dbo->buildColumn($data);
        $expected = '`int_field` int(11) NOT NULL';
        $this->assertEquals($expected, $result);

        $data = [
            'name'    => 'int_field',
            'type'    => 'integer',
            'default' => '',
            'null'    => false,
            'param'   => 'GOOD'
        ];
        $result = $this->Dbo->buildColumn($data);
        $expected = '`int_field` int(11) COLLATE GOOD NOT NULL';
        $this->assertEquals($expected, $result);

        $this->Dbo->columns = $restore;

        $data = [
            'name'    => 'created',
            'type'    => 'timestamp',
            'default' => 'current_timestamp',
            'null'    => false,
        ];
        $result = $this->Dbo->buildColumn($data);
        $expected = '`created` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL';
        $this->assertEquals($expected, $result);

        $data = [
            'name'    => 'created',
            'type'    => 'timestamp',
            'default' => 'CURRENT_TIMESTAMP',
            'null'    => true,
        ];
        $result = $this->Dbo->buildColumn($data);
        $expected = '`created` timestamp DEFAULT CURRENT_TIMESTAMP';
        $this->assertEquals($expected, $result);

        $data = [
            'name' => 'modified',
            'type' => 'timestamp',
            'null' => true,
        ];
        $result = $this->Dbo->buildColumn($data);
        $expected = '`modified` timestamp NULL';
        $this->assertEquals($expected, $result);

        $data = [
            'name'    => 'modified',
            'type'    => 'timestamp',
            'default' => null,
            'null'    => true,
        ];
        $result = $this->Dbo->buildColumn($data);
        $expected = '`modified` timestamp NULL';
        $this->assertEquals($expected, $result);
    }

    /**
     * testBuildColumnBadType method
     *
     * @expectedException PHPUnit_Framework_Error
     */
    public function testBuildColumnBadType()
    {
        $data = [
            'name' => 'testName',
            'type' => 'varchar(255)',
            'default',
            'null' => true,
            'key'
        ];
        $this->Dbo->buildColumn($data);
    }

    /**
     * Test `unsigned` field parameter
     *
     * @param array $data Column data
     * @param string $expected Expected sql part
     *
     *
     * @dataProvider buildColumnUnsignedProvider
     */
    public function testBuildColumnUnsigned($data, $expected)
    {
        $result = $this->Dbo->buildColumn($data);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider testBuildColumnUnsigned method
     *
     * @return array
     */
    public function buildColumnUnsignedProvider()
    {
        return [
            // unsigned int
            [
                [
                    'name'     => 'testName',
                    'type'     => 'integer',
                    'length'   => 11,
                    'unsigned' => true
                ],
                '`testName` int(11) UNSIGNED'
            ],
            // unsigned bigint
            [
                [
                    'name'     => 'testName',
                    'type'     => 'biginteger',
                    'length'   => 20,
                    'unsigned' => true
                ],
                '`testName` bigint(20) UNSIGNED'
            ],
            // unsigned float
            [
                [
                    'name'     => 'testName',
                    'type'     => 'float',
                    'unsigned' => true
                ],
                '`testName` float UNSIGNED'
            ],
            // varchar
            [
                [
                    'name'     => 'testName',
                    'type'     => 'string',
                    'length'   => 255,
                    'unsigned' => true
                ],
                '`testName` varchar(255)'
            ],
            // date unsigned
            [
                [
                    'name'     => 'testName',
                    'type'     => 'date',
                    'unsigned' => true
                ],
                '`testName` date'
            ],
            // date
            [
                [
                    'name'     => 'testName',
                    'type'     => 'date',
                    'unsigned' => false
                ],
                '`testName` date'
            ],
            // integer with length
            [
                [
                    'name'     => 'testName',
                    'type'     => 'integer',
                    'length'   => 11,
                    'unsigned' => false
                ],
                '`testName` int(11)'
            ],
            // unsigned decimal
            [
                [
                    'name'     => 'testName',
                    'type'     => 'decimal',
                    'unsigned' => true
                ],
                '`testName` decimal UNSIGNED'
            ],
            // decimal with default
            [
                [
                    'name'     => 'testName',
                    'type'     => 'decimal',
                    'unsigned' => true,
                    'default'  => 1
                ],
                '`testName` decimal UNSIGNED DEFAULT 1'
            ],
            // smallinteger
            [
                [
                    'name'     => 'testName',
                    'type'     => 'smallinteger',
                    'length'   => 6,
                    'unsigned' => true
                ],
                '`testName` smallint(6) UNSIGNED'
            ],
            // tinyinteger
            [
                [
                    'name'     => 'testName',
                    'type'     => 'tinyinteger',
                    'length'   => 4,
                    'unsigned' => true
                ],
                '`testName` tinyint(4) UNSIGNED'
            ]
        ];
    }

    /**
     * Test getting `unsigned` field parameter from DB
     */
    public function testSchemaUnsigned()
    {
        $this->loadFixtures('Unsigned');
        $Model = ClassRegistry::init('Model');
        $Model->setSource('unsigned');
        $types = $this->Dbo->fieldParameters['unsigned']['types'];
        $schema = $Model->schema();
        foreach ($types as $type) {
            $this->assertArrayHasKey('unsigned', $schema['u' . $type]);
            $this->assertTrue($schema['u' . $type]['unsigned']);
            $this->assertArrayHasKey('unsigned', $schema[$type]);
            $this->assertFalse($schema[$type]['unsigned']);
        }
        $this->assertArrayNotHasKey('unsigned', $schema['string']);
    }

    /**
     * test hasAny()
     */
    public function testHasAny()
    {
        $db = $this->Dbo->config['database'];
        $this->Dbo = $this->getMock('Mysql', ['connect', '_execute', 'execute', 'value']);
        $this->Dbo->config['database'] = $db;

        $this->Model = $this->getMock('TestModel', ['getDataSource']);
        $this->Model->expects($this->any())
            ->method('getDataSource')
            ->will($this->returnValue($this->Dbo));

        $this->Dbo->expects($this->at(0))->method('value')
            ->with('harry')
            ->will($this->returnValue('\'harry\''));

        $modelTable = $this->Dbo->fullTableName($this->Model);
        $this->Dbo->expects($this->at(1))->method('execute')
            ->with('SELECT COUNT(`TestModel`.`id`) AS count FROM ' . $modelTable . ' AS `TestModel` WHERE `TestModel`.`name` = \'harry\'');
        $this->Dbo->expects($this->at(2))->method('execute')
            ->with('SELECT COUNT(`TestModel`.`id`) AS count FROM ' . $modelTable . ' AS `TestModel` WHERE 1 = 1');

        $this->Dbo->hasAny($this->Model, ['TestModel.name' => 'harry']);
        $this->Dbo->hasAny($this->Model, []);
    }

    /**
     * test fields generating usable virtual fields to use in query
     */
    public function testVirtualFields()
    {
        $this->loadFixtures('Article', 'Comment', 'Tag');
        $this->Dbo->virtualFieldSeparator = '__';
        $Article = ClassRegistry::init('Article');
        $commentsTable = $this->Dbo->fullTableName('comments', false, false);
        $Article->virtualFields = [
            'this_moment'   => 'NOW()',
            'two'           => '1 + 1',
            'comment_count' => 'SELECT COUNT(*) FROM ' . $commentsTable .
                ' WHERE Article.id = ' . $commentsTable . '.article_id'
        ];
        $result = $this->Dbo->fields($Article);
        $expected = [
            '`Article`.`id`',
            '`Article`.`user_id`',
            '`Article`.`title`',
            '`Article`.`body`',
            '`Article`.`published`',
            '`Article`.`created`',
            '`Article`.`updated`',
            '(NOW()) AS  `Article__this_moment`',
            '(1 + 1) AS  `Article__two`',
            "(SELECT COUNT(*) FROM $commentsTable WHERE `Article`.`id` = `$commentsTable`.`article_id`) AS  `Article__comment_count`"
        ];

        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($Article, null, ['this_moment', 'title']);
        $expected = [
            '`Article`.`title`',
            '(NOW()) AS  `Article__this_moment`',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($Article, null, ['Article.title', 'Article.this_moment']);
        $expected = [
            '`Article`.`title`',
            '(NOW()) AS  `Article__this_moment`',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($Article, null, ['Article.this_moment', 'Article.title']);
        $expected = [
            '`Article`.`title`',
            '(NOW()) AS  `Article__this_moment`',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($Article, null, ['Article.*']);
        $expected = [
            '`Article`.*',
            '(NOW()) AS  `Article__this_moment`',
            '(1 + 1) AS  `Article__two`',
            "(SELECT COUNT(*) FROM $commentsTable WHERE `Article`.`id` = `$commentsTable`.`article_id`) AS  `Article__comment_count`"
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fields($Article, null, ['*']);
        $expected = [
            '*',
            '(NOW()) AS  `Article__this_moment`',
            '(1 + 1) AS  `Article__two`',
            "(SELECT COUNT(*) FROM $commentsTable WHERE `Article`.`id` = `$commentsTable`.`article_id`) AS  `Article__comment_count`"
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test find() generating usable virtual fields to use in query without modifying custom subqueries.
     */
    public function testVirtualFieldsWithSubquery()
    {
        $this->loadFixtures('Article', 'Comment', 'User', 'Tag', 'ArticlesTag');
        $this->Dbo->virtualFieldSeparator = '__';
        $Article = ClassRegistry::init('Article');
        $commentsTable = $this->Dbo->fullTableName('comments', false, false);
        $Article->Comment->virtualFields = [
            'extra' => 'SELECT id FROM ' . $commentsTable . ' WHERE id = (SELECT 1)',
        ];
        $conditions = ['Article.id' => [1, 2]];
        $contain = ['Comment.extra'];

        $test = ConnectionManager::getDatasource('test');
        $test->getLog();
        $result = $Article->find('all', compact('conditions', 'contain'));

        $expected = 'SELECT `Comment`.`id`, `Comment`.`article_id`, `Comment`.`user_id`, `Comment`.`comment`,' .
            ' `Comment`.`published`, `Comment`.`created`,' .
            ' `Comment`.`updated`, (SELECT id FROM comments WHERE id = (SELECT 1)) AS  `Comment__extra`' .
            ' FROM ' . $test->fullTableName('comments') . ' AS `Comment`   WHERE `Comment`.`article_id` IN (1, 2)';

        $log = $test->getLog();
        $this->assertTextEquals($expected, $log['log'][count($log['log']) - 2]['query']);
    }

    /**
     * test conditions to generate query conditions for virtual fields
     */
    public function testVirtualFieldsInConditions()
    {
        $Article = ClassRegistry::init('Article');
        $commentsTable = $this->Dbo->fullTableName('comments', false, false);

        $Article->virtualFields = [
            'this_moment'   => 'NOW()',
            'two'           => '1 + 1',
            'comment_count' => 'SELECT COUNT(*) FROM ' . $commentsTable .
                ' WHERE Article.id = ' . $commentsTable . '.article_id'
        ];
        $conditions = ['two' => 2];
        $result = $this->Dbo->conditions($conditions, true, false, $Article);
        $expected = '(1 + 1) = 2';
        $this->assertEquals($expected, $result);

        $conditions = ['this_moment BETWEEN ? AND ?' => [1, 2]];
        $expected = 'NOW() BETWEEN 1 AND 2';
        $result = $this->Dbo->conditions($conditions, true, false, $Article);
        $this->assertEquals($expected, $result);

        $conditions = ['comment_count >' => 5];
        $expected = "(SELECT COUNT(*) FROM $commentsTable WHERE `Article`.`id` = `$commentsTable`.`article_id`) > 5";
        $result = $this->Dbo->conditions($conditions, true, false, $Article);
        $this->assertEquals($expected, $result);

        $conditions = ['NOT' => ['two' => 2]];
        $result = $this->Dbo->conditions($conditions, true, false, $Article);
        $expected = 'NOT ((1 + 1) = 2)';
        $this->assertEquals($expected, $result);
    }

    /**
     * test that virtualFields with complex functions and aliases work.
     */
    public function testConditionsWithComplexVirtualFields()
    {
        $Article = ClassRegistry::init('Article', 'Comment', 'Tag');
        $Article->virtualFields = [
            'distance' => 'ACOS(SIN(20 * PI() / 180)
					* SIN(Article.latitude * PI() / 180)
					+ COS(20 * PI() / 180)
					* COS(Article.latitude * PI() / 180)
					* COS((50 - Article.longitude) * PI() / 180)
				) * 180 / PI() * 60 * 1.1515 * 1.609344'
        ];
        $conditions = ['distance >=' => 20];
        $result = $this->Dbo->conditions($conditions, true, true, $Article);

        $this->assertRegExp('/\) >= 20/', $result);
        $this->assertRegExp('/[`\'"]Article[`\'"].[`\'"]latitude[`\'"]/', $result);
        $this->assertRegExp('/[`\'"]Article[`\'"].[`\'"]longitude[`\'"]/', $result);
    }

    /**
     * test calculate to generate claculate statements on virtual fields
     */
    public function testVirtualFieldsInCalculate()
    {
        $Article = ClassRegistry::init('Article');
        $commentsTable = $this->Dbo->fullTableName('comments', false, false);
        $Article->virtualFields = [
            'this_moment'   => 'NOW()',
            'two'           => '1 + 1',
            'comment_count' => 'SELECT COUNT(*) FROM ' . $commentsTable .
                ' WHERE Article.id = ' . $commentsTable . '.article_id'
        ];

        $result = $this->Dbo->calculate($Article, 'count', ['this_moment']);
        $expected = 'COUNT(NOW()) AS `count`';
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->calculate($Article, 'max', ['comment_count']);
        $expected = "MAX(SELECT COUNT(*) FROM $commentsTable WHERE `Article`.`id` = `$commentsTable`.`article_id`) AS `comment_count`";
        $this->assertEquals($expected, $result);
    }

    /**
     * test reading virtual fields containing newlines when recursive > 0
     */
    public function testReadVirtualFieldsWithNewLines()
    {
        $Article = new Article();
        $Article->recursive = 1;
        $Article->virtualFields = [
            'test' => '
			User.id + User.id
			'
        ];
        $result = $this->Dbo->fields($Article, null, []);
        $result = $this->Dbo->fields($Article, $Article->alias, $result);
        $this->assertRegExp('/[`\"]User[`\"]\.[`\"]id[`\"] \+ [`\"]User[`\"]\.[`\"]id[`\"]/', $result[7]);
    }

    /**
     * test group to generate GROUP BY statements on virtual fields
     */
    public function testVirtualFieldsInGroup()
    {
        $Article = ClassRegistry::init('Article');
        $Article->virtualFields = [
            'this_year' => 'YEAR(Article.created)'
        ];

        $result = $this->Dbo->group('this_year', $Article);

        $expected = ' GROUP BY (YEAR(`Article`.`created`))';
        $this->assertEquals($expected, $result);
    }

    /**
     * test that virtualFields with complex functions and aliases work.
     */
    public function testFieldsWithComplexVirtualFields()
    {
        $Article = new Article();
        $Article->virtualFields = [
            'distance' => 'ACOS(SIN(20 * PI() / 180)
					* SIN(Article.latitude * PI() / 180)
					+ COS(20 * PI() / 180)
					* COS(Article.latitude * PI() / 180)
					* COS((50 - Article.longitude) * PI() / 180)
				) * 180 / PI() * 60 * 1.1515 * 1.609344'
        ];

        $fields = ['id', 'distance'];
        $result = $this->Dbo->fields($Article, null, $fields);
        $qs = $this->Dbo->startQuote;
        $qe = $this->Dbo->endQuote;

        $this->assertEquals("{$qs}Article{$qe}.{$qs}id{$qe}", $result[0]);
        $this->assertRegExp('/Article__distance/', $result[1]);
        $this->assertRegExp('/[`\'"]Article[`\'"].[`\'"]latitude[`\'"]/', $result[1]);
        $this->assertRegExp('/[`\'"]Article[`\'"].[`\'"]longitude[`\'"]/', $result[1]);
    }

    /**
     * test that execute runs queries.
     */
    public function testExecute()
    {
        $query = 'SELECT * FROM ' . $this->Dbo->fullTableName('articles') . ' WHERE 1 = 1';
        $this->Dbo->took = null;
        $this->Dbo->affected = null;
        $result = $this->Dbo->execute($query, ['log' => false]);
        $this->assertNotNull($result, 'No query performed! %s');
        $this->assertNull($this->Dbo->took, 'Stats were set %s');
        $this->assertNull($this->Dbo->affected, 'Stats were set %s');

        $result = $this->Dbo->execute($query);
        $this->assertNotNull($result, 'No query performed! %s');
        $this->assertNotNull($this->Dbo->took, 'Stats were not set %s');
        $this->assertNotNull($this->Dbo->affected, 'Stats were not set %s');
    }

    /**
     * test a full example of using virtual fields
     */
    public function testVirtualFieldsFetch()
    {
        $this->loadFixtures('Article', 'Comment');

        $Article = ClassRegistry::init('Article');
        $Article->virtualFields = [
            'comment_count' => 'SELECT COUNT(*) FROM ' . $this->Dbo->fullTableName('comments') .
                ' WHERE Article.id = ' . $this->Dbo->fullTableName('comments') . '.article_id'
        ];

        $conditions = ['comment_count >' => 2];
        $query = 'SELECT ' . implode(',', $this->Dbo->fields($Article, null, ['id', 'comment_count'])) .
                ' FROM ' . $this->Dbo->fullTableName($Article) . ' Article ' . $this->Dbo->conditions($conditions, true, true, $Article);
        $result = $this->Dbo->fetchAll($query);
        $expected = [[
            'Article' => ['id' => 1, 'comment_count' => 4]
        ]];
        $this->assertEquals($expected, $result);
    }

    /**
     * test reading complex virtualFields with subqueries.
     */
    public function testVirtualFieldsComplexRead()
    {
        $this->loadFixtures('DataTest', 'Article', 'Comment', 'User', 'Tag', 'ArticlesTag');

        $Article = ClassRegistry::init('Article');
        $commentTable = $this->Dbo->fullTableName('comments');
        $Article = ClassRegistry::init('Article');
        $Article->virtualFields = [
            'comment_count' => 'SELECT COUNT(*) FROM ' . $commentTable .
                ' AS Comment WHERE Article.id = Comment.article_id'
        ];
        $result = $Article->find('all');
        $this->assertTrue(count($result) > 0);
        $this->assertTrue($result[0]['Article']['comment_count'] > 0);

        $DataTest = ClassRegistry::init('DataTest');
        $DataTest->virtualFields = [
            'complicated' => 'ACOS(SIN(20 * PI() / 180)
				* SIN(DataTest.float * PI() / 180)
				+ COS(20 * PI() / 180)
				* COS(DataTest.count * PI() / 180)
				* COS((50 - DataTest.float) * PI() / 180)
				) * 180 / PI() * 60 * 1.1515 * 1.609344'
        ];
        $result = $DataTest->find('all');
        $this->assertTrue(count($result) > 0);
        $this->assertTrue($result[0]['DataTest']['complicated'] > 0);
    }

    /**
     * testIntrospectType method
     */
    public function testIntrospectType()
    {
        $this->assertEquals('integer', $this->Dbo->introspectType(0));
        $this->assertEquals('integer', $this->Dbo->introspectType(2));
        $this->assertEquals('string', $this->Dbo->introspectType('2'));
        $this->assertEquals('string', $this->Dbo->introspectType('2.2'));
        $this->assertEquals('float', $this->Dbo->introspectType(2.2));
        $this->assertEquals('string', $this->Dbo->introspectType('stringme'));
        $this->assertEquals('string', $this->Dbo->introspectType('0stringme'));

        $data = [2.2];
        $this->assertEquals('float', $this->Dbo->introspectType($data));

        $data = ['2.2'];
        $this->assertEquals('float', $this->Dbo->introspectType($data));

        $data = [2];
        $this->assertEquals('integer', $this->Dbo->introspectType($data));

        $data = ['2'];
        $this->assertEquals('integer', $this->Dbo->introspectType($data));

        $data = ['string'];
        $this->assertEquals('string', $this->Dbo->introspectType($data));

        $data = [2.2, '2.2'];
        $this->assertEquals('float', $this->Dbo->introspectType($data));

        $data = [2, '2'];
        $this->assertEquals('integer', $this->Dbo->introspectType($data));

        $data = ['string one', 'string two'];
        $this->assertEquals('string', $this->Dbo->introspectType($data));

        $data = ['2.2', 3];
        $this->assertEquals('integer', $this->Dbo->introspectType($data));

        $data = ['2.2', '0stringme'];
        $this->assertEquals('string', $this->Dbo->introspectType($data));

        $data = [2.2, 3];
        $this->assertEquals('integer', $this->Dbo->introspectType($data));

        $data = [2.2, '0stringme'];
        $this->assertEquals('string', $this->Dbo->introspectType($data));

        $data = [2, 'stringme'];
        $this->assertEquals('string', $this->Dbo->introspectType($data));

        $data = [2, '2.2', 'stringgme'];
        $this->assertEquals('string', $this->Dbo->introspectType($data));

        $data = [2, '2.2'];
        $this->assertEquals('integer', $this->Dbo->introspectType($data));

        $data = [2, 2.2];
        $this->assertEquals('integer', $this->Dbo->introspectType($data));

        // null
        $result = $this->Dbo->value(null, 'boolean');
        $this->assertEquals('NULL', $result);

        // EMPTY STRING
        $result = $this->Dbo->value('', 'boolean');
        $this->assertEquals('\'0\'', $result);

        // BOOLEAN
        $result = $this->Dbo->value('true', 'boolean');
        $this->assertEquals('\'1\'', $result);

        $result = $this->Dbo->value('false', 'boolean');
        $this->assertEquals('\'1\'', $result);

        $result = $this->Dbo->value(true, 'boolean');
        $this->assertEquals('\'1\'', $result);

        $result = $this->Dbo->value(false, 'boolean');
        $this->assertEquals('\'0\'', $result);

        $result = $this->Dbo->value(1, 'boolean');
        $this->assertEquals('\'1\'', $result);

        $result = $this->Dbo->value(0, 'boolean');
        $this->assertEquals('\'0\'', $result);

        $result = $this->Dbo->value('abc', 'boolean');
        $this->assertEquals('\'1\'', $result);

        $result = $this->Dbo->value(1.234, 'boolean');
        $this->assertEquals('\'1\'', $result);

        $result = $this->Dbo->value('1.234e05', 'boolean');
        $this->assertEquals('\'1\'', $result);

        // NUMBERS
        $result = $this->Dbo->value(123, 'integer');
        $this->assertEquals(123, $result);

        $result = $this->Dbo->value('123', 'integer');
        $this->assertEquals('123', $result);

        $result = $this->Dbo->value('0123', 'integer');
        $this->assertEquals('\'0123\'', $result);

        $result = $this->Dbo->value('0x123ABC', 'integer');
        $this->assertEquals('\'0x123ABC\'', $result);

        $result = $this->Dbo->value('0x123', 'integer');
        $this->assertEquals('\'0x123\'', $result);

        $result = $this->Dbo->value(1.234, 'float');
        $this->assertEquals(1.234, $result);

        $result = $this->Dbo->value('1.234', 'float');
        $this->assertEquals('1.234', $result);

        $result = $this->Dbo->value(' 1.234 ', 'float');
        $this->assertEquals('\' 1.234 \'', $result);

        $result = $this->Dbo->value('1.234e05', 'float');
        $this->assertEquals('\'1.234e05\'', $result);

        $result = $this->Dbo->value('1.234e+5', 'float');
        $this->assertEquals('\'1.234e+5\'', $result);

        $result = $this->Dbo->value('1,234', 'float');
        $this->assertEquals('\'1,234\'', $result);

        $result = $this->Dbo->value('FFF', 'integer');
        $this->assertEquals('\'FFF\'', $result);

        $result = $this->Dbo->value('abc', 'integer');
        $this->assertEquals('\'abc\'', $result);

        // STRINGS
        $result = $this->Dbo->value('123', 'string');
        $this->assertEquals('\'123\'', $result);

        $result = $this->Dbo->value(123, 'string');
        $this->assertEquals('\'123\'', $result);

        $result = $this->Dbo->value(1.234, 'string');
        $this->assertEquals('\'1.234\'', $result);

        $result = $this->Dbo->value('abc', 'string');
        $this->assertEquals('\'abc\'', $result);

        $result = $this->Dbo->value(' abc ', 'string');
        $this->assertEquals('\' abc \'', $result);

        $result = $this->Dbo->value('a bc', 'string');
        $this->assertEquals('\'a bc\'', $result);
    }

    /**
     * testRealQueries method
     */
    public function testRealQueries()
    {
        $this->loadFixtures('Apple', 'Article', 'User', 'Comment', 'Tag', 'Sample', 'ArticlesTag');

        $Apple = ClassRegistry::init('Apple');
        $Article = ClassRegistry::init('Article');

        $result = $this->Dbo->rawQuery('SELECT color, name FROM ' . $this->Dbo->fullTableName('apples'));
        $this->assertTrue(!empty($result));

        $result = $this->Dbo->fetchRow($result);
        $expected = [$this->Dbo->fullTableName('apples', false, false) => [
            'color' => 'Red 1',
            'name'  => 'Red Apple 1'
        ]];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->fetchAll('SELECT name FROM ' . $this->Dbo->fullTableName('apples') . ' ORDER BY id');
        $expected = [
            [$this->Dbo->fullTableName('apples', false, false) => ['name' => 'Red Apple 1']],
            [$this->Dbo->fullTableName('apples', false, false) => ['name' => 'Bright Red Apple']],
            [$this->Dbo->fullTableName('apples', false, false) => ['name' => 'green blue']],
            [$this->Dbo->fullTableName('apples', false, false) => ['name' => 'Test Name']],
            [$this->Dbo->fullTableName('apples', false, false) => ['name' => 'Blue Green']],
            [$this->Dbo->fullTableName('apples', false, false) => ['name' => 'My new apple']],
            [$this->Dbo->fullTableName('apples', false, false) => ['name' => 'Some odd color']]
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->field($this->Dbo->fullTableName('apples', false, false), 'SELECT color, name FROM ' . $this->Dbo->fullTableName('apples') . ' ORDER BY id');
        $expected = [
            'color' => 'Red 1',
            'name'  => 'Red Apple 1'
        ];
        $this->assertEquals($expected, $result);

        $Apple->unbindModel([], false);
        $result = $this->Dbo->read($Apple, [
            'fields'     => [$Apple->escapeField('name')],
            'conditions' => null,
            'recursive'  => -1
        ]);
        $expected = [
            ['Apple' => ['name' => 'Red Apple 1']],
            ['Apple' => ['name' => 'Bright Red Apple']],
            ['Apple' => ['name' => 'green blue']],
            ['Apple' => ['name' => 'Test Name']],
            ['Apple' => ['name' => 'Blue Green']],
            ['Apple' => ['name' => 'My new apple']],
            ['Apple' => ['name' => 'Some odd color']]
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Dbo->read($Article, [
            'fields'     => ['id', 'user_id', 'title'],
            'conditions' => null,
            'recursive'  => 1
        ]);

        $this->assertTrue(Set::matches('/Article[id=1]', $result));
        $this->assertTrue(Set::matches('/Comment[id=1]', $result));
        $this->assertTrue(Set::matches('/Comment[id=2]', $result));
        $this->assertFalse(Set::matches('/Comment[id=10]', $result));
    }

    /**
     * @expectedException MissingConnectionException
     */
    public function testExceptionOnBrokenConnection()
    {
        new Mysql([
            'driver'   => 'mysql',
            'host'     => 'imaginary_host',
            'login'    => 'mark',
            'password' => 'inyurdatabase',
            'database' => 'imaginary'
        ]);
    }

    /**
     * testStatements method
     */
    public function testUpdateStatements()
    {
        $this->loadFixtures('Article', 'User');
        $test = ConnectionManager::getDatasource('test');
        $db = $test->config['database'];

        $this->Dbo = $this->getMock('Mysql', ['execute'], [$test->config]);

        $this->Dbo->expects($this->at(0))->method('execute')
            ->with("UPDATE `$db`.`articles` SET `field1` = 'value1'  WHERE 1 = 1");

        $this->Dbo->expects($this->at(1))->method('execute')
            ->with("UPDATE `$db`.`articles` AS `Article` LEFT JOIN `$db`.`users` AS `User` ON " .
                '(`Article`.`user_id` = `User`.`id`)' .
                ' SET `Article`.`field1` = 2  WHERE 2=2');

        $this->Dbo->expects($this->at(2))->method('execute')
            ->with("UPDATE `$db`.`articles` AS `Article` LEFT JOIN `$db`.`users` AS `User` ON " .
                '(`Article`.`user_id` = `User`.`id`)' .
                ' SET `Article`.`field1` = \'value\'  WHERE `index` = \'val\'');

        $Article = new Article();

        $this->Dbo->update($Article, ['field1'], ['value1']);
        $this->Dbo->update($Article, ['field1'], ['2'], '2=2');
        $this->Dbo->update($Article, ['field1'], ['\'value\''], ['index' => 'val']);
    }

    /**
     * Test deletes with a mock.
     */
    public function testDeleteStatements()
    {
        $this->loadFixtures('Article', 'User');
        $test = ConnectionManager::getDatasource('test');
        $db = $test->config['database'];

        $this->Dbo = $this->getMock('Mysql', ['execute'], [$test->config]);

        $this->Dbo->expects($this->at(0))->method('execute')
            ->with("DELETE  FROM `$db`.`articles`  WHERE 1 = 1");

        $this->Dbo->expects($this->at(1))->method('execute')
            ->with("DELETE `Article` FROM `$db`.`articles` AS `Article` LEFT JOIN `$db`.`users` AS `User` " .
                'ON (`Article`.`user_id` = `User`.`id`)' .
                '  WHERE 1 = 1');

        $this->Dbo->expects($this->at(2))->method('execute')
            ->with("DELETE `Article` FROM `$db`.`articles` AS `Article` LEFT JOIN `$db`.`users` AS `User` " .
                'ON (`Article`.`user_id` = `User`.`id`)' .
                '  WHERE 2=2');
        $Article = new Article();

        $this->Dbo->delete($Article);
        $this->Dbo->delete($Article, true);
        $this->Dbo->delete($Article, '2=2');
    }

    /**
     * Test deletes without complex conditions.
     */
    public function testDeleteNoComplexCondition()
    {
        $this->loadFixtures('Article', 'User');
        $test = ConnectionManager::getDatasource('test');
        $db = $test->config['database'];

        $this->Dbo = $this->getMock('Mysql', ['execute'], [$test->config]);

        $this->Dbo->expects($this->at(0))->method('execute')
            ->with("DELETE `Article` FROM `$db`.`articles` AS `Article`   WHERE `id` = 1");

        $this->Dbo->expects($this->at(1))->method('execute')
            ->with("DELETE `Article` FROM `$db`.`articles` AS `Article`   WHERE NOT (`id` = 1)");

        $Article = new Article();

        $conditions = ['id' => 1];
        $this->Dbo->delete($Article, $conditions);
        $conditions = ['NOT' => ['id' => 1]];
        $this->Dbo->delete($Article, $conditions);
    }

    /**
     * Test truncate with a mock.
     */
    public function testTruncateStatements()
    {
        $this->loadFixtures('Article', 'User');
        $db = ConnectionManager::getDatasource('test');
        $schema = $db->config['database'];
        $Article = new Article();

        $this->Dbo = $this->getMock('Mysql', ['execute'], [$db->config]);

        $this->Dbo->expects($this->at(0))->method('execute')
            ->with("TRUNCATE TABLE `$schema`.`articles`");
        $this->Dbo->truncate($Article);

        $this->Dbo->expects($this->at(0))->method('execute')
            ->with("TRUNCATE TABLE `$schema`.`articles`");
        $this->Dbo->truncate('articles');

        // #2355: prevent duplicate prefix
        $this->Dbo->config['prefix'] = 'tbl_';
        $Article->tablePrefix = 'tbl_';
        $this->Dbo->expects($this->at(0))->method('execute')
            ->with("TRUNCATE TABLE `$schema`.`tbl_articles`");
        $this->Dbo->truncate($Article);

        $this->Dbo->expects($this->at(0))->method('execute')
            ->with("TRUNCATE TABLE `$schema`.`tbl_articles`");
        $this->Dbo->truncate('articles');
    }

    /**
     * Test nested transaction
     */
    public function testNestedTransaction()
    {
        $nested = $this->Dbo->useNestedTransactions;
        $this->Dbo->useNestedTransactions = true;
        if ($this->Dbo->nestedTransactionSupported() === false) {
            $this->Dbo->useNestedTransactions = $nested;
            $this->skipIf(true, 'The MySQL server do not support nested transaction');
        }

        $this->loadFixtures('Inno');
        $model = ClassRegistry::init('Inno');
        $model->hasOne = $model->hasMany = $model->belongsTo = $model->hasAndBelongsToMany = [];
        $model->cacheQueries = false;
        $this->Dbo->cacheMethods = false;

        $this->assertTrue($this->Dbo->begin());
        $this->assertNotEmpty($model->read(null, 1));

        $this->assertTrue($this->Dbo->begin());
        $this->assertTrue($model->delete(1));
        $this->assertEmpty($model->read(null, 1));
        $this->assertTrue($this->Dbo->rollback());
        $this->assertNotEmpty($model->read(null, 1));

        $this->assertTrue($this->Dbo->begin());
        $this->assertTrue($model->delete(1));
        $this->assertEmpty($model->read(null, 1));
        $this->assertTrue($this->Dbo->commit());
        $this->assertEmpty($model->read(null, 1));

        $this->assertTrue($this->Dbo->rollback());
        $this->assertNotEmpty($model->read(null, 1));

        $this->Dbo->useNestedTransactions = $nested;
    }

    /**
     * Test that value() quotes set values even when numeric.
     */
    public function testSetValue()
    {
        $column = 'set(\'a\',\'b\',\'c\')';
        $result = $this->Dbo->value('1', $column);
        $this->assertEquals('\'1\'', $result);

        $result = $this->Dbo->value(1, $column);
        $this->assertEquals('\'1\'', $result);

        $result = $this->Dbo->value('a', $column);
        $this->assertEquals('\'a\'', $result);
    }

    /**
     * Test isConnected
     */
    public function testIsConnected()
    {
        $this->Dbo->disconnect();
        $this->assertFalse($this->Dbo->isConnected(), 'Not connected now.');

        $this->Dbo->connect();
        $this->assertTrue($this->Dbo->isConnected(), 'Should be connected.');
    }

    /**
     * Test insertMulti with id position.
     */
    public function testInsertMultiId()
    {
        $this->loadFixtures('Article');
        $Article = ClassRegistry::init('Article');
        $db = $Article->getDatasource();
        $datetime = date('Y-m-d H:i:s');
        $data = [
            [
                'user_id'   => 1,
                'title'     => 'test',
                'body'      => 'test',
                'published' => 'N',
                'created'   => $datetime,
                'updated'   => $datetime,
                'id'        => 100,
            ],
            [
                'user_id'   => 1,
                'title'     => 'test 101',
                'body'      => 'test 101',
                'published' => 'N',
                'created'   => $datetime,
                'updated'   => $datetime,
                'id'        => 101,
            ]
        ];
        $result = $db->insertMulti('articles', array_keys($data[0]), $data);
        $this->assertTrue($result, 'Data was saved');

        $data = [
            [
                'id'        => 102,
                'user_id'   => 1,
                'title'     => 'test',
                'body'      => 'test',
                'published' => 'N',
                'created'   => $datetime,
                'updated'   => $datetime,
            ],
            [
                'id'        => 103,
                'user_id'   => 1,
                'title'     => 'test 101',
                'body'      => 'test 101',
                'published' => 'N',
                'created'   => $datetime,
                'updated'   => $datetime,
            ]
        ];
        $result = $db->insertMulti('articles', array_keys($data[0]), $data);
        $this->assertTrue($result, 'Data was saved');
    }
}
