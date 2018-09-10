<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;

class OrderRepository extends Repository
{
    /**
     * @param $locale
     * @param $customerId
     * @return OrderCollection
     */
    public function getOrders($locale, $customerId, $anonymousId = null)
    {
        $request = RequestBuilder::of()->orders()->query();

        if (!is_null($customerId)) {
            $request->where('customerId = "' . $customerId . '"');
        } elseif (!is_null($anonymousId)) {
            $request->where('anonymousId = "' . $anonymousId . '"');
        }
        $request->sort('createdAt desc');

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param $locale
     * @param $orderId
     * @return OrderCollection
     */
    public function getOrder($locale, $orderId, $customerId = null, $anonymousId = null)
    {
        $request = RequestBuilder::of()->orders()->query();

        if (!is_null($customerId)) {
            $request->where('id = "' . $orderId . '" and customerId = "' . $customerId . '"');
        } else if (!is_null($anonymousId)) {
            $request->where('id = "' . $orderId . '" and anonymousId = "' . $anonymousId . '"');
        } else {
            $request->where('id = "' . $orderId . '"');
        }

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param $locale
     * @param Cart $cart
     * @param StateReference $stateReference
     * @return Order
     */
    public function createOrderFromCart($locale, Cart $cart, StateReference $stateReference)
    {
        $request = RequestBuilder::of()->orders()
            ->createFromCart($cart)
            ->setOrderNumber($this->createOrderNumber())
            ->setState($stateReference);

        $order = $this->executeRequest($request, $locale);

        return $order;
    }

    /**
     * @return string
     */
    protected function createOrderNumber()
    {
        return (string)time();
    }

    /**
     * @param Order $order
     * @param array $actions
     * @param QueryParams|null $params
     * @return Order
     */
    public function update(Order $order, array $actions, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->orders()->update($order)->setActions($actions);

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        return $this->executeRequest($request);
    }
}
