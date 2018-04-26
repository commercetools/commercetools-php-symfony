<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 25/04/2018
 * Time: 16:34
 */

namespace Commercetools\Symfony\CtpBundle\Model;

use Commercetools\Core\Request\Query\MultiParameter;
use Commercetools\Core\Request\Query\Parameter;

class QueryParams {
    private $params = [];

    public function add($paramName, $paramValue)
    {
        $this->params[] = new MultiParameter($paramName, $paramValue);
    }

    public function addParamObject(Parameter $param)
    {
        $this->params[] = $param;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
