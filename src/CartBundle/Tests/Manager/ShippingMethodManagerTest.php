<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Manager;

use Commercetools\Core\Model\ShippingMethod\ShippingMethod;
use Commercetools\Core\Model\ShippingMethod\ShippingMethodCollection;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Symfony\CartBundle\Manager\ShippingMethodManager;
use Commercetools\Symfony\CartBundle\Model\Repository\ShippingMethodRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShippingMethodManagerTest extends TestCase
{
    private $repository;
    private $dispatcher;

    public function setUp()
    {
        $this->repository = $this->prophesize(ShippingMethodRepository::class);
        $this->dispatcher= $this->prophesize(EventDispatcherInterface::class);
    }

    public function testGetShippingMethodsByLocation()
    {
        $this->repository->getShippingMethodsByLocation('en', Location::of(), null)
            ->willReturn(ShippingMethodCollection::of())->shouldBeCalled();

        $manager = new ShippingMethodManager($this->repository->reveal(), $this->dispatcher->reveal());
        $payment = $manager->getShippingMethodsByLocation('en', Location::of());

        $this->assertInstanceOf(ShippingMethodCollection::class, $payment);
    }

    public function testGetShippingMethodById()
    {
        $this->repository->getShippingMethodById('en', Location::of())
            ->willReturn(ShippingMethod::of())->shouldBeCalled();

        $manager = new ShippingMethodManager($this->repository->reveal(), $this->dispatcher->reveal());
        $payment = $manager->getShippingMethodById('en', Location::of());

        $this->assertInstanceOf(ShippingMethod::class, $payment);
    }

    public function testGetShippingMethodByCart()
    {
        $this->repository->getShippingMethodByCart('en', Location::of())
            ->willReturn(ShippingMethod::of())->shouldBeCalled();

        $manager = new ShippingMethodManager($this->repository->reveal(), $this->dispatcher->reveal());
        $payment = $manager->getShippingMethodByCart('en', Location::of());

        $this->assertInstanceOf(ShippingMethod::class, $payment);
    }
}
