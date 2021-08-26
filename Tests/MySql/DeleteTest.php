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

class DeleteTest extends TestCase
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

    public function testBuildDelete()
    {
        $expected = "DELETE FROM `my_table`";

        $query = $this->connection
            ->delete()->from('my_table')
            ->compile();

        Assert::assertEquals($expected, $query);
    }

    public function testBuildDeleteWhere()
    {
        $expected = "DELETE FROM `my_table` WHERE `field` = 'value'";

        $query = $this->connection
            ->delete()->from('my_table')
            ->where('field', 'value')
            ->compile();

        Assert::assertEquals($expected, $query);
    }

    public function testBuildDeleteWhereNull()
    {
        $expected = "DELETE FROM `my_table` WHERE `field` IS NULL";

        $query = $this->connection
            ->delete()->from('my_table')
            ->where('field', null)
            ->compile();

        Assert::assertEquals($expected, $query);
    }

    public function testBuildDeleteWhereNotNull()
    {
        $expected = "DELETE FROM `my_table` WHERE `field` IS NOT NULL";

        $query = $this->connection
            ->delete()->from('my_table')
            ->where('field', '!=', null)
            ->compile();

        Assert::assertEquals($expected, $query);
    }

    public function testBuildDeleteWhereOr()
    {
        $expected = "DELETE FROM `my_table` WHERE `field` = 'value' OR `other` != 'other value'";

        $query = $this->connection
            ->delete()->from('my_table')
            ->where('field', 'value')
            ->orWhere('other', '!=', 'other value')
            ->compile();

        Assert::assertEquals($expected, $query);
    }

    public function testBuildDeleteWhereAnd()
    {
        $expected = "DELETE FROM `my_table` WHERE `field` = 'value' AND `other` != 'other value'";

        $query = $this->connection
            ->delete()->from('my_table')
            ->where('field', 'value')
            ->andWhere('other', '!=', 'other value')
            ->compile();

        Assert::assertEquals($expected, $query);
    }

    public function testBuildDeleteWhereAndGroup()
    {
        $expected = "DELETE FROM `my_table` WHERE `field` = 'value' AND (`other` != 'other value' OR `field` = 'something')";

        $query = $this->connection
            ->delete()->from('my_table')
            ->where('field', 'value')
            ->andWhereOpen()
            ->Where('other', '!=', 'other value')
            ->orWhere('field', '=', 'something')
            ->andWhereClose()
            ->compile();

        Assert::assertEquals($expected, $query);
    }

    public function testBuildDeleteWhereIn()
    {
        $expected = "DELETE FROM `my_table` WHERE `field` IN (1, 2, 3)";

        $query = $this->connection
            ->delete()->from('my_table')
            ->where('field', [1, 2, 3])
            ->compile();

        Assert::assertEquals($expected, $query);
    }

    public function testBuildDeleteWhereNotIn()
    {
        $expected = "DELETE FROM `my_table` WHERE `field` NOT IN (1, 2, 3)";

        $query = $this->connection
            ->delete()->from('my_table')
            ->where('field', 'not in', [1, 2, 3])
            ->compile();

        Assert::assertEquals($expected, $query);
    }

    public function testBuildDeleteWhereFnc()
    {
        $expected = "DELETE FROM `my_table` WHERE CHAR_LENGTH(`field`) > 2 AND CHAR_LENGTH(`field`) < 20";

        $query = $this->connection
            ->delete()->from('my_table')
            ->where(DB::fnc('char_length', 'field'), '>', 2)
            ->where('CHAR_LENGTH("field")', '<', 20)
            ->compile();

        Assert::assertEquals($expected, $query);
    }
}
