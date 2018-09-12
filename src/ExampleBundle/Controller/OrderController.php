<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\StateBundle\Model\ItemStateWrapper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Commercetools\Core\Client;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
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
     * @var PaymentManager
     */
    private $paymentManager;

    /**
     * OrderController constructor.
     * @param Client $client
     * @param OrderManager $manager
     * @param Registry $workflows
     * @param PaymentManager $paymentManager
     */
    public function __construct(Client $client, OrderManager $manager, Registry $workflows, PaymentManager $paymentManager)
    {
        $this->client = $client;
        $this->manager = $manager;
        $this->workflows = $workflows;
        $this->paymentManager = $paymentManager;
    }

    public function indexAction(Request $request, SessionInterface $session, UserInterface $user = null)
    {
        if (is_null($user)) {
            $orders = $this->manager->getOrdersForAnonymous($request->getLocale(), $session->getId());
        } else {
            $orders = $this->manager->getOrdersForCustomer($request->getLocale(), $user->getId());
        }

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

    public function updateLineItemAction(Request $request, SessionInterface $session, UserInterface $user = null, $orderId)
    {
        if(is_null($user)){
            $orders = $this->manager->getOrderForAnonymous($request->getLocale(), $session->getId(), $orderId);
        } else {
            $orders = $this->manager->getOrderForCustomer($request->getLocale(), $user->getId(), $orderId);
        }

        if (!$orders instanceof OrderCollection) {
            $this->addFlash('error', $orders->getMessage());
            return $this->render('@Example/index.html.twig');
        }

        $order = $orders->current();
        $currentStateReference = StateReference::ofId($request->get('fromState'));

        if ($request->get('lineItemId')){
            $lineItem = $order->getLineItems()->getById($request->get('lineItemId'));
        } elseif ($request->get('customLineItemId')) {
            $lineItem = $order->getCustomLineItems()->getById($request->get('customLineItemId'));
        } else {
            $this->addFlash('error', 'Either a LineItemId or a CustomLineItemId must have been set');
            return $this->render('@Example/index.html.twig');
        }

        $quantity = $request->get('quantity') ?? 1;

        $subject = ItemStateWrapper::create($order, $currentStateReference, $lineItem, (int)$quantity);

        try {
            $workflow = $this->workflows->get($subject);
        } catch (InvalidArgumentException $e) {
            $this->addFlash('error', 'Cannot find proper workflow configuration. Action aborted');
            return $this->render('@Example/index.html.twig');
        }

        if ($workflow->can($subject, $request->get('toState'))) {
            $workflow->apply($subject, $request->get('toState'));
            return $this->redirect($this->generateUrl('_ctp_example_order', ['orderId' => $orderId]));
        }

        $this->addFlash('error', 'Cannot perform this action');
        return $this->render('@Example/index.html.twig');
    }

    public function updateOrderAction(Request $request, SessionInterface $session, UserInterface $user = null, $orderId, $toState)
    {
        if(is_null($user)){
            $orders = $this->manager->getOrderForAnonymous($request->getLocale(), $session->getId(), $orderId);
        } else {
            $orders = $this->manager->getOrderForCustomer($request->getLocale(), $user->getId(), $orderId);
        }

        if (!$orders instanceof OrderCollection) {
            $this->addFlash('error', $orders->getMessage());
            return $this->render('@Example/index.html.twig');
        }

        $order = $orders->current();

        try {
            $workflow = $this->workflows->get($order);
        } catch (InvalidArgumentException $e) {
            $this->addFlash('error', 'Cannot find proper workflow configuration. Action aborted');
            return $this->render('@Example/index.html.twig');
        }

        if ($workflow->can($order, $toState)) {
            $workflow->apply($order, $toState);
            return $this->redirect($this->generateUrl('_ctp_example_order', ['orderId' => $orderId]));
        }

        $this->addFlash('error', 'Cannot perform this action');
        return $this->render('@Example/index.html.twig');

    }
}
