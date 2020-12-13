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

class Expression
{
    /** @var  mixed  $value  the raw expression */
    protected $value;

    /**
     * @param  mixed  expression value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Returns the expression value.
     *
     * @param   object  $connection  connection
     * @return  mixed   the expression value
     */
    public function handle()
    {
        return $this->value;
    }
}
