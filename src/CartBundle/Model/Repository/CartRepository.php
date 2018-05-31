<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Core\Model\Cart\CartState;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\CartDraft;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Psr\Cache\CacheItemPoolInterface;


class CartRepository extends Repository
{
    protected $shippingMethodRepository;

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
     */
    public function __construct(
        $enableCache,
        CacheItemPoolInterface $cache,
        Client $client,
        MapperFactory $mapperFactory,
        ShippingMethodRepository $shippingMethodRepository
    ) {
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
        $this->shippingMethodRepository = $shippingMethodRepository;
    }

    // TODO: check/fix
    public function getCart($locale, $cartId = null, $customerId = null, $anonymousId = null)
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
                if ($cart->getCustomerId() !== $customerId && !is_null($customerId)) { // accepts null customer id (?)
                    throw new \InvalidArgumentException();
                }
            }
        } elseif (!is_null($anonymousId)) {
            $cartRequest = RequestBuilder::of()->carts()->query();

            $predicate = 'cartState = "' . CartState::ACTIVE . '" and anonymousId="' . $anonymousId . '"';

            $cartRequest->where($predicate)->limit(1);

            $carts = $this->executeRequest($cartRequest, $locale);
            $cart = $carts->current();
        }

        return $cart;
    }

    /**
     * @param $locale
     * @param $currency
     * @param Location $location
     * @param LineItemDraftCollection $lineItemDraftCollection
     * @param $customerId
     * @param $anonymousId
     * @return Cart|null
     */
    public function createCart($locale, $currency, Location $location, LineItemDraftCollection $lineItemDraftCollection, $customerId = null, $anonymousId = null)
    {
        $shippingMethods = $this->shippingMethodRepository->getShippingMethodsByLocation($locale, $location, $currency);

        $cartDraft = CartDraft::ofCurrency($currency)->setCountry($location->getCountry())
            ->setShippingAddress(Address::of()->setCountry($location->getCountry()))
            ->setLineItems($lineItemDraftCollection);

        if (!is_null($customerId)) {
            $cartDraft->setCustomerId($customerId);
        } else if (!is_null($anonymousId)) {
            $cartDraft->setAnonymousId($anonymousId);
        }

        $cartDraft->setShippingMethod($shippingMethods->current()->getReference());

        $request = RequestBuilder::of()->carts()->create($cartDraft);
        $cart = $this->executeRequest($request, $locale);

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
