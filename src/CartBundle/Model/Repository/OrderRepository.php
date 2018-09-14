<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Symfony\Component\Security\Core\User\UserInterface;

class OrderRepository extends Repository
{
    /**
     * @param $locale
     * @param UserInterface|null $user
     * @param null $anonymousId
     * @return OrderCollection
     */
    public function getOrders($locale, UserInterface $user = null, $anonymousId = null)
    {
        $request = RequestBuilder::of()->orders()->query();

        if (!is_null($user)) {
            $request->where('customerId = "' . $user->getId() . '"');
        } elseif (!is_null($anonymousId)) {
            $request->where('anonymousId = "' . $anonymousId . '"');
        } else {
            throw new InvalidArgumentException('At least one of CustomerId or AnonymousId should be present');
        }
        $request->sort('createdAt desc');

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param $locale
     * @param $orderId
     * @param UserInterface|null $user
     * @param null $anonymousId
     * @return Order
     */
    public function getOrder($locale, $orderId, UserInterface $user = null, $anonymousId = null)
    {
        $request = RequestBuilder::of()->orders()->query();

        if (!is_null($user)) {
            $request->where('id = "' . $orderId . '" and customerId = "' . $user->getId() . '"');
        } else if (!is_null($anonymousId)) {
            $request->where('id = "' . $orderId . '" and anonymousId = "' . $anonymousId . '"');
        } else {
            throw new InvalidArgumentException('At least one of CustomerId or AnonymousId should be present');
        }

        $orders = $this->executeRequest($request, $locale);

        return $orders->current();
    }

    /**
     * @param $locale
     * @param $paymentId
     * @param UserInterface|null $user
     * @param null $anonymousId
     * @return mixed
     */
    public function getOrderFromPayment($locale, $paymentId, UserInterface $user = null, $anonymousId = null)
    {
        $request = RequestBuilder::of()->orders()->query();

        $predicate = 'paymentInfo(payments(id = "' . $paymentId . '"))';

        if (!is_null($user)) {
            $predicate .= ' and customerId = "' . $user->getId() . '"';
        } elseif (!is_null($anonymousId)) {
            $predicate .= ' and anonymousId = "' . $user->getId() . '"';
        } else {
            throw new InvalidArgumentException('At least one of CustomerId or AnonymousId should be preset');
        }

        $request->where($predicate);
        $orders = $this->executeRequest($request, $locale);

        return $orders->current();
    }

    /**
     * @param $locale
     * @param Cart $cart
     * @param StateReference $stateReference
     * @return Order
     */
    public function createOrderFromCart($locale, Cart $cart, StateReference $stateReference)
    {
        $request = RequestBuilder::of()->orders()
            ->createFromCart($cart)
            ->setOrderNumber($this->createOrderNumber())
            ->setState($stateReference);

        return $this->executeRequest($request, $locale);
    }

    /**
     * @return string
     */
    protected function createOrderNumber()
    {
        return (string)time();
    }

    /**
     * @param Order $order
     * @param array $actions
     * @param QueryParams|null $params
     * @return Order
     */
    public function update(Order $order, array $actions, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->orders()->update($order)->setActions($actions);

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        return $this->executeRequest($request);
    }
}
