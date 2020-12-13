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

class SelectTest extends TestCase
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

    public function testBuildSelect()
    {
        $expected = "SELECT * FROM `my_table`";

        $query = $this->connection
            ->select()->from('my_table')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectLike()
    {
        $expected = "SELECT * FROM `my_table` WHERE `field` LIKE '%this%'";

        $query = $this->connection
            ->select()->from('my_table')
            ->where('field', 'like', '%this%')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectFields()
    {
        $expected = "SELECT `column`, `other` FROM `my_table`";

        $query = $this->connection
            ->select('column', 'other')->from('my_table')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectAlias()
    {
        $expected = "SELECT `column` AS `alias`, `other` FROM `my_table`";

        $query = $this->connection
            ->select(['column', 'alias'], 'other')->from('my_table')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectFnc()
    {
        $expected = "SELECT COUNT(*) FROM `my_table`";

        $query = $this->connection
            ->select(DB::fnc('count', '*'))->from('my_table')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectFnAlias()
    {
        $expected = "SELECT COUNT(*) AS `num` FROM `my_table`";

        $query = $this->connection
            ->select(DB::fnc('count', '*')->aliasTo('num'))->from('my_table')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectFnAliasArray()
    {
        $expected = "SELECT COUNT(*) AS `alias` FROM `my_table`";

        $query = $this->connection
            ->select([DB::fnc('count', '*'), 'alias'])->from('my_table')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectExpr()
    {
        $expected = "SELECT expr FROM `my_table`";

        $query = $this->connection
            ->select(DB::expr('expr'))->from('my_table')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectField()
    {
        $expected = "SELECT `column` FROM `my_table`";

        $query = $this->connection
            ->select('column')->from('my_table')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectMultipleTables()
    {
        $expected = "SELECT * FROM `my_table`, `other_table`";

        $query = $this->connection
            ->select()->from('my_table', 'other_table')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectWhere()
    {
        $expected = "SELECT * FROM `my_table` WHERE `field` = 'value'";

        $query = $this->connection
            ->select()->from('my_table')
            ->where('field', 'value')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectHaving()
    {
        $expected = "SELECT * FROM `my_table` HAVING `field` = 'value'";

        $query = $this->connection
            ->select()->from('my_table')
            ->having('field', 'value')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectWhereNot()
    {
        $expected = "SELECT * FROM `my_table` WHERE `field` = 'value' AND NOT `other_field` = 'other value'";

        $query = $this->connection
            ->select()->from('my_table')
            ->where('field', 'value')
            ->andNotWhere('other_field', 'other value')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectHavingNot()
    {
        $expected = "SELECT * FROM `my_table` HAVING `field` = 'value' AND NOT `other_field` = 'other value'";

        $query = $this->connection
            ->select()->from('my_table')
            ->having('field', 'value')
            ->andNotHaving('other_field', 'other value')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectWhereNotNested()
    {
        $expected = "SELECT * FROM `my_table` WHERE `field` = 'value' AND NOT (`something` = 'different' OR NOT `this` = 'crazy')";

        $query = $this->connection
            ->select()->from('my_table')
            ->where('field', 'value')
            ->andNotWhere(function ($w) {
                $w->where('something', 'different')
                    ->orNotWhere('this', 'crazy');
            })
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectHavingNotNested()
    {
        $expected = "SELECT * FROM `my_table` HAVING `field` = 'value' AND NOT (`something` = 'different' OR NOT `this` = 'crazy')";

        $query = $this->connection
            ->select()->from('my_table')
            ->having('field', 'value')
            ->andNotHaving(function ($w) {
                $w->having('something', 'different')
                    ->orNotHaving('this', 'crazy');
            })
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectWhereNull()
    {
        $expected = "SELECT * FROM `my_table` WHERE `field` IS NULL";

        $query = $this->connection
            ->select()->from('my_table')
            ->where('field', null)
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectHavingNull()
    {
        $expected = "SELECT * FROM `my_table` HAVING `field` IS NULL";

        $query = $this->connection
            ->select()->from('my_table')
            ->having('field', null)
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectWhereNotNull()
    {
        $expected = "SELECT * FROM `my_table` WHERE `field` IS NOT NULL";

        $query = $this->connection
            ->select()->from('my_table')
            ->where('field', '!=', null)
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectHavingNotNull()
    {
        $expected = "SELECT * FROM `my_table` HAVING `field` IS NOT NULL";

        $query = $this->connection
            ->select()->from('my_table')
            ->having('field', '!=', null)
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectHavingOr()
    {
        $expected = "SELECT * FROM `my_table` HAVING `field` = 'value' OR `other` != 'other value'";

        $query = $this->connection
            ->select()->from('my_table')
            ->having('field', 'value')
            ->orHaving('other', '!=', 'other value')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectWhereOr()
    {
        $expected = "SELECT * FROM `my_table` WHERE `field` = 'value' OR `other` != 'other value'";

        $query = $this->connection
            ->select()->from('my_table')
            ->where('field', 'value')
            ->orWhere('other', '!=', 'other value')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectHavingAnd()
    {
        $expected = "SELECT * FROM `my_table` HAVING `field` = 'value' AND `other` != 'other value'";

        $query = $this->connection
            ->select()->from('my_table')
            ->having('field', 'value')
            ->andHaving('other', '!=', 'other value')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectWhereAnd()
    {
        $expected = "SELECT * FROM `my_table` WHERE `field` = 'value' AND `other` != 'other value'";

        $query = $this->connection
            ->select()->from('my_table')
            ->where('field', 'value')
            ->andWhere('other', '!=', 'other value')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectWhereAndGroups()
    {
        $expected = "SELECT * FROM `my_table` WHERE `field` = 'value' AND (`other` != 'other value' OR `field` = 'something') AND (`age` IN (1, 2, 3) OR `age` NOT IN (2, 5, 7))";

        $query = $this->connection
            ->select()->from('my_table')
            ->where('field', 'value')->andWhereOpen()
            ->where('other', '!=', 'other value')
            ->orWhere('field', '=', 'something')->andWhereClose()
            ->andWhere(function ($q) {
                $q->where('age', 'in', [1, 2, 3])
                    ->orWhere('age', 'not in', [2, 5, 7]);
            })
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectHavingAndGroups()
    {
        $expected = "SELECT * FROM `my_table` HAVING `field` = 'value' AND (`other` != 'other value' OR `field` = 'something') AND (`age` IN (1, 2, 3) OR `age` NOT IN (2, 5, 7))";

        $query = $this->connection
            ->select()->from('my_table')
            ->having('field', 'value')->andHavingOpen()
            ->having('other', '!=', 'other value')
            ->orHaving('field', '=', 'something')->andHavingClose()
            ->andHaving(function ($q) {
                $q->having('age', 'in', [1, 2, 3])
                    ->orHaving('age', 'not in', [2, 5, 7]);
            })
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectWhereAndGroup()
    {
        $expected = "SELECT * FROM `my_table` WHERE `field` = 'value' AND (`other` != 'other value' OR `field` = 'something')";

        $query = $this->connection
            ->select()->from('my_table')
            ->where('field', 'value')->andWhereOpen()
            ->where('other', '!=', 'other value')
            ->orWhere('field', '=', 'something')->andWhereClose()
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectHavingAndGroup()
    {
        $expected = "SELECT * FROM `my_table` HAVING `field` = 'value' AND (`other` != 'other value' OR `field` = 'something')";

        $query = $this->connection
            ->select()->from('my_table')
            ->having('field', 'value')->andHavingOpen()
            ->having('other', '!=', 'other value')
            ->orHaving('field', '=', 'something')->andHavingClose()
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectWhereIn()
    {
        $expected = "SELECT * FROM `my_table` WHERE `field` IN (1, 2, 3)";

        $query = $this->connection
            ->select()->from('my_table')
            ->where('field', [1, 2, 3])
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectHavingIn()
    {
        $expected = "SELECT * FROM `my_table` HAVING `field` IN (1, 2, 3)";

        $query = $this->connection
            ->select()->from('my_table')
            ->having('field', [1, 2, 3])
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectWhereNotIn()
    {
        $expected = "SELECT * FROM `my_table` WHERE `field` NOT IN (1, 2, 3)";

        $query = $this->connection
            ->select()->from('my_table')
            ->where('field', 'not in', [1, 2, 3])
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectHavingNotIn()
    {
        $expected = "SELECT * FROM `my_table` HAVING `field` NOT IN (1, 2, 3)";

        $query = $this->connection
            ->select()->from('my_table')
            ->having('field', 'not in', [1, 2, 3])
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectWhereFn()
    {
        $expected = "SELECT * FROM `my_table` WHERE CHAR_LENGTH(`field`) > 2 AND CHAR_LENGTH(`field`) < 20";

        $query = $this->connection
            ->select()->from('my_table')
            ->where(DB::fnc('char_length', 'field'), '>', 2)
            ->where('CHAR_LENGTH("field")', '<', 20)
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectJoin()
    {
        $expected = "SELECT * FROM `my_table` JOIN `other_table` ON (`my_table`.`field` = `other_table`.`field`)";

        $query = $this->connection
            ->select()->from('my_table')
            ->join('other_table')
            ->on('my_table.field', '=', 'other_table.field')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectJoinAnd()
    {
        $expected = "SELECT * FROM `my_table` JOIN `other_table` ON (`my_table`.`field` = `other_table`.`field` AND `my_table`.`other_field` = `other_table`.`other_field`)";

        $query = $this->connection
            ->select()->from('my_table')
            ->join('other_table')
            ->on('my_table.field', '=', 'other_table.field')
            ->andOn('my_table.other_field', 'other_table.other_field')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectJoinOr()
    {
        $expected = "SELECT * FROM `my_table` JOIN `other_table` ON (`my_table`.`field` = `other_table`.`field` OR `my_table`.`other_field` = `other_table`.`other_field`)";

        $query = $this->connection
            ->select()->from('my_table')
            ->join('other_table')
            ->on('my_table.field', '=', 'other_table.field')
            ->orOn('my_table.other_field', 'other_table.other_field')
            ->compile();

        $this->assertEquals($expected, $query);
    }

    public function testBuildSelectBindings()
    {
        $expected = "SELECT * FROM `my_table`";

        $query = $this->connection
            ->select()->from(':table')
            ->bind('table', 'my_table')
            ->compile();

        $this->assertEquals($expected, $query);
    }
}
