<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Model\Repository;


use Commercetools\Core\Request\Carts\Command\CartSetBillingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingAddressAction;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\CartDraft;
use Commercetools\Core\Model\Cart\LineItemDraft;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\ShippingMethod\ShippingMethodCollection;
use Commercetools\Core\Request\Carts\CartByIdGetRequest;
use Commercetools\Core\Request\Carts\CartCreateRequest;
use Commercetools\Core\Request\Carts\CartUpdateRequest;
use Commercetools\Core\Request\Carts\Command\CartAddLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartChangeLineItemQuantityAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveLineItemAction;
use Commercetools\Symfony\CtpBundle\Service\ClientFactory;


class CartRepository extends Repository
{
    protected $shippingMethodRepository;

    const NAME = 'cart';

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
        ShippingMethodRepository $shippingMethodRepository
    ) {
        parent::__construct($enableCache, $cache, $clientFactory);
        $this->shippingMethodRepository = $shippingMethodRepository;
    }


    public function getCart($locale, $cartId = null)
    {
        $cart = null;
        $client = $this->getClient($locale);
        if ($cartId) {
            $cartRequest = CartByIdGetRequest::ofId($cartId);
            $cartResponse = $cartRequest->executeWithClient($client);
            $cart = $cartRequest->mapResponse($cartResponse);
        }

        if (is_null($cart)) {
            $cart = Cart::of($client->getConfig()->getContext());
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

        $billingAddressAction = CartSetBillingAddressAction::of();
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

        return $cart;
    }
}