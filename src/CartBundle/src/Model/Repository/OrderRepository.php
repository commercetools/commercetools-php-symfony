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
use Commercetools\Symfony\CtpBundle\Security\User\CtpUser;

class OrderRepository extends Repository
{
    /**
     * @param $locale
     * @param CtpUser|null $user
     * @param string|null $anonymousId
     * @return OrderCollection
     */
    public function getOrders($locale, CtpUser $user = null, $anonymousId = null)
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
     * @param CtpUser|null $user
     * @param string|null $anonymousId
     * @return Order
     */
    public function getOrder($locale, $orderId, CtpUser $user = null, $anonymousId = null)
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
     * @param CtpUser|null $user
     * @param string|null $anonymousId
     * @return mixed
     */
    public function getOrderFromPayment($locale, $paymentId, CtpUser $user = null, $anonymousId = null)
    {
        $request = RequestBuilder::of()->orders()->query();

        $predicate = 'paymentInfo(payments(id = "' . $paymentId . '"))';

        if (!is_null($user)) {
            $predicate .= ' and customerId = "' . $user->getId() . '"';
        } elseif (!is_null($anonymousId)) {
            $predicate .= ' and anonymousId = "' . $anonymousId . '"';
        } else {
            throw new InvalidArgumentException('At least one of CustomerId or AnonymousId should be present');
        }

        $request->where($predicate);
        $orders = $this->executeRequest($request, $locale);

        return $orders->current();
    }

    /**
     * @param $locale
     * @param Cart $cart
     * @param StateReference|null $stateReference
     * @return Order
     */
    public function createOrderFromCart($locale, Cart $cart, StateReference $stateReference = null)
    {
        $request = RequestBuilder::of()->orders()
            ->createFromCart($cart)
            ->setOrderNumber($this->createOrderNumber());

        if (!is_null($stateReference)) {
            $request->setState($stateReference);
        }

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

    /**
     * @param Order $order
     * @return mixed
     */
    public function delete(Order $order)
    {
        $request = RequestBuilder::of()->orders()->delete($order);

        return $this->executeRequest($request);
    }
}
