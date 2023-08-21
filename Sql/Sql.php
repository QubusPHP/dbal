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
     * Compiles an insert query.
     *
     * @return  string  compiled INSERT query.
     */
    public function compileSelect(): string
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
     * Compiles an update query.
     *
     * @return  string  compiled UPDATE query.
     */
    public function compileUpdate(): string
    {
        $sql = $this->compilePartUpdate();
        $sql .= $this->compilePartSet();
        $sql .= $this->compilePartWhere();
        $sql .= $this->compilePartOrderBy();
        $sql .= $this->compilePartLimitOffset();
        return $sql;
    }

    /**
     * Compiles a delete query.
     *
     * @return  string  compiled DELETE query.
     */
    public function compileDelete(): string
    {
        $sql = $this->compilePartDelete();
        $sql .= $this->compilePartWhere();
        $sql .= $this->compilePartOrderBy();
        $sql .= $this->compilePartLimitOffset();
        return $sql;
    }

    /**
     * Compiles an insert query.
     *
     * @return  string  compiled INSERT query.
     */
    public function compileInsert(): string
    {
        $sql = $this->compilePartInsert();
        $sql .= $this->compilePartInsertValues();
        return $sql;
    }

    /**
     * Compiles field parts.
     *
     * @param string $type  field/query type.
     * @return  string  compiled field sql.
     */
    protected function compilePartFields(string $type): string
    {
        $fieldsSql = [];
        $fields = $this->prepareFields(fields: $this->query['fields']);

        foreach ($fields as $data) {
            if ($type === 'alter') {
                if ($data['newName'] && $data['name'] !== $data['newName']) {
                    $type = 'change';
                } else {
                    $type = 'modify';
                }
            }

            $fsql = $type !== 'create' ? strtoupper(string: $type) . ' ' : '';
            $fsql .= $this->quoteIdentifier(identifier: $data['name']) . ' ';

            if ($data['newName']) {
                $fsql .= $this->quoteIdentifier($data['newName']) . ' ';
            }

            $fsql .= strtoupper(string: $data['type']);

            if ($data['constraint']) {
                $constraint = is_array(value: $data['constraint']) ? $data['constraint'] : [$data['constraint']];
                $fsql .= '(' . implode(separator: ', ', array: array_map(callback: [$this, 'quote'], array: $constraint)) . ')';
            }

            if ($data['charset']) {
                $fsql .= ' ' . $this->compilePartCharset(charset: $data['charset']);
            }

            if ($data['primary']) {
                $fsql .= ' PRIMARY KEY';
            }

            if ($data['unsigned']) {
                $fsql .= ' UNSIGNED';
            }

            if ($data['defaultValue']) {
                $fsql .= ' DEFAULT ' . $this->quote(value: $data['defaultValue']);
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
                $fsql .= ' AFTER ' . $this->quoteIdentifier(identifier: $data['after']);
            }

            if ($data['comments']) {
                $fsql .= ' COMMENT ' . $this->quote(value: $data['comments']);
            }

            $fieldsSql[] = $fsql;
        }

        return implode(separator: ', ', array: $fieldsSql);
    }

    /**
     * Prepares the fields for rendering.
     *
     * @param array $fields  array with field objects.
     * @return array  array with prepped field objects.
     */
    protected function prepareFields(array $fields): array
    {
        return array_map(callback: function ($field) {
            return $field->getContents();
        }, array: $fields);
    }

    /**
     * Compiles the ENGINE statement.
     *
     * @return  string  compiled ENGINE statement.
     */
    protected function compilePartEngine(): string
    {
        return $this->query['engine'] ? ' ENGINE = ' . $this->query['engine'] : '';
    }

    /**
     * Compiles charset statements.
     *
     * @param string $charset  charset to compile.
     * @return string compiled charset statement.
     */
    protected function compilePartCharset(string $charset): string
    {
        if (empty($charset)) {
            return '';
        }

        if (($pos = stripos(haystack: $charset, needle: '_')) !== false) {
            $charset = ' CHARACTER SET ' . substr(string: $charset, offset: 0, length: $pos) . ' COLLATE ' . $charset;
        } else {
            $charset = ' CHARACTER SET ' . $charset;
        }

        isset($this->query['charsetIsDefault']) && $this->query['charsetIsDefault'] && $charset = ' DEFAULT' . $charset;

        return $charset;
    }

    /**
     * Compiles conditions for where and having statements.
     *
     * @param array $conditions  conditions array.
     * @return string  compiled conditions.
     */
    protected function compileConditions(array $conditions): string
    {
        $parts = [];
        $last = false;

        foreach ($conditions as $c) {
            if (isset($c['type']) && count($parts) > 0) {
                $parts[] = ' ' . strtoupper(string: $c['type']) . ' ';
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
                $c['op'] = trim(string: $c['op']);

                if ($c['value'] === null) {
                    if ($c['op'] === '!=') {
                        $c['op'] = 'IS NOT';
                    } elseif ($c['op'] === '=') {
                        $c['op'] = 'IS';
                    }
                }

                $c['op'] = strtoupper(string: $c['op']);

                if ($c['op'] === 'BETWEEN' && is_array(value: $c['value'])) {
                    [$min, $max] = $c['value'];
                    $c['value'] = $this->quote(value: $min) . ' AND ' . $this->quote(value: $max);
                } else {
                    $c['value'] = $this->quote(value: $c['value']);
                }

                $c['field'] = $this->quoteIdentifier(identifier: $c['field']);
                $parts[] = $c['field'] . ' ' . $c['op'] . ' ' . $c['value'];
            }
        }

        return trim(string: implode(separator: '', array: $parts));
    }

    /**
     * Compiles SQL functions
     *
     * @param object $value   function object.
     * @return  string  compiles SQL function.
     */
    public function compilePartFnc(object $value): string
    {
        $fnc = ucfirst(string: $value->getFnc());

        if (method_exists(object_or_class: $this, method: 'compileFnc' . $fnc)) {
            return $this->{'compilFnc' . $fnc}($value);
        }

        $quoteFnc = $value->quoteAs() === 'identifier' ? 'quoteIdentifier' : 'quote';

        return strtoupper(string: $fnc) . '(' . implode(separator: ', ', array: array_map(callback: [$this, $quoteFnc], array: $value->getParams())) . ')';
    }

    /**
     * Compiles the insert into part.
     *
     * @return  string  compiled inter into part
     */
    protected function compilePartInsert(): string
    {
        return 'INSERT INTO ' . $this->quoteIdentifier(identifier: $this->query['table']);
    }

    /**
     * Compiles the insert values.
     *
     * @return  string  compiled values part.
     */
    protected function compilePartInsertValues(): string
    {
        $columns = array_map(callback: [$this, 'quoteIdentifier'], array: $this->query['columns']);
        $sql = ' (' . implode(separator: ', ', array: $columns) . ') VALUES (';
        $parts = [];

        foreach ($this->query['values'] as $row) {
            foreach ($this->query['columns'] as $c) {
                if (array_key_exists(key: $c, array: $row)) {
                    $parts[] = $this->quote(value: $row[$c]);
                } else {
                    $parts[] = 'NULL';
                }
            }
        }

        return $sql . implode(separator: ', ', array: $parts) . ')';
    }

    /**
     * Compiles a select part.
     *
     * @return  string  compiled select part.
     */
    protected function compilePartSelect(): string
    {
        $columns = $this->query['columns'];
        empty($columns) && $columns = ['*'];
        $columns = array_map(callback: [$this, 'quoteIdentifier'], array: $columns);
        return 'SELECT' . ($this->query['distinct'] === true ? ' DISTINCT ' : ' ') . trim(string: implode(separator: ', ', array: $columns));
    }

    /**
     * Compiles a delete part.
     *
     * @return  string  compiled delete part
     */
    protected function compilePartDelete(): string
    {
        return 'DELETE FROM ' . $this->quoteIdentifier(identifier: $this->query['table']);
    }

    /**
     * Compiles an update part.
     *
     * @return  string  compiled update part
     */
    protected function compilePartUpdate(): string
    {
        return 'UPDATE ' . $this->quoteIdentifier(identifier: $this->query['table']);
    }

    /**
     * Compiles a from part.
     *
     * @return  string  compiled from part
     */
    protected function compilePartFrom(): string
    {
        $tables = $this->query['table'];
        is_array(value: $tables) || $tables = [$tables];
        return ' FROM ' . implode(separator: ', ', array: array_map(callback: [$this, 'quoteIdentifier'], array: $tables));
    }

    /**
     * Compiles the where part.
     *
     * @return  string  compiled where part
     */
    protected function compilePartWhere(): string
    {
        if (! empty($this->query['where'])) {
            // Add selection conditions
            return ' WHERE ' . $this->compileConditions(conditions: $this->query['where']);
        }

        return '';
    }

    /**
     * Compiles the set part.
     *
     * @return string Compiled set part.
     */
    protected function compilePartSet(): string
    {
        if (! empty($this->query['values'])) {
            $parts = [];

            foreach ($this->query['values'] as $k => $v) {
                $parts[] = $this->quoteIdentifier(identifier: $k) . ' = ' . $this->quote(value: $v);
            }

            return ' SET ' . implode(separator: ', ', array: $parts);
        }

        return '';
    }

    /**
     * Compiles the having part.
     *
     * @return  string  compiled HAVING statement
     */
    protected function compilePartHaving(): string
    {
        if (! empty($this->query['having'])) {
            // Add selection conditions
            return ' HAVING ' . $this->compileConditions(conditions: $this->query['having']);
        }

        return '';
    }

    /**
     * Compiles the order by part.
     *
     * @return  string  compiled order by part
     */
    protected function compilePartOrderBy(): string
    {
        if (! empty($this->query['orderBy'])) {
            $sort = [];

            foreach ($this->query['orderBy'] as $group) {
                extract($group);

                if (! empty($direction)) {
                    // Make the direction uppercase
                    $direction = ' ' . strtoupper(string: $direction);
                }

                $sort[] = $this->quoteIdentifier(identifier: $column) . $direction;
            }

            return ' ORDER BY ' . implode(separator: ', ', array: $sort);
        }

        return '';
    }

    /**
     * Compiles the join part.
     *
     * @return  string  compiled join part
     */
    protected function compilePartJoin(): string
    {
        if (empty($this->query['join'])) {
            return '';
        }

        $return = [];

        foreach ($this->query['join'] as $join) {
            $join = $join->asArray();

            if ($join['type']) {
                $sql = strtoupper(string: $join['type']) . ' JOIN';
            } else {
                $sql = 'JOIN';
            }

            // Quote the table name that is being joined
            $sql .= ' ' . $this->quoteIdentifier(identifier: $join['table']) . ' ON ';

            $onSql = '';
            foreach ($join['on'] as $condition) {
                // Split the condition
                [$c1, $op, $c2, $andOr] = $condition;

                if ($op) {
                    // Make the operator uppercase and spaced
                    $op = ' ' . strtoupper(string: $op);
                }

                // Quote each of the identifiers used for the condition
                $onSql .= (empty($onSql) ? '' : ' ' . $andOr . ' ') . $this->quoteIdentifier(identifier: $c1) . $op . ' ' . $this->quoteIdentifier(identifier: $c2);
            }

            // Concat the conditions "... AND ..."
            empty($onSql) || $sql .= '(' . $onSql . ')';

            $return[] = $sql;
        }

        return ' ' . implode(separator: ' ', array: $return);
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
            return ' GROUP BY ' . implode(separator: ', ', array: array_map(callback: [$this, 'quoteIdentifier'], array: $this->query['groupBy']));
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
     * @param string $value  value to escape
     * @return  string  escaped string
     */
    public function escape(string $value): string
    {
        return $this->connection->quote($value);
    }
}
