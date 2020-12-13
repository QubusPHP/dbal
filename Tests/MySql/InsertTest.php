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


use PHPUnit\Framework\TestCase;
use Qubus\Dbal\DB;

class InsertTest extends TestCase
{
    private $connection;

    public function setUp()
    {
        $this->connection = DB::connection([
            'driver'   => 'mysql',
            'username' => 'root',
            'password' => isset($_SERVER['DB']) ? '' : 'root',
            'database' => 'test_database',
        ]);
    }

    public function testBuildInsert()
    {
        $expected = "INSERT INTO `my_table` () VALUES ()";

        $query = $this->connection
            ->insert('my_table')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildInsertWithValues()
    {
        $expected = "INSERT INTO `my_table` (`id`, `name`) VALUES (1, 'Frank')";

        $query = $this->connection
            ->insert('my_table')
            ->values([
                'id'   => 1,
                'name' => 'Frank',
            ])
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildInsertWithFunction()
    {
        $expected = "INSERT INTO `my_table` (`id`, `time`) VALUES (1, NOW())";

        $query = $this->connection
            ->insert('my_table')
            ->values([
                'id'   => 1,
                'time' => $this->connection->fnc('now'),
            ])
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildInsertWithExpression()
    {
        $expected = "INSERT INTO `my_table` (`id`, `expression`) VALUES (1, 'value')";

        $query = $this->connection
            ->insert('my_table')
            ->values([
                'id'         => 1,
                'expression' => $this->connection->expr("'value'"),
            ])
            ->compile();

        $this->assertEquals($expected, $query);
    }
}
