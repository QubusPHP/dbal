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

namespace Qubus\Dbal\Sql\Compiler;

use Qubus\Dbal\Sql\Sql;

use function array_map;

class Sqlite extends Sql
{
    /**
     * Prepares the fields for rendering.
     *
     * @param   array  $fields  array with field objects
     * @return  array  array with prepped field objects
     */
    protected function prepareFields($fields)
    {
        return array_map(function ($field) {
            $data = $field->getContents();

            if ($data['incremental']) {
                $data['type'] = 'integer';
                $data['primary'] = true;
                $data['incremental'] = false;
            }

            return $data;
        }, $fields);
    }
}
