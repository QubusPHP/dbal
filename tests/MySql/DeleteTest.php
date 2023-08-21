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

namespace Qubus\Tests\Dbal\MySql;

use PHPUnit\Framework\Assert;
use Qubus\Dbal\DB;
use Qubus\Exception\Exception;

try {
    $connection = DB::connection([
        'driver' => 'mysql',
        'username' => 'root',
        'password' => isset($_SERVER['DB']) ? '' : 'root',
        'database' => 'test_database',
    ]);
} catch (Exception $e) {
}

it('should build simple delete string.', function () use ($connection) {
    $expected = "DELETE FROM `my_table`";

        $query = $connection
            ->delete()->from('my_table')
            ->compile();

        Assert::assertEquals($expected, $query);
});

it('should build delete string with where condition.', function () use ($connection) {
    $expected = "DELETE FROM `my_table` WHERE `field` = 'value'";

    $query = $connection
        ->delete()->from('my_table')
        ->where('field', 'value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build delete string with where field IS NULL.', function () use ($connection) {
    $expected = "DELETE FROM `my_table` WHERE `field` IS NULL";

    $query = $connection
        ->delete()->from('my_table')
        ->where('field', null)
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build delete string with where field IS NOT NULL.', function () use ($connection) {
    $expected = "DELETE FROM `my_table` WHERE `field` IS NOT NULL";

    $query = $connection
        ->delete()->from('my_table')
        ->where('field', '!=', null)
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build delete string with orWhere condition.', function () use ($connection) {
    $expected = "DELETE FROM `my_table` WHERE `field` = 'value' OR `other` != 'other value'";

    $query = $connection
        ->delete()->from('my_table')
        ->where('field', 'value')
        ->orWhere('other', '!=', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build delete string with andWhere condition.', function () use ($connection) {
    $expected = "DELETE FROM `my_table` WHERE `field` = 'value' AND `other` != 'other value'";

    $query = $connection
        ->delete()->from('my_table')
        ->where('field', 'value')
        ->andWhere('other', '!=', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build delete string with where grouping.', function () use ($connection) {
    $expected = "DELETE FROM `my_table` WHERE `field` = 'value' AND (`other` != 'other value' OR `field` = 'something')";

    $query = $connection
        ->delete()->from('my_table')
        ->where('field', 'value')
        ->andWhereOpen()
        ->Where('other', '!=', 'other value')
        ->orWhere('field', '=', 'something')
        ->andWhereClose()
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build delete string with whereIn condition.', function () use ($connection) {
    $expected = "DELETE FROM `my_table` WHERE `field` IN (1, 2, 3)";

    $query = $connection
        ->delete()->from('my_table')
        ->where('field', 'in', [1, 2, 3])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build delete string with whereNotIn condition.', function () use ($connection) {
    $expected = "DELETE FROM `my_table` WHERE `field` NOT IN (1, 2, 3)";

    $query = $connection
        ->delete()->from('my_table')
        ->where('field', 'not in', [1, 2, 3])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build delete string with function.', function () use ($connection) {
    $expected = "DELETE FROM `my_table` WHERE CHAR_LENGTH(`field`) > 2 AND CHAR_LENGTH(`field`) < 20";

    $query = $connection
        ->delete()->from('my_table')
        ->where(DB::fnc('char_length', 'field'), '>', 2)
        ->where('CHAR_LENGTH("field")', '<', 20)
        ->compile();

    Assert::assertEquals($expected, $query);
});
