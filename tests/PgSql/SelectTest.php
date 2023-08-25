<?php

declare(strict_types=1);

namespace Qubus\Tests\Dbal\PgSql;

use PHPUnit\Framework\Assert;
use Qubus\Dbal\DB;
use Qubus\Exception\Exception;

try {
    $connection = DB::connection([
        'driver' => 'pgsql',
        'username' => 'root',
        'password' => isset($_SERVER['DB']) ? '' : 'root',
        'database' => 'test_database',
    ]);
} catch (Exception $e) {
}

it('should build simple select string.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table"';

    $query = $connection
        ->select()->from('my_table')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with LIKE.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE "field" LIKE \'%this%\'';

    $query = $connection
        ->select()->from('my_table')
        ->where('field', 'like', '%this%')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with comma delimited fields.', function () use ($connection) {
    $expected = 'SELECT "column", "other" FROM "my_table"';

    $query = $connection
        ->select('column', 'other')->from('my_table')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with aliased field.', function () use ($connection) {
    $expected = 'SELECT "column" AS "alias", "other" FROM "my_table"';

    $query = $connection
        ->select(['column', 'alias'], 'other')->from('my_table')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with function.', function () use ($connection) {
    $expected = 'SELECT COUNT(*) FROM "my_table"';

    $query = $connection
        ->select(DB::fnc('count', '*'))->from('my_table')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with aliased function.', function () use ($connection) {
    $expected = 'SELECT COUNT(*) AS "num" FROM "my_table"';

    $query = $connection
        ->select(DB::fnc('count', '*')->aliasTo('num'))->from('my_table')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with aliased function in array.', function () use ($connection) {
    $expected = 'SELECT COUNT(*) AS "alias" FROM "my_table"';

    $query = $connection
        ->select([DB::fnc('count', '*'), 'alias'])->from('my_table')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with expression.', function () use ($connection) {
    $expected = 'SELECT expr FROM "my_table"';

    $query = $connection
        ->select(DB::expr('expr'))->from('my_table')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with field selection.', function () use ($connection) {
    $expected = 'SELECT "column" FROM "my_table"';

    $query = $connection
        ->select('column')->from('my_table')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with multiple tables.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table", "other_table"';

    $query = $connection
        ->select()->from('my_table', 'other_table')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with where condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE "field" = \'value\'';

    $query = $connection
        ->select()->from('my_table')
        ->where('field', 'value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with having condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" HAVING "field" = \'value\'';

    $query = $connection
        ->select()->from('my_table')
        ->having('field', 'value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with whereNot condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE "field" = \'value\' AND NOT "other_field" = \'other value\'';

    $query = $connection
        ->select()->from('my_table')
        ->where('field', 'value')
        ->andNotWhere('other_field', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with havingNot condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" HAVING "field" = \'value\' AND NOT "other_field" = \'other value\'';

    $query = $connection
        ->select()->from('my_table')
        ->having('field', 'value')
        ->andNotHaving('other_field', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with nested whereNot condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE "field" = \'value\' AND NOT ("something" = \'different\' OR NOT "this" = \'crazy\')';

    $query = $connection
        ->select()->from('my_table')
        ->where('field', 'value')
        ->andNotWhere(function ($w) {
            $w->where('something', 'different')
                ->orNotWhere('this', 'crazy');
        })
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with nested havingNot condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" HAVING "field" = \'value\' AND NOT ("something" = \'different\' OR NOT "this" = \'crazy\')';

    $query = $connection
        ->select()->from('my_table')
        ->having('field', 'value')
        ->andNotHaving(function ($w) {
            $w->having('something', 'different')
                ->orNotHaving('this', 'crazy');
        })
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with whereNull condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE "field" IS NULL';

    $query = $connection
        ->select()->from('my_table')
        ->where('field', null)
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with havingNull condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" HAVING "field" IS NULL';

    $query = $connection
        ->select()->from('my_table')
        ->having('field', null)
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with whereNotNull condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE "field" IS NOT NULL';

    $query = $connection
        ->select()->from('my_table')
        ->where('field', '!=', null)
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with havingNotNull condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" HAVING "field" IS NOT NULL';

    $query = $connection
        ->select()->from('my_table')
        ->having('field', '!=', null)
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with orHaving condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" HAVING "field" = \'value\' OR "other" != \'other value\'';

    $query = $connection
        ->select()->from('my_table')
        ->having('field', 'value')
        ->orHaving('other', '!=', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with orWhere condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE "field" = \'value\' OR "other" != \'other value\'';

    $query = $connection
        ->select()->from('my_table')
        ->where('field', 'value')
        ->orWhere('other', '!=', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with andHaving condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" HAVING "field" = \'value\' AND "other" != \'other value\'';

    $query = $connection
        ->select()->from('my_table')
        ->having('field', 'value')
        ->andHaving('other', '!=', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with andWhere condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE "field" = \'value\' AND "other" != \'other value\'';

    $query = $connection
        ->select()->from('my_table')
        ->where('field', 'value')
        ->andWhere('other', '!=', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with where grouping.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE "field" = \'value\' AND ("other" != \'other value\' OR "field" = \'something\')';

    $query = $connection
        ->select()->from('my_table')
        ->where('field', 'value')->andWhereOpen()
        ->where('other', '!=', 'other value')
        ->orWhere('field', '=', 'something')->andWhereClose()
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with having grouping.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" HAVING "field" = \'value\' AND ("other" != \'other value\' OR "field" = \'something\')';

    $query = $connection
        ->select()->from('my_table')
        ->having('field', 'value')->andHavingOpen()
        ->having('other', '!=', 'other value')
        ->orHaving('field', '=', 'something')->andHavingClose()
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with multiple where grouping.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE "field" = \'value\' AND ("other" != \'other value\' OR "field" = \'something\') AND ("age" IN (1, 2, 3) OR "age" NOT IN (2, 5, 7))';

    $query = $connection
        ->select()->from('my_table')
        ->where('field', 'value')->andWhereOpen()
        ->where('other', '!=', 'other value')
        ->orWhere('field', '=', 'something')->andWhereClose()
        ->andWhere(function ($q) {
            $q->where('age', 'in', [1, 2, 3])
                ->orWhere('age', 'not in', [2, 5, 7]);
        })
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with multiple having grouping.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" HAVING "field" = \'value\' AND ("other" != \'other value\' OR "field" = \'something\') AND ("age" IN (1, 2, 3) OR "age" NOT IN (2, 5, 7))';

    $query = $connection
        ->select()->from('my_table')
        ->having('field', 'value')->andHavingOpen()
        ->having('other', '!=', 'other value')
        ->orHaving('field', '=', 'something')->andHavingClose()
        ->andHaving(function ($q) {
            $q->having('age', 'in', [1, 2, 3])
                ->orHaving('age', 'not in', [2, 5, 7]);
        })
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with whereIn condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE "field" IN (1, 2, 3)';

    $query = $connection
        ->select()->from('my_table')
        ->where('field', 'in', [1, 2, 3])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with havingIn condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" HAVING "field" IN (1, 2, 3)';

    $query = $connection
        ->select()->from('my_table')
        ->having('field', 'in', [1, 2, 3])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with whereNotIn condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE "field" NOT IN (1, 2, 3)';

    $query = $connection
        ->select()->from('my_table')
        ->where('field', 'not in', [1, 2, 3])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with havingNotIn condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" HAVING "field" NOT IN (1, 2, 3)';

    $query = $connection
        ->select()->from('my_table')
        ->having('field', 'not in', [1, 2, 3])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with where function.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" WHERE CHAR_LENGTH("field") > 2 AND CHAR_LENGTH("field") < 20';

    $query = $connection
        ->select()->from('my_table')
        ->where(DB::fnc('char_length', 'field'), '>', 2)
        ->where('CHAR_LENGTH("field")', '<', 20)
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with simple join.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" JOIN "other_table" ON ("my_table"."field" = "other_table"."field")';

    $query = $connection
        ->select()->from('my_table')
        ->join('other_table')
        ->on('my_table.field', '=', 'other_table.field')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with join andOn condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" JOIN "other_table" ON ("my_table"."field" = "other_table"."field" AND "my_table"."other_field" = "other_table"."other_field")';

    $query = $connection
        ->select()->from('my_table')
        ->join('other_table')
        ->on('my_table.field', '=', 'other_table.field')
        ->andOn('my_table.other_field', 'other_table.other_field')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with join orOn condition.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table" JOIN "other_table" ON ("my_table"."field" = "other_table"."field" OR "my_table"."other_field" = "other_table"."other_field")';

    $query = $connection
        ->select()->from('my_table')
        ->join('other_table')
        ->on('my_table.field', '=', 'other_table.field')
        ->orOn('my_table.other_field', 'other_table.other_field')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with parameter binding.', function () use ($connection) {
    $expected = 'SELECT * FROM "my_table"';

    $query = $connection
        ->select()->from(':table')
        ->bind('table', 'my_table')
        ->compile();

    Assert::assertEquals($expected, $query);
});
