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

use function is_array;

class Fnc
{
    /** @var array  $params  function params */
    protected array $params = [];

    /** @var string  $fnc  function name */
    protected ?string $fnc = null;

    /** @var  string  $quoteAs  quote as value or as identifier */
    protected string $quoteAs = 'identifier';

    /**
     * Constructor, stores function name and ensures $params is an array.
     *
     * @param  string  $fnc      function name
     * @param  mixed   $params  function params
     */
    public function __construct(?string $fnc, $params = [])
    {
        is_array($params) || $params = [$params];

        $this->fnc = $fnc;
        $this->params = $params;
    }

    /**
     * Sets the default quote type to value.
     *
     * @return  object  $this
     */
    public function quoteAsValue()
    {
        $this->quoteAs = 'value';

        return $this;
    }

    /**
     * Sets the default quote type to identifier.
     *
     * @return  object  $this
     */
    public function quoteAsIdentifier()
    {
        $this->quoteAs = 'identifier';

        return $this;
    }

    /**
     * Returns default the quoting type.
     *
     * @return  string  quoteation type
     */
    public function quoteAs()
    {
        return $this->quoteAs;
    }

    /**
     * Retrieve the function name.
     *
     * @return  string  function name
     */
    public function getFnc()
    {
        return $this->fnc;
    }

    /**
     * Retrieve the function params.
     *
     * @return  array  function params
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Wrap the function in an alias.
     *
     * @param   string  alias identifier
     * @return  array   alias array
     */
    public function aliasTo($name)
    {
        return [$this, $name];
    }
}
