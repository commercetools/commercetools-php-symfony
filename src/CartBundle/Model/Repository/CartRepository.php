<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Core\Model\Cart\CartState;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\CartDraft;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Common\Address;
use Symfony\Component\Security\Core\User\UserInterface;


class CartRepository extends Repository
{
    const CART_ID = 'cart.id';
    const CART_ITEM_COUNT = 'cart.itemCount';

    /**
     * @param $locale
     * @param null $cartId
     * @param UserInterface|null $user
     * @param null $anonymousId
     * @return mixed
     */
    public function getCart($locale, $cartId = null, UserInterface $user = null, $anonymousId = null)
    {
        $cartRequest = RequestBuilder::of()->carts()->query();

        $predicate = 'cartState = "' . CartState::ACTIVE . '"';

        if (!is_null($cartId)) {
            $predicate .= ' and id = "' . $cartId . '"';
        }

        if (!is_null($user)) {
            $predicate .= ' and customerId = "' . $user->getId() . '"';
        } elseif (!is_null($anonymousId)) {
            $predicate .= ' and anonymousId = "' . $anonymousId . '"';
        }

        $cartRequest->where($predicate)->limit(1);

        $carts = $this->executeRequest($cartRequest, $locale);

        return $carts->current();
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
    public function createCart($locale, $currency, Location $location, LineItemDraftCollection $lineItemDraftCollection = null, $customerId = null, $anonymousId = null)
    {
        $cartDraft = CartDraft::ofCurrency($currency)->setCountry($location->getCountry())
            ->setShippingAddress(Address::of()->setCountry($location->getCountry()));

        if (!is_null($lineItemDraftCollection)) {
            $cartDraft->setLineItems($lineItemDraftCollection);
        }

        if (!is_null($customerId)) {
            $cartDraft->setCustomerId($customerId);
        } else if (!is_null($anonymousId)) {
            $cartDraft->setAnonymousId($anonymousId);
        } else {
            throw new InvalidArgumentException('At least one of CustomerId or AnonymousId should be present');
        }

        $request = RequestBuilder::of()->carts()->create($cartDraft);

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
        $request = RequestBuilder::of()->carts()->update($cart)->setActions($actions);

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        return $this->executeRequest($request);
    }
}
