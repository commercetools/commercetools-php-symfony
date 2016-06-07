<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Model\Repository;


use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Core\Request\Orders\OrderByIdGetRequest;
use Commercetools\Core\Request\Orders\OrderCreateFromCartRequest;
use Commercetools\Core\Request\Orders\OrderQueryRequest;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\CtpBundle\Service\ClientFactory;
use Symfony\Component\HttpFoundation\Session\Session;

class OrderRepository extends Repository
{
    protected $session;

    const NAME = 'orders';

    /**
     * CartRepository constructor
     * @param $enableCache
     * @param CacheAdapterInterface $cache
     * @param ClientFactory $clientFactory
     * @param ShippingMethodRepository $shippingMethodRepository
     */
//    public function __construct(
//        $enableCache,
//        CacheAdapterInterface $cache,
//        ClientFactory $clientFactory,
//        Session $session
//    ) {
//        parent::__construct($enableCache, $cache, $clientFactory);
//        $this->session = $session;
//    }

    /**
     * @param $locale
     * @param $customerId
     * @return OrderCollection
     */
    public function getOrders($locale, $customerId)
    {
        $client = $this->getClient($locale);
        $request = OrderQueryRequest::of()->where('customerId = "' . $customerId . '"');
        $response = $request->executeWithClient($client);
        $orders = $request->mapResponse($response);

        return $orders;
    }

    /**
     * @param $locale
     * @param $orderId
     * @return Order
     */
    public function getOrder($locale, $orderId)
    {
        $client = $this->getClient($locale);
        $request = OrderByIdGetRequest::ofId($orderId);
        $response = $request->executeWithClient($client);
        $order = $request->mapResponse($response);

        return $order;
    }

    public function createOrderFromCart($locale, Cart $cart)
    {
        $client = $this->getClient($locale);
        $request = OrderCreateFromCartRequest::ofCartIdAndVersion($cart->getId(), $cart->getVersion());
        $request->setOrderNumber($this->createOrderNumber());
        $response = $request->executeWithClient($client);
        $order = $request->mapResponse($response);

        $this->session->remove(CartRepository::CART_ID);
        $this->session->remove(CartRepository::CART_ITEM_COUNT);

        return $order;
    }

    protected function createOrderNumber()
    {
        return (string)time();
    }
}