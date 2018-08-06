<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Commercetools\Core\Client;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;


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
     * OrderController constructor.
     * @param Client $client
     * @param OrderManager $manager
     */
    public function __construct(Client $client, OrderManager $manager)
    {
        $this->client = $client;
        $this->manager = $manager;
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


}
