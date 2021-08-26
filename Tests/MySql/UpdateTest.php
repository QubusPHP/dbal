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
use PHPUnit\Framework\TestCase;
use Qubus\Dbal\DB;

class UpdateTest extends TestCase
{
    private $connection;

    public function setUp(): void
    {
        $this->connection = DB::connection([
            'driver'   => 'mysql',
            'username' => 'root',
            'password' => isset($_SERVER['DB']) ? '' : 'root',
            'database' => 'test_database',
        ]);
    }

    public function testBuildSimple()
    {
        $expected = "UPDATE `my_table` SET `field` = 'value'";

        $query = $this->connection
            ->update('my_table')
            ->set([
                'field' => 'value',
            ])
            ->compile();

        Assert::assertEquals($expected, $query);
    }

    public function testBuildMultiple()
    {
        $expected = "UPDATE `my_table` SET `field` = 'value', `another_field` = 1";

        $query = $this->connection
            ->update('my_table')
            ->set([
                'field'         => 'value',
                'another_field' => true,
            ])
            ->compile();

        Assert::assertEquals($expected, $query);
    }

    public function testBuildWhere()
    {
        $expected = "UPDATE `my_table` SET `field` = 'value' WHERE `field` = 'other value'";

        $query = $this->connection
            ->update('my_table')
            ->set([
                'field' => 'value',
            ])
            ->where('field', 'other value')
            ->compile();

        Assert::assertEquals($expected, $query);
    }
}
