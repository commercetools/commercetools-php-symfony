<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 16/04/2018
 * Time: 11:15
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Model\ShippingMethod\ShippingMethodReference;
use Commercetools\Core\Request\Carts\Command\CartSetBillingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingAddressAction;
use Commercetools\Core\Model\Cart\CartState;
use Commercetools\Core\Request\Carts\CartQueryRequest;
use Commercetools\Core\Request\Carts\Command\CartSetShippingMethodAction;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\CartDraft;
use Commercetools\Core\Model\Cart\LineItemDraft;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\ShippingMethod\ShippingMethodCollection;
use Commercetools\Core\Request\Carts\CartCreateRequest;
use Commercetools\Core\Request\Carts\CartUpdateRequest;
use Commercetools\Core\Request\Carts\Command\CartAddLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartChangeLineItemQuantityAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveLineItemAction;
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
        $client = $this->getClient();
        if ($cartId) {
            $cartRequest = CartQueryRequest::of();
            $predicate = 'id = "' . $cartId . '" and cartState = "' . CartState::ACTIVE . '"';
            if (!is_null($customerId)) {
                $predicate .= ' and customerId="' . $customerId . '"';
            }
            $cartRequest->where($predicate)->limit(1);
            $cartResponse = $cartRequest->executeWithClient($client);
            $carts = $cartRequest->mapFromResponse(
                $cartResponse,
                $this->getMapper($locale)
            );
            if (!is_null($carts)) {
                $cart = $carts->current();
                if ($cart->getCustomerId() !== $customerId) {
                    throw new \InvalidArgumentException();
                }
            }
        }

        if (is_null($cart)) {
            $cart = Cart::of($client->getConfig()->getContext());
            $this->session->remove(self::CART_ID);
            $this->session->remove(self::CART_ITEM_COUNT);
        } else {
            $this->session->set(self::CART_ID, $cart->getId());
            $this->session->set(self::CART_ITEM_COUNT, $cart->getLineItemCount());
        }

        return $cart;
    }

    /**
     * @param $cartId
     * @param $productId
     * @param $variantId
     * @param $quantity
     * @return Cart|null
     */
    public function addLineItem($locale, $cartId, $productId, $variantId, $quantity, $currency, $country, $customerId = null)
    {
        $cart = null;
        if (!is_null($cartId)) {
            $cart = $this->getCart($locale, $cartId, $customerId);
        }

        if (is_null($cart)) {
            $lineItems = LineItemDraftCollection::of()->add(
                LineItemDraft::of()->setProductId($productId)
                    ->setVariantId($variantId)
                    ->setQuantity($quantity)
            );
            $cart = $this->createCart($locale, $currency, $country, $lineItems, $customerId);
        } else {
            $client = $this->getClient();

            $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());
            $cartUpdateRequest->addAction(
                CartAddLineItemAction::ofProductIdVariantIdAndQuantity($productId, $variantId, $quantity)
            );
            $cartResponse = $cartUpdateRequest->executeWithClient($client);
            if ($cartResponse->isError()) {
                throw new \InvalidArgumentException();
            }
            $cart = $cartUpdateRequest->mapFromResponse(
                $cartResponse,
                $this->getMapper($locale)
            );
            $this->session->set(self::CART_ITEM_COUNT, $cart->getLineItemCount());
        }

        return $cart;
    }

    public function deleteLineItem($locale, $cartId, $lineItemId, $customerId = null)
    {
        $client = $this->getClient();
        $cart = $this->getCart($locale, $cartId, $customerId);

        $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());
        $cartUpdateRequest->addAction(
            CartRemoveLineItemAction::ofLineItemId($lineItemId)
        );
        $cartResponse = $cartUpdateRequest->executeWithClient($client);
        $cart = $cartUpdateRequest->mapFromResponse(
            $cartResponse,
            $this->getMapper($locale)
        );
        $this->session->set(self::CART_ITEM_COUNT, $cart->getLineItemCount());

        return $cart;
    }

    public function changeLineItemQuantity($locale, $cartId, $lineItemId, $quantity, $customerId = null)
    {
        $cart = $this->getCart($locale, $cartId, $customerId);
        $client = $this->getClient();
        $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());
        $cartUpdateRequest->addAction(
            CartChangeLineItemQuantityAction::ofLineItemIdAndQuantity($lineItemId, $quantity)
        );
        $cartResponse = $cartUpdateRequest->executeWithClient($client);
        $cart = $cartUpdateRequest->mapFromResponse(
            $cartResponse,
            $this->getMapper($locale)
        );
        $this->session->set(self::CART_ITEM_COUNT, $cart->getLineItemCount());

        return $cart;
    }

    public function setAddresses($locale, $cartId, Address $shippingAddress, Address $billingAddress = null, $customerId = null)
    {
        $cart = $this->getCart($locale, $cartId, $customerId);
        $client = $this->getClient();
        $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());

        $cartUpdateRequest->addAction(CartSetShippingAddressAction::of()->setAddress($shippingAddress));

        $billingAddressAction = CartSetBillingAddressAction::of();
        if (!is_null($billingAddress)) {
            $billingAddressAction->setAddress($billingAddress);
            $cartUpdateRequest->addAction($billingAddressAction);
        }

        $cartResponse = $cartUpdateRequest->executeWithClient($client);
        $cart = $cartUpdateRequest->mapFromResponse(
            $cartResponse,
            $this->getMapper($locale)
        );

        return $cart;
    }

    public function setShippingMethod($locale, $cartId, ShippingMethodReference $shippingMethod, $customerId = null)
    {
        $cart = $this->getCart($locale, $cartId, $customerId);
        $client = $this->getClient();
        $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());

        $shippingMethodAction = CartSetShippingMethodAction::of()->setShippingMethod($shippingMethod);
        $cartUpdateRequest->addAction($shippingMethodAction);

        $cartResponse = $cartUpdateRequest->executeWithClient($client);
        $cart = $cartUpdateRequest->mapFromResponse(
            $cartResponse,
            $this->getMapper($locale)
        );

        return $cart;
    }

    /**
     * @param $cartId
     * @param $currency
     * @param $country
     * @return Cart|null
     */
    public function createCart($locale, $currency, $country, LineItemDraftCollection $lineItems, $customerId = null)
    {
        $client = $this->getClient();
        $shippingMethodResponse = $this->shippingMethodRepository->getByCountryAndCurrency($locale, $country, $currency);
        $cartDraft = CartDraft::ofCurrency($currency)->setCountry($country)
            ->setShippingAddress(Address::of()->setCountry($country))
            ->setLineItems($lineItems);
        if (!is_null($customerId)) {
            $cartDraft->setCustomerId($customerId);
        }
        if (!$shippingMethodResponse->isError()) {
            /**
             * @var ShippingMethodCollection $shippingMethods
             */
            $shippingMethods = $shippingMethodResponse->toObject();
            $cartDraft->setShippingMethod($shippingMethods->current()->getReference());
        }
        $cartCreateRequest = CartCreateRequest::ofDraft($cartDraft);
        $cartResponse = $cartCreateRequest->executeWithClient($client);
        $cart = $cartCreateRequest->mapFromResponse(
            $cartResponse,
            $this->getMapper($locale)
        );
        $this->session->set(self::CART_ID, $cart->getId());
        $this->session->set(self::CART_ITEM_COUNT, $cart->getLineItemCount());

        return $cart;
    }
}
