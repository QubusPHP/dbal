<?php

declare(strict_types=1);

namespace Qubus\Tests\Dbal\SqLite;

use PHPUnit\Framework\Assert;
use Qubus\Dbal\Connection\DbalPdo;
use Qubus\Dbal\DB;
use Qubus\Exception\Exception;

try {
    $connection = new DbalPdo([
        'driver' => 'sqlite',
        'path' => 'sqlite:dbal.sqlite',
        'dsn' => 'sqlite:dbal.sqlite',
        'username' => null,
        'password' => null,
    ]);

    $db = $connection->getPDO();
    $db->exec(
        statement: "CREATE TABLE `users` (
        `user_id` TEXT NOT NULL,
        `username` TEXT NOT NULL,
        `first_name` TEXT DEFAULT NULL,
        `last_name` TEXT DEFAULT NULL,
        `email` TEXT NOT NULL
        );"
    );

    $db->exec("INSERT INTO `users` VALUES('01K4C9YW0XE038Q2W8CYY6VJGA', 'parkerj', 'Joshua', 'Parker', 'joshua@joshuaparker.dev');");
    $db->exec("INSERT INTO `users` VALUES('01K6GRJ1E1E1QVHZ31K9QC9AT8', 'jamesd', 'James', 'Dunn', 'jamesdunn@gmail.com');");
    $db->exec("INSERT INTO `users` VALUES('01K6GRM8GNEDMS7J7K0MT6AJYP', 'joejonas', 'Joe', 'Jonas', 'joejonas@gmail.com');");
} catch (Exception $e) {
}

