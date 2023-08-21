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

namespace Qubus\Dbal\Collector;

use Qubus\Dbal\DB;

class Delete extends Where
{
    protected ?string $type = DB::DELETE;

    public function __construct(string|array $table = null)
    {
        $table && $this->table = $table;
    }

    /**
     * Sets the table to update
     *
     * @param string|array $table table to update
     * @return  object  $this
     */
    public function from(string|array $table): object
    {
        $this->table = $table;

        return $this;
    }
}
