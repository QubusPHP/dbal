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

namespace Qubus\Dbal\Sql\Compiler;

use Qubus\Dbal\Sql\Sql;

use function array_map;
use function implode;

class Pgsql extends Sql
{
    /**
     * Compiles a PGSQL concatenation.
     *
     * @param mixed $value  Fn object.
     * @return string compiles concat.
     */
    protected function compileFncConcat(mixed $value): string
    {
        $values = $value->getParams();
        $quoteFnc = $value->quoteAs() === 'identifier' ? 'quoteIdentifier' : 'quote';

        return implode(separator: ' || ', array: array_map(callback: [$this, $quoteFnc], array: $value->getParams()));
    }

    /**
     * Prepares the fields for rendering.
     *
     * @param array $fields  array with field objects
     * @return  array  array with prepped field objects
     */
    protected function prepareFields(array $fields): array
    {
        return array_map(callback: function ($field) {
            $data = $field->getContents();

            if ($data['incremental']) {
                $data['type'] = 'serial';
                $data['incremental'] = false;
            }

            return $data;
        }, array: $fields);
    }

    /**
     * Compiles the ENGINE statement
     *
     * @return  string  compiled ENGINE statement
     */
    protected function compilePartEngine(): string
    {
        return '';
    }
}
