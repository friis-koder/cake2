<?php
/**
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
 * @package       Cake.Model
 *
 * @since         CakePHP(tm) v 0.2.9
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Model', 'Model');

/**
 * ACL Node
 *
 * @package       Cake.Model
 */
class AclNode extends Model
{
    /**
     * Explicitly disable in-memory query caching for ACL models
     *
     * @var bool
     */
    public $cacheQueries = false;

    /**
     * ACL models use the Tree behavior
     *
     * @var array
     */
    public $actsAs = ['Tree' => ['type' => 'nested']];

    /**
     * Constructor
     *
     * @param bool|int|string|array $id Set this ID for this model on startup,
     *   can also be an array of options, see above.
     * @param string $table Name of database table to use.
     * @param string $ds DataSource connection name.
     */
    public function __construct($id = false, $table = null, $ds = null)
    {
        $config = Configure::read('Acl.database');
        if (isset($config)) {
            $this->useDbConfig = $config;
        }
        parent::__construct($id, $table, $ds);
    }

    /**
     * Retrieves the Aro/Aco node for this model
     *
     * @param string|array|Model $ref Array with 'model' and 'foreign_key', model object, or string value
     *
     * @throws CakeException when binding to a model that doesn't exist.
     *
     * @return array Node found in database
     */
    public function node($ref = null)
    {
        $db = $this->getDataSource();
        $type = $this->alias;
        $result = null;

        if (!empty($this->useTable)) {
            $table = $this->useTable;
        } else {
            $table = Inflector::pluralize(Inflector::underscore($type));
        }

        if (empty($ref)) {
            return null;
        } elseif (is_string($ref)) {
            $path = explode('/', $ref);
            $start = $path[0];
            unset($path[0]);

            $queryData = [
                'conditions' => [
                    $db->name("{$type}.lft") . ' <= ' . $db->name("{$type}0.lft"),
                    $db->name("{$type}.rght") . ' >= ' . $db->name("{$type}0.rght")],
                'fields' => ['id', 'parent_id', 'model', 'foreign_key', 'alias'],
                'joins'  => [[
                    'table'      => $table,
                    'alias'      => "{$type}0",
                    'type'       => 'INNER',
                    'conditions' => ["{$type}0.alias" => $start]
                ]],
                'order' => $db->name("{$type}.lft") . ' DESC'
            ];

            $conditionsAfterJoin = [];

            foreach ($path as $i => $alias) {
                $j = $i - 1;

                $queryData['joins'][] = [
                    'table'      => $table,
                    'alias'      => "{$type}{$i}",
                    'type'       => 'INNER',
                    'conditions' => [
                        "{$type}{$i}.alias" => $alias
                    ]
                ];

                // it will be better if this conditions will performs after join operation
                $conditionsAfterJoin[] = $db->name("{$type}{$j}.id") . ' = ' . $db->name("{$type}{$i}.parent_id");
                $conditionsAfterJoin[] = $db->name("{$type}{$i}.rght") . ' < ' . $db->name("{$type}{$j}.rght");
                $conditionsAfterJoin[] = $db->name("{$type}{$i}.lft") . ' > ' . $db->name("{$type}{$j}.lft");

                $queryData['conditions'] = ['or' => [
                    $db->name("{$type}.lft") . ' <= ' . $db->name("{$type}0.lft") . ' AND ' . $db->name("{$type}.rght") . ' >= ' . $db->name("{$type}0.rght"),
                    $db->name("{$type}.lft") . ' <= ' . $db->name("{$type}{$i}.lft") . ' AND ' . $db->name("{$type}.rght") . ' >= ' . $db->name("{$type}{$i}.rght")]
                ];
            }
            $queryData['conditions'] = array_merge($queryData['conditions'], $conditionsAfterJoin);
            $result = $db->read($this, $queryData, -1);
            $path = array_values($path);

            if (!isset($result[0][$type]) ||
                (!empty($path) && $result[0][$type]['alias'] != $path[count($path) - 1]) ||
                (empty($path) && $result[0][$type]['alias'] != $start)
            ) {
                return false;
            }
        } elseif (is_object($ref) && $ref instanceof Model) {
            $ref = ['model' => $ref->name, 'foreign_key' => $ref->id];
        } elseif (is_array($ref) && !(isset($ref['model']) && isset($ref['foreign_key']))) {
            $name = key($ref);
            list(, $alias) = pluginSplit($name);

            $model = ClassRegistry::init(['class' => $name, 'alias' => $alias]);

            if (empty($model)) {
                throw new CakeException('cake_dev', 'Model class \'%s\' not found in AclNode::node() when trying to bind %s object', $type, $this->alias);
            }

            $tmpRef = null;
            if (method_exists($model, 'bindNode')) {
                $tmpRef = $model->bindNode($ref);
            }
            if (empty($tmpRef)) {
                $ref = ['model' => $alias, 'foreign_key' => $ref[$name][$model->primaryKey]];
            } else {
                if (is_string($tmpRef)) {
                    return $this->node($tmpRef);
                }
                $ref = $tmpRef;
            }
        }
        if (is_array($ref)) {
            if (is_array(current($ref)) && is_string(key($ref))) {
                $name = key($ref);
                $ref = current($ref);
            }
            foreach ($ref as $key => $val) {
                if (strpos($key, $type) !== 0 && strpos($key, '.') === false) {
                    unset($ref[$key]);
                    $ref["{$type}0.{$key}"] = $val;
                }
            }
            $queryData = [
                'conditions' => $ref,
                'fields'     => ['id', 'parent_id', 'model', 'foreign_key', 'alias'],
                'joins'      => [[
                    'table'      => $table,
                    'alias'      => "{$type}0",
                    'type'       => 'INNER',
                    'conditions' => [
                        $db->name("{$type}.lft") . ' <= ' . $db->name("{$type}0.lft"),
                        $db->name("{$type}.rght") . ' >= ' . $db->name("{$type}0.rght")
                    ]
                ]],
                'order' => $db->name("{$type}.lft") . ' DESC'
            ];
            $result = $db->read($this, $queryData, -1);

            if (!$result) {
                throw new CakeException(__d('cake_dev', 'AclNode::node() - Couldn\'t find %s node identified by "%s"', $type, print_r($ref, true)));
            }
        }

        return $result;
    }
}