it('should build simple select string.', function () use ($connection) {
    $expected = "SELECT * FROM `users`";

    $query = $connection
        ->select()->from('users')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with LIKE.', function () use ($connection) {
    $expected = "SELECT * FROM `users` WHERE `first_name` LIKE '%Jo%'";

    $query = $connection
        ->select()->from('users')
        ->where('first_name', 'like', '%Jo%')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with comma delimited fields.', function () use ($connection) {
    $expected = "SELECT `username`, `email` FROM `users`";

    $query = $connection
        ->select('username', 'email')->from('users')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with aliased field.', function () use ($connection) {
    $expected = "SELECT `username` AS `user`, `email` FROM `users`";

    $query = $connection
        ->select(['username', 'user'], 'email')->from('users')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with function.', function () use ($connection) {
    $expected = "SELECT COUNT(*) FROM `users`";

    $query = $connection
        ->select(DB::fnc('count', '*'))->from('users')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with aliased function.', function () use ($connection) {
    $expected = "SELECT COUNT(*) AS `num` FROM `users`";

    $query = $connection
        ->select(DB::fnc('count', '*')->aliasTo('num'))->from('users')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with aliased function in array.', function () use ($connection) {
    $expected = "SELECT COUNT(*) AS `userCount` FROM `users`";

    $query = $connection
        ->select([DB::fnc('count', '*'), 'userCount'])->from('users')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with expression.', function () use ($connection) {
    $expected = "SELECT expr FROM `users`";

    $query = $connection
        ->select(DB::expr('expr'))->from('users')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with field selection.', function () use ($connection) {
    $expected = "SELECT `username` FROM `users`";

    $query = $connection
        ->select('username')->from('users')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with multiple tables.', function () use ($connection) {
    $expected = "SELECT * FROM `users`, `other_table`";

    $query = $connection
        ->select()->from('users', 'other_table')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with where condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` WHERE `username` = 'parkerj'";

    $query = $connection
        ->select()->from('users')
        ->where('username', 'parkerj')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with having condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` HAVING `last_name` = 'Parker'";

    $query = $connection
        ->select()->from('users')
        ->having('last_name', 'Parker')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with whereNot condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` WHERE `field` = 'value' AND NOT `other_field` = 'other value'";

    $query = $connection
        ->select()->from('users')
        ->where('field', 'value')
        ->andNotWhere('other_field', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with havingNot condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` HAVING `field` = 'value' AND NOT `other_field` = 'other value'";

    $query = $connection
        ->select()->from('users')
        ->having('field', 'value')
        ->andNotHaving('other_field', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with nested whereNot condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` WHERE `field` = 'value' AND NOT (`something` = 'different' OR NOT `this` = 'crazy')";

    $query = $connection
        ->select()->from('users')
        ->where('field', 'value')
        ->andNotWhere(function ($w) {
            $w->where('something', 'different')
                ->orNotWhere('this', 'crazy');
        })
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with nested havingNot condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` HAVING `field` = 'value' AND NOT (`something` = 'different' OR NOT `this` = 'crazy')";

    $query = $connection
        ->select()->from('users')
        ->having('field', 'value')
        ->andNotHaving(function ($w) {
            $w->having('something', 'different')
                ->orNotHaving('this', 'crazy');
        })
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with whereNull condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` WHERE `field` IS NULL";

    $query = $connection
        ->select()->from('users')
        ->where('field', null)
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with havingNull condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` HAVING `field` IS NULL";

    $query = $connection
        ->select()->from('users')
        ->having('field', null)
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with whereNotNull condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` WHERE `field` IS NOT NULL";

    $query = $connection
        ->select()->from('users')
        ->where('field', '!=', null)
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with havingNotNull condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` HAVING `field` IS NOT NULL";

    $query = $connection
        ->select()->from('users')
        ->having('field', '!=', null)
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with orHaving condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` HAVING `field` = 'value' OR `other` != 'other value'";

    $query = $connection
        ->select()->from('users')
        ->having('field', 'value')
        ->orHaving('other', '!=', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with orWhere condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` WHERE `field` = 'value' OR `other` != 'other value'";

    $query = $connection
        ->select()->from('users')
        ->where('field', 'value')
        ->orWhere('other', '!=', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with andHaving condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` HAVING `field` = 'value' AND `other` != 'other value'";

    $query = $connection
        ->select()->from('users')
        ->having('field', 'value')
        ->andHaving('other', '!=', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with andWhere condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` WHERE `field` = 'value' AND `other` != 'other value'";

    $query = $connection
        ->select()->from('users')
        ->where('field', 'value')
        ->andWhere('other', '!=', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with where grouping.', function () use ($connection) {
    $expected = "SELECT * FROM `users` WHERE `field` = 'value' AND (`other` != 'other value' OR `field` = 'something')";

    $query = $connection
        ->select()->from('users')
        ->where('field', 'value')->andWhereOpen()
        ->where('other', '!=', 'other value')
        ->orWhere('field', '=', 'something')->andWhereClose()
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with having grouping.', function () use ($connection) {
    $expected = "SELECT * FROM `users` HAVING `field` = 'value' AND (`other` != 'other value' OR `field` = 'something')";

    $query = $connection
        ->select()->from('users')
        ->having('field', 'value')->andHavingOpen()
        ->having('other', '!=', 'other value')
        ->orHaving('field', '=', 'something')->andHavingClose()
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with multiple where grouping.', function () use ($connection) {
    $expected = "SELECT * FROM `users` WHERE `field` = 'value' AND (`other` != 'other value' OR `field` = 'something') AND (`age` IN (1, 2, 3) OR `age` NOT IN (2, 5, 7))";

    $query = $connection
        ->select()->from('users')
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
    $expected = "SELECT * FROM `users` HAVING `field` = 'value' AND (`other` != 'other value' OR `field` = 'something') AND (`age` IN (1, 2, 3) OR `age` NOT IN (2, 5, 7))";

    $query = $connection
        ->select()->from('users')
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
    $expected = "SELECT * FROM `users` WHERE `field` IN (1, 2, 3)";

    $query = $connection
        ->select()->from('users')
        ->where('field', 'in', [1, 2, 3])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with havingIn condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` HAVING `field` IN (1, 2, 3)";

    $query = $connection
        ->select()->from('users')
        ->having('field', 'in', [1, 2, 3])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with whereNotIn condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` WHERE `field` NOT IN (1, 2, 3)";

    $query = $connection
        ->select()->from('users')
        ->where('field', 'not in', [1, 2, 3])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with havingNotIn condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` HAVING `field` NOT IN (1, 2, 3)";

    $query = $connection
        ->select()->from('users')
        ->having('field', 'not in', [1, 2, 3])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with where function.', function () use ($connection) {
    $expected = "SELECT * FROM `users` WHERE CHAR_LENGTH(`field`) > 2 AND CHAR_LENGTH(`field`) < 20";

    $query = $connection
        ->select()->from('users')
        ->where(DB::fnc('char_length', 'field'), '>', 2)
        ->where('CHAR_LENGTH("field")', '<', 20)
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with simple join.', function () use ($connection) {
    $expected = "SELECT * FROM `users` JOIN `other_table` ON (`users`.`field` = `other_table`.`field`)";

    $query = $connection
        ->select()->from('users')
        ->join('other_table')
        ->on('users.field', '=', 'other_table.field')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with join andOn condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` JOIN `other_table` ON (`users`.`field` = `other_table`.`field` AND `users`.`other_field` = `other_table`.`other_field`)";

    $query = $connection
        ->select()->from('users')
        ->join('other_table')
        ->on('users.field', '=', 'other_table.field')
        ->andOn('users.other_field', 'other_table.other_field')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with join orOn condition.', function () use ($connection) {
    $expected = "SELECT * FROM `users` JOIN `other_table` ON (`users`.`field` = `other_table`.`field` OR `users`.`other_field` = `other_table`.`other_field`)";

    $query = $connection
        ->select()->from('users')
        ->join('other_table')
        ->on('users.field', '=', 'other_table.field')
        ->orOn('users.other_field', 'other_table.other_field')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build select string with parameter binding.', function () use ($connection) {
    $expected = "SELECT * FROM `users`";

    $query = $connection
        ->select()->from(':table')
        ->bind('table', 'users')
        ->compile();

    Assert::assertEquals($expected, $query);
});
