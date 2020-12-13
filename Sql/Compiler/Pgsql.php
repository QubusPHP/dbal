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
use function implode;

class Pgsql extends Sql
{
    /**
     * Compiles a PGSQL concatination.
     *
     * @param   object  $value  Fn object
     * @return  string  compiles concat
     */
    protected function compileFncConcat($value)
    {
        $values = $value->getParams();
        $quoteFnc = $value->quoteAs() === 'identifier' ? 'quoteIdentifier' : 'quote';

        return implode(' || ', array_map([$this, $quoteFnc], $value->getParams()));
    }

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
                $data['type'] = 'serial';
                $data['incremental'] = false;
            }

            return $data;
        }, $fields);
    }

    /**
     * Compiles the ENGINE statement
     *
     * @return  string  compiled ENGINE statement
     */
    protected function compilePartEngine()
    {
        return '';
    }
}
