<?php
/**
 */

namespace Commercetools\Symfony\CtpBundle\Model;

use Commercetools\Core\Request\Query\MultiParameter;
use Commercetools\Core\Request\Query\Parameter;

class QueryParams
{
    /**
     * @var array
     */
    private $params = [];

    /**
     * @param $paramName
     * @param $paramValue
     * @return $this
     */
    public function add($paramName, $paramValue)
    {
        $this->params[] = new MultiParameter($paramName, $paramValue);

        return $this;
    }

    /**
     * @param Parameter $param
     * @return $this
     */
    public function addParamObject(Parameter $param)
    {
        $this->params[] = $param;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return QueryParams
     */
    public static function of()
    {
        return new static();
    }
}
