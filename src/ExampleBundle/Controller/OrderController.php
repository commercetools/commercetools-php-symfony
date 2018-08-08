<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Orders\Command\OrderTransitionStateAction;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use Commercetools\Symfony\CartBundle\Model\OrderWrapper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Commercetools\Core\Client;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\Registry;


class OrderController extends Controller
{
    const CSRF_TOKEN_NAME = 'csrfToken';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var OrderManager
     */
    private $manager;

    /**
     * @var Registry
     */
    private $workflows;

    /**
     * OrderController constructor.
     * @param Client $client
     * @param OrderManager $manager
     * @param Registry $workflows
     */
    public function __construct(Client $client, OrderManager $manager, Registry $workflows)
    {
        $this->client = $client;
        $this->manager = $manager;
        $this->workflows = $workflows;
    }


    protected function getCustomerId()
    {
        $user = $this->getUser();
        if (is_null($user)) {
            return null;
        }
        $customerId = $user->getId();

        return $customerId;
    }

    public function indexAction(Request $request, UserInterface $user)
    {
        $orders = $this->manager->getOrders($request->getLocale(), $user->getId());

        return $this->render('ExampleBundle:user:orders.html.twig', [
            'orders' => $orders
        ]);
    }

    public function showOrderAction(Request $request, SessionInterface $session, UserInterface $user = null, $orderId)
    {
        if(is_null($user)){
            $order = $this->manager->getOrderForAnonymous($request->getLocale(), $session->getId(), $orderId);
        } else {
            $order = $this->manager->getOrderForCustomer($request->getLocale(), $user->getId(), $orderId);
        }

        return $this->render('ExampleBundle:user:order.html.twig', [
            'order' => $order->current()
        ]);
    }

    public function cancelOrderAction(Request $request, SessionInterface $session, UserInterface $user = null, $orderId)
    {
        if(is_null($user)){
            $orders = $this->manager->getOrderForAnonymous($request->getLocale(), $session->getId(), $orderId);
        } else {
            $orders = $this->manager->getOrderForCustomer($request->getLocale(), $user->getId(), $orderId);
        }

        if (get_class($orders) !== OrderCollection::class) {
            $this->addFlash('error', $orders->getMessage());
            return $this->render('@Example/index.html.twig');
        }

        $order = $orders->current();

        $orderWrapper = OrderWrapper::fromArray($order->toArray());

        $workflow = $this->workflows->get($orderWrapper);

        if ($workflow->can($orderWrapper, 'createdToCanceled') ||
            $workflow->can($orderWrapper, 'readyToShipToCanceled')
        ) {
            $orderBuilder = new OrderUpdateBuilder($order, $this->manager);
            $orderBuilder->addAction(
                OrderTransitionStateAction::ofState(StateReference::ofTypeAndKey('state', 'canceled'))->setForce(true)
            );

            $orderBuilder->flush();

            return $this->render('ExampleBundle:user:order.html.twig', [
                'order' => $order
            ]);
        }

        $this->addFlash('error', 'cannot perform this action');
        return $this->render('@Example/index.html.twig');

    }


}
