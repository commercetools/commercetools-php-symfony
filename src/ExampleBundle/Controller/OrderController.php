<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\StateBundle\Model\ItemStateWrapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Commercetools\Core\Client;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
use Symfony\Component\Workflow\Registry;

class OrderController extends AbstractController
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

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, SessionInterface $session, UserInterface $user = null)
    {
        $orders = $this->manager->getOrdersForUser($request->getLocale(), $user, $session->getId());

        return $this->render('@Example/my-account-my-orders.html.twig', [
            'orders' => $orders
        ]);
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param UserInterface|null $user
     * @param $orderId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showOrderAction(Request $request, SessionInterface $session, UserInterface $user = null, $orderId)
    {
        $order = $this->manager->getOrderForUser($request->getLocale(), $orderId, $user, $session->getId());

        if (!$order instanceof Order) {
            $this->addFlash('error', sprintf('Cannot find order: %s', $orderId));
            return $this->render('@Example/index.html.twig');
        }

        if (!is_null($order->getPaymentInfo())) {
            $paymentsIds = array_map(function ($elem) {
                return $elem['id'];
            }, $order->getPaymentInfo()->getPayments()->toArray());
            $payments = $this->paymentManager->getMultiplePayments($request->getLocale(), $paymentsIds);
        }

        return $this->render('@Example/my-account-my-orders-order.html.twig', [
            'order' => $order,
            'payments' => $payments ?? []
        ]);
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param $orderId
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function updateLineItemAction(Request $request, SessionInterface $session, $orderId, UserInterface $user = null)
    {
        $order = $this->manager->getOrderForUser($request->getLocale(), $orderId, $user, $session->getId());

        if ($request->get('lineItemId')) {
            $lineItem = $order->getLineItems()->getById($request->get('lineItemId'));
        } elseif ($request->get('customLineItemId')) {
            $lineItem = $order->getCustomLineItems()->getById($request->get('customLineItemId'));
        } else {
            $this->addFlash('error', 'Either a LineItemId or a CustomLineItemId must have been set');
            return $this->render('@Example/index.html.twig');
        }

        $currentStateReference = StateReference::ofId($request->get('fromState'));
        $quantity = $request->get('quantity') ?? 1;

        $subject = ItemStateWrapper::create($order, $currentStateReference, $lineItem, (int)$quantity);

        try {
            $workflow = $this->workflows->get($subject);
        } catch (InvalidArgumentException $e) {
            $this->addFlash('error', 'Cannot find proper workflow configuration. Action aborted');
            return $this->render('@Example/index.html.twig');
        }

        if (!$workflow->can($subject, $request->get('toState'))) {
            $this->addFlash('error', 'Cannot perform this action');
            return $this->render('@Example/index.html.twig');
        }

        $workflow->apply($subject, $request->get('toState'));
        return $this->redirect($this->generateUrl('_ctp_example_order', ['orderId' => $orderId]));
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param UserInterface|null $user
     * @param $orderId
     * @param $toState
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function updateOrderAction(Request $request, SessionInterface $session, UserInterface $user = null, $orderId, $toState)
    {
        $order = $this->manager->getOrderForUser($request->getLocale(), $orderId, $user, $session->getId());

        try {
            $workflow = $this->workflows->get($order);
        } catch (InvalidArgumentException $e) {
            $this->addFlash('error', 'Cannot find proper workflow configuration. Action aborted');
            return $this->render('@Example/index.html.twig');
        }

        if (!$workflow->can($order, $toState)) {
            $this->addFlash('error', 'Cannot perform this action');
            return $this->render('@Example/index.html.twig');
        }

        $workflow->apply($order, $toState);
        return $this->redirect($this->generateUrl('_ctp_example_order', ['orderId' => $orderId]));
    }
}
