<?php

/**
 * Qubus\Dbal
 *
 * @link       https://github.com/QubusPHP/dbal
 * @copyright  2020
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Dbal;

use function get_object_vars;

class Collector extends Base
{
    /** @var  array|string  $table  tables to use */
    public string|array $table = [];

    /**
     * Get the query contents
     *
     * @return mixed query contents.
     */
    public function getContents(): mixed
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
