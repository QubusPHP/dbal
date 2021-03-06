<?php

/**
 * Qubus\Dbal
 *
 * @link       https://github.com/QubusPHP/dbal
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Dbal\Sql;

use Qubus\Dbal\Sql\Compiler;

use function array_key_exists;
use function array_map;
use function array_pop;
use function count;
use function extract;
use function implode;
use function is_array;
use function method_exists;
use function stripos;
use function strtoupper;
use function substr;
use function trim;
use function ucfirst;

abstract class Sql extends Compiler
{
    /**
     * Compiles an insert query
     *
     * @return  string  compiled INSERT query
     */
    public function compileSelect()
    {
        $sql = $this->compilePartSelect();
        $sql .= $this->compilePartFrom();
        $sql .= $this->compilePartJoin();
        $sql .= $this->compilePartWhere();
        $sql .= $this->compilePartGroupBy();
        $sql .= $this->compilePartHaving();
        $sql .= $this->compilePartOrderBy();
        $sql .= $this->compilePartLimitOffset();
        return $sql;
    }

    /**
     * Compiles an update query
     *
     * @return  string  compiled UPDATE query
     */
    public function compileUpdate()
    {
        $sql = $this->compilePartUpdate();
        $sql .= $this->compilePartSet();
        $sql .= $this->compilePartWhere();
        $sql .= $this->compilePartOrderBy();
        $sql .= $this->compilePartLimitOffset();
        return $sql;
    }

    /**
     * Compiles a delete query
     *
     * @return  string  compiled DELETE query
     */
    public function compileDelete()
    {
        $sql = $this->compilePartDelete();
        $sql .= $this->compilePartWhere();
        $sql .= $this->compilePartOrderBy();
        $sql .= $this->compilePartLimitOffset();
        return $sql;
    }

    /**
     * Compiles an insert query
     *
     * @return  string  compiled INSERT query
     */
    public function compileInsert()
    {
        $sql = $this->compilePartInsert();
        $sql .= $this->compilePartInsertValues();
        return $sql;
    }

    /**
     * Compiles field parts
     *
     * @param   string  $type  field/query type
     * @return  string  compiled field sql
     */
    protected function compilePartFields($type)
    {
        $fieldsSql = [];
        $fields = $this->prepareFields($this->query['fields']);

        foreach ($fields as $data) {
            if ($type === 'alter') {
                if ($data['newName'] && $data['name'] !== $data['newName']) {
                    $type = 'change';
                } else {
                    $type = 'modify';
                }
            }

            $fsql = $type !== 'create' ? strtoupper($type) . ' ' : '';
            $fsql .= $this->quoteIdentifier($data['name']) . ' ';

            if ($data['newName']) {
                $fsql .= $this->quoteIdentifier($data['newName']) . ' ';
            }

            $fsql .= strtoupper($data['type']);

            if ($data['constraint']) {
                $constraint = is_array($data['constraint']) ? $data['constraint'] : [$data['constraint']];
                $fsql .= '(' . implode(', ', array_map([$this, 'quote'], $constraint)) . ')';
            }

            if ($data['charset']) {
                $fsql .= ' ' . $this->compilePartCharset($data['charset']);
            }

            if ($data['primary']) {
                $fsql .= ' PRIMARY KEY';
            }

            if ($data['unsigned']) {
                $fsql .= ' UNSIGNED';
            }

            if ($data['defaultValue']) {
                $fsql .= ' DEFAULT ' . $this->quote($data['defaultValue']);
            }

            if ($data['nullable']) {
                $fsql .= ' NULL';
            } else {
                $fsql .= ' NOT NULL';
            }

            if ($data['incremental']) {
                $fsql .= ' AUTO_INCREMENT';
            }

            if ($data['first']) {
                $fsql .= ' FIRST';
            }

            if ($data['after']) {
                $fsql .= ' AFTER ' . $this->quoteIdentifier($data['after']);
            }

            if ($data['comments']) {
                $fsql .= ' COMMENT ' . $this->quote($data['comments']);
            }

            $fieldsSql[] = $fsql;
        }

        return implode(', ', $fieldsSql);
    }

    /**
     * Prepares the fields for rendering.
     *
     * @param   array  $fields  array with field objects
     * @return  array  array with prepped field objects
     */
    protected function prepareFields($fields)
    {
        return array_map(function ($field) {
            return $field->getContents();
        }, $fields);
    }

    /**
     * Compiles the ENGINE statement
     *
     * @return  string  compiled ENGINE statement
     */
    protected function compilePartEngine()
    {
        return $this->query['engine'] ? ' ENGINE = ' . $this->query['engine'] : '';
    }

    /**
     * Compiles charset statements.
     *
     * @param   string  $charset  charset to compile
     * @return  string  compiled charset statement
     */
    protected function compilePartCharset($charset)
    {
        if (empty($charset)) {
            return '';
        }

        if (($pos = stripos($charset, '_')) !== false) {
            $charset = ' CHARACTER SET ' . substr($charset, 0, $pos) . ' COLLATE ' . $charset;
        } else {
            $charset = ' CHARACTER SET ' . $charset;
        }

        isset($this->query['charsetIsDefault']) && $this->query['charsetIsDefault'] && $charset = ' DEFAULT' . $charset;

        return $charset;
    }

    /**
     * Compiles conditions for where and having statements.
     *
     * @param   array   $conditions  conditions array
     * @return  string  compiled conditions
     */
    protected function compileConditions($conditions)
    {
        $parts = [];
        $last = false;

        foreach ($conditions as $c) {
            if (isset($c['type']) && count($parts) > 0) {
                $parts[] = ' ' . strtoupper($c['type']) . ' ';
            }

            if ($useNot = isset($c['not']) && $c['not']) {
                $parts[] = count($parts) > 0 ? 'NOT ' : ' NOT ';
            }

            if (isset($c['nesting'])) {
                if ($c['nesting'] === 'open') {
                    if ($last === '(') {
                        array_pop($parts);

                        if ($useNot) {
                            array_pop($parts);
                            $parts[] = ' NOT ';
                        }
                    }

                    $last = '(';
                    $parts[] = '(';
                } else {
                    $last = ')';
                    $parts[] = ')';
                }
            } else {
                if ($last === '(') {
                    array_pop($parts);

                    if ($useNot) {
                        array_pop($parts);
                        $parts[] = ' NOT ';
                    }
                }

                $last = false;
                $c['op'] = trim($c['op']);

                if ($c['value'] === null) {
                    if ($c['op'] === '!=') {
                        $c['op'] = 'IS NOT';
                    } elseif ($c['op'] === '=') {
                        $c['op'] = 'IS';
                    }
                }

                $c['op'] = strtoupper($c['op']);

                if ($c['op'] === 'BETWEEN' && is_array($c['value'])) {
                    [$min, $max] = $c['value'];
                    $c['value'] = $this->quote($min) . ' AND ' . $this->quote($max);
                } else {
                    $c['value'] = $this->quote($c['value']);
                }

                $c['field'] = $this->quoteIdentifier($c['field']);
                $parts[] = $c['field'] . ' ' . $c['op'] . ' ' . $c['value'];
            }
        }

        return trim(implode('', $parts));
    }

    /**
     * Compiles SQL functions
     *
     * @param   object  $value   function object
     * @return  string  compiles SQL function
     */
    public function compilePartFnc($value)
    {
        $fnc = ucfirst($value->getFnc());

        if (method_exists($this, 'compileFnc' . $fnc)) {
            return $this->{'compilFnc' . $fnc}($value);
        }

        $quoteFnc = $value->quoteAs() === 'identifier' ? 'quoteIdentifier' : 'quote';

        return strtoupper($fnc) . '(' . implode(', ', array_map([$this, $quoteFnc], $value->getParams())) . ')';
    }

    /**
     * Compiles the insert into part.
     *
     * @return  string  compiled inter into part
     */
    protected function compilePartInsert()
    {
        return 'INSERT INTO ' . $this->quoteIdentifier($this->query['table']);
    }

    /**
     * Compiles the insert values.
     *
     * @return  string  compiled values part
     */
    protected function compilePartInsertValues()
    {
        $columns = array_map([$this, 'quoteIdentifier'], $this->query['columns']);
        $sql = ' (' . implode(', ', $columns) . ') VALUES (';
        $parts = [];

        foreach ($this->query['values'] as $row) {
            foreach ($this->query['columns'] as $c) {
                if (array_key_exists($c, $row)) {
                    $parts[] = $this->quote($row[$c]);
                } else {
                    $parts[] = 'NULL';
                }
            }
        }

        return $sql . implode(', ', $parts) . ')';
    }

    /**
     * Compiles a select part.
     *
     * @return  string  compiled select part
     */
    protected function compilePartSelect()
    {
        $columns = $this->query['columns'];
        empty($columns) && $columns = ['*'];
        $columns = array_map([$this, 'quoteIdentifier'], $columns);
        return 'SELECT' . ($this->query['distinct'] === true ? ' DISTINCT ' : ' ') . trim(implode(', ', $columns));
    }

    /**
     * Compiles a delete part.
     *
     * @return  string  compiled delete part
     */
    protected function compilePartDelete()
    {
        return 'DELETE FROM ' . $this->quoteIdentifier($this->query['table']);
    }

    /**
     * Compiles an update part.
     *
     * @return  string  compiled update part
     */
    protected function compilePartUpdate()
    {
        return 'UPDATE ' . $this->quoteIdentifier($this->query['table']);
    }

    /**
     * Compiles a from part.
     *
     * @return  string  compiled from part
     */
    protected function compilePartFrom()
    {
        $tables = $this->query['table'];
        is_array($tables) || $tables = [$tables];
        return ' FROM ' . implode(', ', array_map([$this, 'quoteIdentifier'], $tables));
    }

    /**
     * Compiles the where part.
     *
     * @return  string  compiled where part
     */
    protected function compilePartWhere()
    {
        if (! empty($this->query['where'])) {
            // Add selection conditions
            return ' WHERE ' . $this->compileConditions($this->query['where']);
        }

        return '';
    }

    /**
     * Compiles the set part.
     *
     * @return string Compiled set part.
     */
    protected function compilePartSet()
    {
        if (! empty($this->query['values'])) {
            $parts = [];

            foreach ($this->query['values'] as $k => $v) {
                $parts[] = $this->quoteIdentifier($k) . ' = ' . $this->quote($v);
            }

            return ' SET ' . implode(', ', $parts);
        }

        return '';
    }

    /**
     * Compiles the having part.
     *
     * @return  string  compiled HAVING statement
     */
    protected function compilePartHaving()
    {
        if (! empty($this->query['having'])) {
            // Add selection conditions
            return ' HAVING ' . $this->compileConditions($this->query['having']);
        }

        return '';
    }

    /**
     * Compiles the order by part.
     *
     * @return  string  compiled order by part
     */
    protected function compilePartOrderBy()
    {
        if (! empty($this->query['orderBy'])) {
            $sort = [];

            foreach ($this->query['orderBy'] as $group) {
                extract($group);

                if (! empty($direction)) {
                    // Make the direction uppercase
                    $direction = ' ' . strtoupper($direction);
                }

                $sort[] = $this->quoteIdentifier($column) . $direction;
            }

            return ' ORDER BY ' . implode(', ', $sort);
        }

        return '';
    }

    /**
     * Compiles the join part.
     *
     * @return  string  compiled join part
     */
    protected function compilePartJoin()
    {
        if (empty($this->query['join'])) {
            return '';
        }

        $return = [];

        foreach ($this->query['join'] as $join) {
            $join = $join->asArray();

            if ($join['type']) {
                $sql = strtoupper($join['type']) . ' JOIN';
            } else {
                $sql = 'JOIN';
            }

            // Quote the table name that is being joined
            $sql .= ' ' . $this->quoteIdentifier($join['table']) . ' ON ';

            $onSql = '';
            foreach ($join['on'] as $condition) {
                // Split the condition
                [$c1, $op, $c2, $andOr] = $condition;

                if ($op) {
                    // Make the operator uppercase and spaced
                    $op = ' ' . strtoupper($op);
                }

                // Quote each of the identifiers used for the condition
                $onSql .= (empty($onSql) ? '' : ' ' . $andOr . ' ') . $this->quoteIdentifier($c1) . $op . ' ' . $this->quoteIdentifier($c2);
            }

            // Concat the conditions "... AND ..."
            empty($onSql) || $sql .= '(' . $onSql . ')';

            $return[] = $sql;
        }

        return ' ' . implode(' ', $return);
    }

    /**
     * Compiles the group by part.
     *
     * @return string Compiler group by part.
     */
    protected function compilePartGroupBy(): string
    {
        if (! empty($this->query['groupBy'])) {
            // Add sorting
            return ' GROUP BY ' . implode(', ', array_map([$this, 'quoteIdentifier'], $this->query['groupBy']));
        }

        return '';
    }

    /**
     * Compiles the limit and offset statement.
     *
     * @return string Compiled limit and offset statement.
     */
    protected function compilePartLimitOffset(): string
    {
        $part = '';

        if (isset($this->query['limit']) && null !== $this->query['limit']) {
            $part .= ' LIMIT ' . $this->query['limit'];
        }

        if (isset($this->query['offset']) && null !== $this->query['offset']) {
            $part .= ' OFFSET ' . $this->query['offset'];
        }

        return $part;
    }

    /**
     * Escapes a value.
     *
     * @param   string  $value  value to escape
     * @return  string  escaped string
     */
    public function escape($value)
    {
        return $this->connection->quote($value);
    }
}
