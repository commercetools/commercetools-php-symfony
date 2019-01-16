<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
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
     * @param string $locale
     * @param string $orderId
     * @return Order
     */
    public function getOrderById($locale, $orderId)
    {
        $request = RequestBuilder::of()->orders()->getById($orderId);

        return $this->executeRequest($request, $locale);
    }

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
        }

        $request->sort('createdAt desc');

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param string $locale
     * @param string $orderId
     * @param UserInterface|null $user
     * @param string|null $anonymousId
     * @return Order
     */
    public function getOrder($locale, $orderId, UserInterface $user = null, $anonymousId = null)
    {
        $request = RequestBuilder::of()->orders()->query();

        $where = 'id = "' . $orderId . '"';

        if (!is_null($user)) {
            $where .= ' and customerId = "' . $user->getId() . '"';
        } else if (!is_null($anonymousId)) {
            $where .= ' and anonymousId = "' . $anonymousId . '"';
        }

        $request->where($where);

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
            $predicate .= ' and anonymousId = "' . $anonymousId . '"';
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
