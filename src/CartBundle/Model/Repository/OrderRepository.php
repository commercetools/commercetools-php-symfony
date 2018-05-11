<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Core\Request\Orders\OrderByIdGetRequest;
use Commercetools\Core\Request\Orders\OrderCreateFromCartRequest;
use Commercetools\Core\Request\Orders\OrderQueryRequest;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class OrderRepository extends Repository
{
    protected $session;

    const NAME = 'orders';

    /**
     * OrderRepository constructor.
     * @param $enableCache
     * @param CacheItemPoolInterface $cache
     * @param Client $client
     * @param MapperFactory $mapperFactory
     * @param Session $session
     */
    public function __construct(
        $enableCache,
        CacheItemPoolInterface $cache,
        Client $client,
        MapperFactory $mapperFactory,
        Session $session
    ) {
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
        $this->session = $session;
    }

    /**
     * @param $locale
     * @param $customerId
     * @return OrderCollection
     */
    public function getOrders($locale, $customerId)
    {
        $client = $this->getClient();
        $request = OrderQueryRequest::of()->where('customerId = "' . $customerId . '"')->sort('createdAt desc');
        $response = $request->executeWithClient($client);
        $orders = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $orders;
    }

    /**
     * @param $locale
     * @param $orderId
     * @return Order
     */
    public function getOrder($locale, $orderId)
    {
        $client = $this->getClient();
        $request = OrderByIdGetRequest::ofId($orderId);
        $response = $request->executeWithClient($client);
        $order = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $order;
    }

    public function createOrderFromCart($locale, Cart $cart)
    {
        $client = $this->getClient();
        $request = OrderCreateFromCartRequest::ofCartIdAndVersion($cart->getId(), $cart->getVersion());
        $request->setOrderNumber($this->createOrderNumber());
        $response = $request->executeWithClient($client);
        $order = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        $this->session->remove(CartRepository::CART_ID);
        $this->session->remove(CartRepository::CART_ITEM_COUNT);

        return $order;
    }

    protected function createOrderNumber()
    {
        return (string)time();
    }

    public function update()
    {
        return true;
    }
}
