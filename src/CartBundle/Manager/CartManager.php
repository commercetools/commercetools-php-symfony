<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 02/05/2018
 * Time: 12:02
 */

namespace Commercetools\Symfony\CartBundle\Manager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;

class CartManager
{
    /**
     * @var CartRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * CartManager constructor.
     * @param CartRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(CartRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    public function getCart($locale, $cartId = null, $customerId = null)
    {
        return $this->repository->getCart($locale, $cartId, $customerId);
    }

    public function addLineItem($locale, $cartId, $productId, $variantId, $quantity, $currency, $country, $customerId = null)
    {
        return $this->repository->addLineItem($locale, $cartId, $productId, $variantId, $quantity, $currency, $country, $customerId);
    }
}
