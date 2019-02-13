<?php
/**
 */

namespace Commercetools\Symfony\ShoppingListBundle\Tests\Manager;

use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Model\ShoppingList\ShoppingListCollection;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListUpdateEvent;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListPostUpdateEvent;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use Commercetools\Symfony\ShoppingListBundle\Model\Repository\ShoppingListRepository;
use Commercetools\Symfony\ShoppingListBundle\Model\ShoppingListUpdateBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShoppingListManagerTest extends TestCase
{
    public function testApply()
    {
        $shoppingList = $this->prophesize(ShoppingList::class);
        $repository = $this->prophesize(ShoppingListRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->update($shoppingList, Argument::type('array'))
            ->will(function ($args) {
                return $args[0];
            })->shouldBeCalled();

        $dispatcher->dispatch(
            Argument::containingString(ShoppingListPostUpdateEvent::class),
            Argument::type(ShoppingListPostUpdateEvent::class)
        )->will(function ($args) {
            return $args[1];
        })->shouldBeCalled();

        $manager = new ShoppingListManager($repository->reveal(), $dispatcher->reveal());
        $list = $manager->apply($shoppingList->reveal(), []);

        $this->assertInstanceOf(ShoppingList::class, $list);
    }

    public function testGetAllOfCustomer()
    {
        $customer = $this->prophesize(CustomerReference::class);
        $repository = $this->prophesize(ShoppingListRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getAllShoppingListsByCustomer('en', $customer->reveal(), null)
            ->willReturn(ShoppingListCollection::of())->shouldBeCalled();

        $manager = new ShoppingListManager($repository->reveal(), $dispatcher->reveal());
        $lists = $manager->getAllOfCustomer('en', $customer->reveal());

        $this->assertInstanceOf(ShoppingListCollection::class, $lists);
    }

    public function testGetAllOfAnonymous()
    {
        $repository = $this->prophesize(ShoppingListRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getAllShoppingListsByAnonymousId('en', 'anon-1', null)
            ->willReturn(ShoppingListCollection::of())->shouldBeCalled();

        $manager = new ShoppingListManager($repository->reveal(), $dispatcher->reveal());
        $lists = $manager->getAllOfAnonymous('en', 'anon-1');

        $this->assertInstanceOf(ShoppingListCollection::class, $lists);
    }

    public function testGetShoppingListForCustomer()
    {
        $repository = $this->prophesize(ShoppingListRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $customer = CustomerReference::ofId('user-1');

        $repository->getShoppingList('en', 'list-1', $customer, null, null)
            ->willReturn(ShoppingList::of())->shouldBeCalled();

        $manager = new ShoppingListManager($repository->reveal(), $dispatcher->reveal());
        $list = $manager->getShoppingListForUser('en', 'list-1', $customer);

        $this->assertInstanceOf(ShoppingList::class, $list);
    }

    public function testGetShoppingListForAnonymous()
    {
        $repository = $this->prophesize(ShoppingListRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getShoppingList('en', 'list-1', null, 'anon-1', null)
            ->willReturn(ShoppingList::of())->shouldBeCalled();

        $manager = new ShoppingListManager($repository->reveal(), $dispatcher->reveal());
        $list = $manager->getShoppingListForUser('en', 'list-1', null, 'anon-1');

        $this->assertInstanceOf(ShoppingList::class, $list);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetShoppingListWithMissingData()
    {
        $repository = $this->prophesize(ShoppingListRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $manager = new ShoppingListManager($repository->reveal(), $dispatcher->reveal());
        $manager->getShoppingListForUser('en', 'list-1');
    }

    public function testCreateShoppingListByCustomer()
    {
        $customer = $this->prophesize(CustomerReference::class);
        $repository = $this->prophesize(ShoppingListRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->create('en', 'list-1', $customer->reveal())
            ->willReturn(ShoppingList::of())->shouldBeCalled();

        $manager = new ShoppingListManager($repository->reveal(), $dispatcher->reveal());
        $lists = $manager->createShoppingListByCustomer('en', $customer->reveal(), 'list-1');

        $this->assertInstanceOf(ShoppingList::class, $lists);
    }

    public function testCreateShoppingListByAnonymous()
    {
        $repository = $this->prophesize(ShoppingListRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->create('en', 'list-1', null, 'anon-1')
            ->willReturn(ShoppingList::of())->shouldBeCalled();

        $manager = new ShoppingListManager($repository->reveal(), $dispatcher->reveal());
        $lists = $manager->createShoppingListByAnonymous('en', 'anon-1', 'list-1');

        $this->assertInstanceOf(ShoppingList::class, $lists);
    }

    public function testDeleteShoppingList()
    {
        $repository = $this->prophesize(ShoppingListRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $shoppingList = ShoppingList::of();

        $repository->delete('en', $shoppingList)
            ->willReturn(ShoppingList::of())->shouldBeCalled();

        $manager = new ShoppingListManager($repository->reveal(), $dispatcher->reveal());
        $lists = $manager->deleteShoppingList('en', $shoppingList);

        $this->assertInstanceOf(ShoppingList::class, $lists);
    }

    public function testDispatch()
    {
        $shoppingList = $this->prophesize(ShoppingList::class);
        $repository = $this->prophesize(ShoppingListRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher->dispatch(
            Argument::containingString(AbstractAction::class),
            Argument::type(ShoppingListUpdateEvent::class)
        )->will(function ($args) {
            return $args[1];
        })->shouldBeCalled();
        $action = $this->prophesize(AbstractAction::class);

        $manager = new ShoppingListManager($repository->reveal(), $dispatcher->reveal());

        $actions = $manager->dispatch($shoppingList->reveal(), $action->reveal());
        $this->assertInstanceOf(AbstractAction::class, current($actions));
        $this->assertCount(1, $actions);
    }

    public function testUpdate()
    {
        $shoppingList = $this->prophesize(ShoppingList::class);
        $repository = $this->prophesize(ShoppingListRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $manager = new ShoppingListManager($repository->reveal(), $dispatcher->reveal());
        $this->assertInstanceOf(ShoppingListUpdateBuilder::class, $manager->update($shoppingList->reveal()));
    }

    public function testGetById()
    {
        $repository = $this->prophesize(ShoppingListRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getShoppingListById('en', '123456', null)
            ->willReturn(ShoppingList::of())->shouldBeCalled();

        $manager = new ShoppingListManager($repository->reveal(), $dispatcher->reveal());
        $list = $manager->getById('en', '123456');

        $this->assertInstanceOf(ShoppingList::class, $list);
    }
}
