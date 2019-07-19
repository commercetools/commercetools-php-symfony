<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Symfony\CtpBundle\Model\MeRepository;

class MeOrderRepository extends MeRepository
{
    /**
     * @param string $locale
     * @param string $orderId
     * @return Order
     */
    public function getOrderById($locale, $orderId)
    {
        $request = RequestBuilder::of()->me()->orders()->getById($orderId);

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param string $locale
     * @return OrderCollection
     */
    public function getOrders($locale)
    {
        $request = RequestBuilder::of()->me()->orders()->query();

        $request->sort('createdAt desc');

        return $this->executeRequest($request, $locale);
    }


    /**
     * @param string $locale
     * @param string $paymentId
     * @return OrderCollection
     */
    public function getOrdersFromPayment($locale, $paymentId)
    {
        $request = RequestBuilder::of()->me()->orders()->query();

        $predicate = 'paymentInfo(payments(id = "' . $paymentId . '"))';

        $request->where($predicate);

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param string $locale
     * @param Cart $cart
     * @return Order
     */
    public function createOrderFromCart($locale, Cart $cart)
    {
        $request = RequestBuilder::of()->me()->orders()->createFromCart($cart);

        return $this->executeRequest($request, $locale);
    }
}
