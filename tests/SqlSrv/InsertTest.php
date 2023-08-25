<?php

declare(strict_types=1);

namespace Qubus\Tests\Dbal\SqlSrv;

use PHPUnit\Framework\Assert;
use Qubus\Dbal\DB;
use Qubus\Exception\Exception;

try {
    $connection = DB::connection([
        'driver' => 'sqlsrv',
        'username' => 'root',
        'password' => isset($_SERVER['DB']) ? '' : 'root',
        'database' => 'test_database',
    ]);
} catch (Exception $e) {
}

it('should build simple insert string.', function () use ($connection) {
    $expected = "INSERT INTO `my_table` () VALUES ()";

    $query = $connection
        ->insert('my_table')
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build insert string with values.', function () use ($connection) {
    $expected = "INSERT INTO `my_table` (`id`, `name`) VALUES (1, 'Frank')";

    $query = $connection
        ->insert('my_table')
        ->values([
            'id'   => 1,
            'name' => 'Frank',
        ])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build insert string with function.', function () use ($connection) {
    $expected = "INSERT INTO `my_table` (`id`, `time`) VALUES (1, NOW())";

    $query = $connection
        ->insert('my_table')
        ->values([
            'id'   => 1,
            'time' => $connection->fnc('now'),
        ])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build insert string with expression.', function () use ($connection) {
    $expected = "INSERT INTO `my_table` (`id`, `expression`) VALUES (1, 'value')";

    $query = $connection
        ->insert('my_table')
        ->values([
            'id'         => 1,
            'expression' => $connection->expr("'value'"),
        ])
        ->compile();

    Assert::assertEquals($expected, $query);
});
