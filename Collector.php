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

namespace Qubus\Dbal;

use function get_object_vars;

class Collector extends Base
{
    /** @var  array|string  $table  tables to use */
    public $table = [];

    /**
     * Get the query contents
     *
     * @return  array  query contents
     */
    public function getContents()
    {
        $return = [];
        $vars = get_object_vars($this);

        foreach ($vars as $k => $v) {
            if ($k[0] !== '_') {
                $return[$k] = $v;
            }
        }

        return $return;
    }
}
