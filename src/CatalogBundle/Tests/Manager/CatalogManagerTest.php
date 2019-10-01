<?php
/**
 */

namespace Commercetools\Symfony\CatalogBundle\Tests\Manager;

use Commercetools\Core\Model\Category\CategoryCollection;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\Product\ProductProjectionCollection;
use Commercetools\Core\Model\ProductType\ProductTypeCollection;
use Commercetools\Core\Request\Products\Command\ProductSetKeyAction;
use Commercetools\Core\Request\Products\ProductProjectionSearchRequest;
use Commercetools\Symfony\CatalogBundle\Event\ProductPostUpdateEvent;
use Commercetools\Symfony\CatalogBundle\Event\ProductUpdateEvent;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Commercetools\Symfony\CatalogBundle\Model\ProductUpdateBuilder;
use Commercetools\Symfony\CatalogBundle\Model\Repository\CatalogRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\UriInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CatalogManagerTest extends TestCase
{
    /** @var CatalogRepository */
    private $repository;
    private $eventDispatcher;

    public function setUp()
    {
        $this->repository = $this->prophesize(CatalogRepository::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    }

    public function testGetProductById()
    {
        $this->repository->getProductById('en', '123')
            ->willReturn(ProductProjection::of()->setId('123'))->shouldBeCalled();

        $manager = new CatalogManager($this->repository->reveal(), $this->eventDispatcher->reveal());
        $product = $manager->getProductById('en', '123');

        $this->assertInstanceOf(ProductProjection::class, $product);
        $this->assertSame('123', $product->getId());
    }

    public function testGetProductBySlug()
    {
        $this->repository->getProductBySlug('en', 'slug-123', 'EUR', 'DE')
            ->willReturn(ProductProjection::of()->setId('123')->setSlug(
                LocalizedString::ofLangAndText('en', 'slug-123')
            ))->shouldBeCalled();

        $manager = new CatalogManager($this->repository->reveal(), $this->eventDispatcher->reveal());
        $product = $manager->getProductBySlug('en', 'slug-123', 'EUR', 'DE');

        $this->assertInstanceOf(ProductProjection::class, $product);
        $this->assertSame('123', $product->getId());
        $this->assertInstanceOf(LocalizedString::class, $product->getSlug());
    }

    public function testGetProductTypes()
    {
        $this->repository->getProductTypes('en', null)
            ->willReturn(ProductTypeCollection::of())->shouldBeCalled();

        $manager = new CatalogManager($this->repository->reveal(), $this->eventDispatcher->reveal());
        $product = $manager->getProductTypes('en');

        $this->assertInstanceOf(ProductTypeCollection::class, $product);
    }

    public function testGetCategories()
    {
        $this->repository->getCategories('en', null)
            ->willReturn(CategoryCollection::of())->shouldBeCalled();

        $manager = new CatalogManager($this->repository->reveal(), $this->eventDispatcher->reveal());
        $product = $manager->getCategories('en');

        $this->assertInstanceOf(CategoryCollection::class, $product);
    }

    public function testSuggestProducts()
    {
        $this->repository->suggestProducts('en', 'foo', 5, 'EUR', 'DE')
            ->willReturn(ProductProjectionCollection::of())->shouldBeCalled();

        $manager = new CatalogManager($this->repository->reveal(), $this->eventDispatcher->reveal());
        $product = $manager->suggestProducts('en', 'foo', 5, 'EUR', 'DE');

        $this->assertInstanceOf(ProductProjectionCollection::class, $product);
    }

    public function testGetProducts()
    {
        $searchRequest = $this->prophesize(ProductProjectionSearchRequest::class);

        $this->repository->baseSearchProductsRequest(5, 1, null)
            ->willReturn($searchRequest->reveal())->shouldBeCalledOnce();

        $this->repository->searchRequestAddCountryAndCurrency(
            Argument::type(ProductProjectionSearchRequest::class),
            'DE',
            'EUR'
        )->willReturn($searchRequest->reveal())->shouldBeCalledOnce();

        $this->repository->searchRequestAddSearchParameters(
            Argument::type(ProductProjectionSearchRequest::class),
            Argument::type('string'),
            Argument::type(UriInterface::class),
            Argument::is(null)
        )->willReturn($searchRequest->reveal())->shouldBeCalledOnce();

        $this->repository->searchRequestAddSearchFilters(
            Argument::type(ProductProjectionSearchRequest::class),
            Argument::is(null)
        )->willReturn($searchRequest->reveal())->shouldBeCalledOnce();

        $this->repository->executeSearchRequest(Argument::type(ProductProjectionSearchRequest::class), Argument::type('string'))
            ->willReturn([])->shouldBeCalledOnce();

        $uri = $this->prophesize(UriInterface::class);

        $manager = new CatalogManager($this->repository->reveal(), $this->eventDispatcher->reveal());
        $products = $manager->searchProducts(
            'en',
            5,
            1,
            null,
            'EUR',
            'DE',
            $uri->reveal()
        );

        $this->assertIsArray($products);
    }

    public function testUpdate()
    {
        $manager = new CatalogManager($this->repository->reveal(), $this->eventDispatcher->reveal());
        $update = $manager->update(Product::of()->setKey('product-1'));

        $this->assertInstanceOf(ProductUpdateBuilder::class, $update);
    }

    public function testApply()
    {
        $product = Product::of()->setId('1');

        $this->repository->update($product, Argument::type('array'))
            ->will(function ($args) {
                return $args[0];
            })->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            Argument::type(ProductPostUpdateEvent::class)
        )->will(function ($args) {
            return $args[0];
        })->shouldBeCalled();

        $manager = new CatalogManager($this->repository->reveal(), $this->eventDispatcher->reveal());
        $product = $manager->apply($product, []);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertSame('1', $product->getId());
    }

    public function testDispatch()
    {
        $product = Product::of()->setId('1');
        $action = ProductSetKeyAction::of();

        $this->eventDispatcher->dispatch(
            Argument::type(ProductUpdateEvent::class),
            Argument::containingString(ProductSetKeyAction::class)
        )->will(function ($args) {
            return $args[0];
        })->shouldBeCalled();

        $manager = new CatalogManager($this->repository->reveal(), $this->eventDispatcher->reveal());

        $actions = $manager->dispatch($product, $action);
        $this->assertCount(1, $actions);
        $this->assertInstanceOf(ProductSetKeyAction::class, current($actions));
    }

    public function testDispatchPostUpdate()
    {
        $product = Product::of()->setId('1');
        $action = ProductSetKeyAction::of();

        $this->eventDispatcher->dispatch(
            Argument::type(ProductPostUpdateEvent::class)
        )->will(function ($args) {
            return $args[0];
        })->shouldBeCalled();

        $manager = new CatalogManager($this->repository->reveal(), $this->eventDispatcher->reveal());

        $actions = $manager->dispatchPostUpdate($product, [$action]);
        $this->assertCount(1, $actions);
        $this->assertInstanceOf(ProductSetKeyAction::class, current($actions));
    }
}
