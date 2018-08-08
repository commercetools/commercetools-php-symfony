<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Model;


use Commercetools\Core\Model\Order\Order;
use Symfony\Component\Yaml\Yaml;

class OrderWrapper extends Order
{
    public function getStateKey()
    {
        $stateReference = parent::getState();

        if (is_null($stateReference)) {
            return 'created';
        }

        $obj = $stateReference->getobj();

        return $obj->getKey();
    }
}
