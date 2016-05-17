<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Model\Repository;


use Commercetools\Core\Model\ShippingMethod\ShippingMethodReference;
use Commercetools\Core\Request\Carts\Command\CartSetBillingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingAddressAction;
use Commercetools\Core\Model\Cart\CartState;
use Commercetools\Core\Request\Carts\CartQueryRequest;
use Commercetools\Core\Request\Carts\Command\CartSetShippingMethodAction;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Core\Cache\CacheAdapterInterface;
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
use Commercetools\Symfony\CtpBundle\Service\ClientFactory;
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
     * @param CacheAdapterInterface $cache
     * @param ClientFactory $clientFactory
     * @param ShippingMethodRepository $shippingMethodRepository
     */
    public function __construct(
        $enableCache,
        CacheAdapterInterface $cache,
        ClientFactory $clientFactory,
        ShippingMethodRepository $shippingMethodRepository,
        Session $session
    ) {
        parent::__construct($enableCache, $cache, $clientFactory);
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->session = $session;
    }

    public function getCart($locale, $cartId = null, $customerId = null)
    {
        $cart = null;
        $client = $this->getClient($locale);
        if ($cartId) {
            $cartRequest = CartQueryRequest::of();
            $predicate = 'id = "' . $cartId . '" and cartState = "' . CartState::ACTIVE . '"';
            if (!is_null($customerId)) {
                $predicate .= ' and customerId="' . $customerId . '"';
            }
            $cartRequest->where($predicate)->limit(1);
            $cartResponse = $cartRequest->executeWithClient($client);
            $carts = $cartRequest->mapResponse($cartResponse);
            if (!is_null($carts)) {
                $cart = $carts->current();
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
    public function addLineItem($locale, $cartId, $productId, $variantId, $quantity, $currency, $country)
    {
        $cart = null;
        if (!is_null($cartId)) {
            $cart = $this->getCart($locale, $cartId);
        }

        if (is_null($cart)) {
            $lineItems = LineItemDraftCollection::of()->add(
                LineItemDraft::of()->setProductId($productId)
                    ->setVariantId($variantId)
                    ->setQuantity($quantity)
            );
            $cart = $this->createCart($locale, $currency, $country, $lineItems);
        } else {
            $client = $this->getClient($locale);

            $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());
            $cartUpdateRequest->addAction(
                CartAddLineItemAction::ofProductIdVariantIdAndQuantity($productId, $variantId, $quantity)
            );
            $cartResponse = $cartUpdateRequest->executeWithClient($client);
            if ($cartResponse->isError()) {
                throw new \InvalidArgumentException();
            }
            $cart = $cartUpdateRequest->mapResponse($cartResponse);
            $this->session->set(self::CART_ITEM_COUNT, $cart->getLineItemCount());
        }

        return $cart;
    }

    public function deleteLineItem($locale, $cartId, $lineItemId)
    {
        $client = $this->getClient($locale);
        $cart = $this->getCart($locale, $cartId);

        $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());
        $cartUpdateRequest->addAction(
            CartRemoveLineItemAction::ofLineItemId($lineItemId)
        );
        $cartResponse = $cartUpdateRequest->executeWithClient($client);
        $cart = $cartUpdateRequest->mapResponse($cartResponse);
        $this->session->set(self::CART_ITEM_COUNT, $cart->getLineItemCount());

        return $cart;
    }

    public function changeLineItemQuantity($locale, $cartId, $lineItemId, $quantity)
    {
        $cart = $this->getCart($locale, $cartId);
        $client = $this->getClient($locale);
        $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());
        $cartUpdateRequest->addAction(
            CartChangeLineItemQuantityAction::ofLineItemIdAndQuantity($lineItemId, $quantity)
        );
        $cartResponse = $cartUpdateRequest->executeWithClient($client);
        $cart = $cartUpdateRequest->mapResponse($cartResponse);
        $this->session->set(self::CART_ITEM_COUNT, $cart->getLineItemCount());

        return $cart;
    }

    public function setAddresses($locale, $cartId, Address $shippingAddress, Address $billingAddress = null)
    {
        $cart = $this->getCart($locale, $cartId);
        $client = $this->getClient($locale);
        $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());

        $billingAddressAction = CartSetBillingAddressAction::of();
        if (!is_null($billingAddress)) {
            $billingAddressAction->setAddress($billingAddress);
        }
        $cartUpdateRequest->addAction(CartSetShippingAddressAction::of()->setAddress($shippingAddress))
            ->addAction($billingAddressAction);

        $cartResponse = $cartUpdateRequest->executeWithClient($client);
        $cart = $cartUpdateRequest->mapResponse($cartResponse);

        return $cart;
    }

    public function setShippingMethod($locale, $cartId, ShippingMethodReference $shippingMethod)
    {
        $cart = $this->getCart($locale, $cartId);
        $client = $this->getClient($locale);
        $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());

        $shippingMethodAction = CartSetShippingMethodAction::of()->setShippingMethod($shippingMethod);
        $cartUpdateRequest->addAction($shippingMethodAction);

        $cartResponse = $cartUpdateRequest->executeWithClient($client);
        $cart = $cartUpdateRequest->mapResponse($cartResponse);

        return $cart;
    }

    /**
     * @param $cartId
     * @param $currency
     * @param $country
     * @return Cart|null
     */
    public function createCart($locale, $currency, $country, LineItemDraftCollection $lineItems)
    {
        $client = $this->getClient($locale);
        $shippingMethodResponse = $this->shippingMethodRepository->getByCountryAndCurrency($locale, $country, $currency);
        $cartDraft = CartDraft::ofCurrency($currency)->setCountry($country)
            ->setShippingAddress(Address::of()->setCountry($country))
            ->setLineItems($lineItems);
        if (!$shippingMethodResponse->isError()) {
            /**
             * @var ShippingMethodCollection $shippingMethods
             */
            $shippingMethods = $shippingMethodResponse->toObject();
            $cartDraft->setShippingMethod($shippingMethods->current()->getReference());
        }
        $cartCreateRequest = CartCreateRequest::ofDraft($cartDraft);
        $cartResponse = $cartCreateRequest->executeWithClient($client);
        $cart = $cartCreateRequest->mapResponse($cartResponse);
        $this->session->set(self::CART_ID, $cart->getId());
        $this->session->set(self::CART_ITEM_COUNT, $cart->getLineItemCount());

        return $cart;
    }
}
