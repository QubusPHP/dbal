<?php

declare(strict_types=1);

namespace Qubus\Tests\Dbal\SqLite;

use PHPUnit\Framework\Assert;
use Qubus\Dbal\DB;
use Qubus\Exception\Exception;

try {
    $connection = DB::connection([
        'driver' => 'sqlite',
        'username' => 'root',
        'password' => isset($_SERVER['DB']) ? '' : 'root',
        'database' => 'test_database',
    ]);
} catch (Exception $e) {
}

it('should build simple update string.', function () use ($connection) {
    $expected = "UPDATE `my_table` SET `field` = 'value'";

    $query = $connection
        ->update('my_table')
        ->set([
            'field' => 'value',
        ])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build update string with multiple field sets.', function () use ($connection) {
    $expected = "UPDATE `my_table` SET `field` = 'value', `another_field` = 1";

    $query = $connection
        ->update('my_table')
        ->set([
            'field'         => 'value',
            'another_field' => true,
        ])
        ->compile();

    Assert::assertEquals($expected, $query);
});

it('should build update string with where condition.', function () use ($connection) {
    $expected = "UPDATE `my_table` SET `field` = 'value' WHERE `field` = 'other value'";

    $query = $connection
        ->update('my_table')
        ->set([
            'field' => 'value',
        ])
        ->where('field', 'other value')
        ->compile();

    Assert::assertEquals($expected, $query);
});
