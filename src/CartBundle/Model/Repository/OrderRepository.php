<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;

class OrderRepository extends Repository
{
    const NAME = 'orders';

    /**
     * @param $locale
     * @param $customerId
     * @return OrderCollection
     */
    public function getOrders($locale, $customerId)
    {
        $request = RequestBuilder::of()->orders()->query()->where('customerId = "' . $customerId . '"')->sort('createdAt desc');

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param $locale
     * @param $orderId
     * @return Order
     */
    public function getOrder($locale, $orderId)
    {
        $request = RequestBuilder::of()->orders()->getById($orderId);

        return $this->executeRequest($request, $locale);
    }

    public function createOrderFromCart($locale, Cart $cart)
    {
        $request = RequestBuilder::of()->orders()->createFromCart($cart);

        $order = $this->executeRequest($request, $locale);

        $this->session->remove(CartRepository::CART_ID);
        $this->session->remove(CartRepository::CART_ITEM_COUNT);

        return $order;
    }

    protected function createOrderNumber()
    {
        return (string)time();
    }

    public function update(Order $order, array $actions, QueryParams $params = null)
    {
        $client = $this->getClient();
        $request = RequestBuilder::of()->orders()->update($order)->setActions($actions);

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        $response = $request->executeWithClient($client);
        $order = $request->mapFromResponse($response);

        return $order;

    }
}
