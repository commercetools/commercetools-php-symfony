<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Core\Model\Cart\CartState;
use Commercetools\Core\Request\ClientRequestInterface;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\CartDraft;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Request\Carts\CartCreateRequest;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Session\Session;


class CartRepository extends Repository
{
    protected $shippingMethodRepository;
    protected $session;

    const NAME = 'cart';
    const CART_ID = 'cart.id';
    const CART_ITEM_COUNT = 'cart.itemCount';

    /**
     * CartRepository constructor
     * @param $enableCache
     * @param CacheItemPoolInterface $cache
     * @param Client $client
     * @param MapperFactory $mapperFactory
     * @param ShippingMethodRepository $shippingMethodRepository
     * @param Session $session
     */
    public function __construct(
        $enableCache,
        CacheItemPoolInterface $cache,
        Client $client,
        MapperFactory $mapperFactory,
        ShippingMethodRepository $shippingMethodRepository,
        Session $session
    ) {
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->session = $session;
    }

    public function getCart($locale, $cartId = null, $customerId = null)
    {
        $cart = null;

        if ($cartId) {
            $cartRequest = RequestBuilder::of()->carts()->query();

            $predicate = 'id = "' . $cartId . '" and cartState = "' . CartState::ACTIVE . '"';
            if (!is_null($customerId)) {
                $predicate .= ' and customerId="' . $customerId . '"';
            }

            $cartRequest->where($predicate)->limit(1);

            $carts = $this->executeRequest($cartRequest, $locale);
            $cart = $carts->current();

            if (!is_null($cart)) {
                if ($cart->getCustomerId() !== $customerId) {
                    throw new \InvalidArgumentException();
                }
            }
        }

        if (is_null($cart)) {
            $cart = Cart::of($this->getClient()->getConfig()->getContext());
            $this->session->remove(self::CART_ID);
            $this->session->remove(self::CART_ITEM_COUNT);
        } else {
            $this->session->set(self::CART_ID, $cart->getId());
            $this->session->set(self::CART_ITEM_COUNT, $cart->getLineItemCount());
        }

        return $cart;
    }

    /**
     * @param $locale
     * @param $currency
     * @param Location $location
     * @param LineItemDraftCollection $lineItems
     * @param $customerId
     * @param $anonymousId
     * @return Cart|null
     */
    public function createCart($locale, $currency, Location $location, LineItemDraftCollection $lineItems, $customerId = null, $anonymousId = null)
    {
        $shippingMethods = $this->shippingMethodRepository->getShippingMethodsByLocation($locale, $location, $currency);

        $cartDraft = CartDraft::ofCurrency($currency)->setCountry($location->getCountry())
            ->setShippingAddress(Address::of()->setCountry($location->getCountry()))
            ->setLineItems($lineItems);

        if (!is_null($anonymousId)) {
            $cartDraft->setAnonymousId($anonymousId);
        }

        if (!is_null($customerId)) {
            $cartDraft->setCustomerId($customerId);
        }

        $cartDraft->setShippingMethod($shippingMethods->current()->getReference());

        $cartCreateRequest = RequestBuilder::of()->carts()->create($cartDraft);
        $cart = $this->executeRequest($cartCreateRequest, $locale);

        $this->session->set(self::CART_ID, $cart->getId());
        $this->session->set(self::CART_ITEM_COUNT, $cart->getLineItemCount());

        return $cart;
    }

    public function update(Cart $cart, array $actions, QueryParams $params = null)
    {
        $client = $this->getClient();
        $request = RequestBuilder::of()->carts()->update($cart)->setActions($actions);

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        $response = $request->executeWithClient($client);
        $cart = $request->mapFromResponse(
            $response
        );

        return $cart;
    }
}
