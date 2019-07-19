<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Cart\MyCartDraft;
use Commercetools\Core\Model\Cart\MyLineItemDraftCollection;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Symfony\CtpBundle\Model\MeRepository;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Address;

class MeCartRepository extends MeRepository
{
    const CART_ID = 'cart.id';
    const CART_ITEM_COUNT = 'cart.itemCount';

    /**
     * @param string $locale
     * @return mixed
     */
    public function getActiveCart($locale)
    {
        $cartRequest = RequestBuilder::of()->me()->carts()->getActiveCart();

        return $this->executeRequest($cartRequest, $locale);
    }

    /**
     * @param string $locale
     * @param string $currency
     * @param Location $location
     * @param MyLineItemDraftCollection|null $lineItemDraftCollection
     * @return Cart|null
     */
    public function createCart($locale, $currency, Location $location, MyLineItemDraftCollection $lineItemDraftCollection = null)
    {
        $cartDraft = MyCartDraft::ofCurrency($currency)->setCountry($location->getCountry())
            ->setShippingAddress(Address::of()->setCountry($location->getCountry()));

        if (!is_null($lineItemDraftCollection)) {
            $cartDraft->setLineItems($lineItemDraftCollection);
        }

        $request = RequestBuilder::of()->me()->carts()->create($cartDraft);

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param Cart $cart
     * @param array $actions
     * @param QueryParams|null $params
     * @return Cart
     */
    public function update(Cart $cart, array $actions, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->me()->carts()->update($cart)->setActions($actions);

        if (!is_null($params)) {
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        return $this->executeRequest($request);
    }

    /**
     * @param Cart $cart
     * @return Cart
     */
    public function delete(Cart $cart)
    {
        $request = RequestBuilder::of()->me()->carts()->delete($cart);

        return $this->executeRequest($request);
    }
}
