<?php
/**
 *
 */

namespace Commercetools\Symfony\CatalogBundle\Tests\Model\Repository;

use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Common\Context;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Model\Product\ProductDraft;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\Product\ProductProjectionCollection;
use Commercetools\Core\Model\Product\Search\Filter;
use Commercetools\Core\Model\ProductType\ProductTypeDraft;
use Commercetools\Core\Model\ProductType\ProductTypeReference;
use Commercetools\Core\Request\AbstractProjectionRequest;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\Products\Command\ProductSetKeyAction;
use Commercetools\Core\Request\Products\ProductCreateRequest;
use Commercetools\Core\Request\Products\ProductProjectionByIdGetRequest;
use Commercetools\Core\Request\Products\ProductProjectionBySlugGetRequest;
use Commercetools\Core\Request\Products\ProductProjectionSearchRequest;
use Commercetools\Core\Request\Products\ProductUpdateRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeCreateRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeQueryRequest;
use Commercetools\Core\Response\PagedSearchResponse;
use Commercetools\Core\Response\ResourceResponse;
use Commercetools\Symfony\CatalogBundle\Model\Repository\CatalogRepository;
use Commercetools\Symfony\CatalogBundle\Model\Search;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Cache\Tests\Fixtures\ExternalAdapter;


class CatalogRepositoryTest extends TestCase
{
    private $cache;
    private $mapperFactory;
    private $response;
    private $client;
    private $search;

    protected function setUp()
    {
        $this->cache = new ExternalAdapter();
        $this->mapperFactory = $this->prophesize(MapperFactory::class);
        $this->search = $this->prophesize(Search::class);

        $this->response = $this->prophesize(ResourceResponse::class);
        $this->response->toArray()->willReturn([]);
        $this->response->getContext()->willReturn(null);
        $this->response->isError()->willReturn(false);

        $this->client = $this->prophesize(Client::class);
    }

    private function getCatalogRepository()
    {
        return new CatalogRepository(
            "false",
            $this->cache,
            $this->client->reveal(),
            $this->mapperFactory->reveal(),
            $this->search->reveal()
        );
    }

    public function testGetProductBySlug()
    {
        $context = $this->prophesize(Context::class);
        $context->getLanguages()->willReturn(['en'])->shouldBeCalled();

        $config = $this->prophesize(Config::class);
        $config->getContext()->willReturn($context->reveal())->shouldBeCalled();

        $this->client->getConfig()->willReturn($config->reveal())->shouldBeCalled();

        $this->response->toObject()->willReturn(ProductProjection::of())->shouldBeCalled();

        $this->client->execute(
            Argument::type(ProductProjectionBySlugGetRequest::class),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getCatalogRepository();
        $repository->getProductBySlug('en', 'foo', 'EUR', 'DE');
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage resource not found
     */
    public function testGetProductBySlugWithEmptyResponse()
    {
        $context = $this->prophesize(Context::class);
        $context->getLanguages()->willReturn(['en'])->shouldBeCalled();

        $config = $this->prophesize(Config::class);
        $config->getContext()->willReturn($context->reveal())->shouldBeCalled();

        $this->client->getConfig()->willReturn($config->reveal())->shouldBeCalled();

        $this->response->toObject()->willReturn(null)->shouldBeCalled();

        $this->client->execute(
            Argument::type(ProductProjectionBySlugGetRequest::class),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getCatalogRepository();
        $repository->getProductBySlug('en', 'foo', 'EUR', 'DE');
    }

    public function testGetProductById()
    {
        $this->response->toObject()->willReturn(ProductProjectionCollection::of()->add(
            ProductProjection::of()->setId('product-1')->setKey('foo'))
        )->shouldBeCalled();

        $this->client->execute(
            Argument::that(function(ProductProjectionByIdGetRequest $request){
                static::assertSame(ProductProjection::class, $request->getResultClass());
                static::assertSame('foo', $request->getId());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getCatalogRepository();
        $repository->getProductById('en', 'foo');
    }

    public function testSuggestProducts()
    {
        $this->response->toArray()->willReturn(['searchKeywords.en' => ['foo' => ['bar']]]);

        $this->client->execute(
            Argument::type(AbstractProjectionRequest::class),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledTimes(2);

        $repository = $this->getCatalogRepository();
        $repository->suggestProducts('en','foo', null, null, null);
    }

    public function testGetProducts()
    {
        $this->search->getSelectedValues(Argument::type(UriInterface::class))
            ->willReturn(null)
            ->shouldBeCalledOnce();

        $this->search->addFacets(Argument::type(ProductProjectionSearchRequest::class), Argument::is(null))
            ->will(function($args){return $args[0];})
            ->shouldBeCalledOnce();

        $response = $this->prophesize(PagedSearchResponse::class);
        $response->toArray()->willReturn([])->shouldBeCalled();
        $response->getContext()->willReturn(null)->shouldBeCalled();
        $response->isError()->willReturn(false)->shouldBeCalled();
        $response->getFacets()->willReturn(null)->shouldBeCalledOnce();
        $response->getOffset()->willReturn(null)->shouldBeCalledOnce();
        $response->getTotal()->willReturn(null)->shouldBeCalledOnce();

        $this->client->execute(
            Argument::that(function(ProductProjectionSearchRequest $request){
                static::assertContains('limit=5', (string)$request->httpRequest()->getBody());
                static::assertContains('priceCurrency=EUR', (string)$request->httpRequest()->getBody());
                static::assertContains('priceCountry=DE', (string)$request->httpRequest()->getBody());
                static::assertContains('sort=id+desc', (string)$request->httpRequest()->getBody());
                static::assertContains('offset=5', (string)$request->httpRequest()->getBody());
                static::assertNotContains('fuzzy', (string)$request->httpRequest()->getBody());
                static::assertNotContains('filter', (string)$request->httpRequest()->getBody());

                return true;
            }),
            Argument::is(null)
        )->willReturn($response->reveal())->shouldBeCalledOnce();

        $uri = $this->prophesize(UriInterface::class);

        $repository = $this->getCatalogRepository();
        $repository->getProducts(
            'en', 5, 2, 'id desc', 'EUR', 'DE', $uri->reveal()
        );
    }

    public function testGetProductsWithSearchAndFilters()
    {
        $this->search->getSelectedValues(Argument::type(UriInterface::class))
            ->willReturn(null)
            ->shouldBeCalledOnce();

        $this->search->addFacets(Argument::type(ProductProjectionSearchRequest::class), Argument::is(null))
            ->will(function($args){return $args[0];})
            ->shouldBeCalledOnce();

        $response = $this->prophesize(PagedSearchResponse::class);
        $response->toArray()->willReturn([])->shouldBeCalled();
        $response->getContext()->willReturn(null)->shouldBeCalled();
        $response->isError()->willReturn(false)->shouldBeCalled();
        $response->getFacets()->willReturn(null)->shouldBeCalledOnce();
        $response->getOffset()->willReturn(null)->shouldBeCalledOnce();
        $response->getTotal()->willReturn(null)->shouldBeCalledOnce();

        $this->client->execute(
            Argument::that(function(ProductProjectionSearchRequest $request){
                static::assertContains('text.en=searchTerm', (string)$request->httpRequest()->getBody());
                static::assertContains('fuzzy=true', (string)$request->httpRequest()->getBody());
                static::assertContains('filter=foo%3A%22bar%22', (string)$request->httpRequest()->getBody());
                static::assertContains('filter.query=query%3A%22queryValue%22', (string)$request->httpRequest()->getBody());
                static::assertContains('filter.facets=facet%3A%22facetValue%22', (string)$request->httpRequest()->getBody());

                return true;
            }),
            Argument::is(null)
        )->willReturn($response->reveal())->shouldBeCalledOnce();

        $uri = $this->prophesize(UriInterface::class);

        $filter = [
            'filter' => [Filter::ofName('foo')->setValue('bar')],
            'filter.query' => [Filter::ofName('query')->setValue('queryValue')],
            'filter.facets' => [Filter::ofName('facet')->setValue('facetValue')]
        ];

        $repository = $this->getCatalogRepository();
        $repository->getProducts('en', 5, 1, 'id desc', 'EUR', 'DE',
            $uri->reveal(), 'searchTerm', $filter);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage unknown filter type provided
     */
    public function testGetProductsWithWrongFilter()
    {
        $this->search->getSelectedValues(Argument::type(UriInterface::class))
            ->willReturn(null)
            ->shouldBeCalledOnce();

        $this->search->addFacets(Argument::type(ProductProjectionSearchRequest::class), Argument::is(null))
            ->will(function($args){return $args[0];})
            ->shouldBeCalledOnce();

        $uri = $this->prophesize(UriInterface::class);

        $filter = ['foo' => ['bar']];
        $repository = $this->getCatalogRepository();
        $repository->getProducts('en', 5, 1, 'id desc', 'EUR', 'DE',
            $uri->reveal(), 'searchTerm', $filter);
    }

    public function testGetProductTypes()
    {
        $this->client->execute(
            Argument::type(ProductTypeQueryRequest::class),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getCatalogRepository();
        $repository->getProductTypes('en');
    }

    public function testGetCategories()
    {
        $this->client->execute(
            Argument::type(CategoryQueryRequest::class),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getCatalogRepository();
        $repository->getCategories('en');
    }

    public function testUpdateProduct()
    {
        $this->client->execute(
            Argument::that(function(ProductUpdateRequest $request) {
                $action = current($request->getActions());

                static::assertSame(Product::class, $request->getResultClass());
                static::assertInstanceOf(ProductSetKeyAction::class, $action);
                static::assertSame('foobar', $action->getKey());
                static::assertSame('product-1', $request->getId());
                static::assertSame(1, $request->getVersion());
                static::assertSame(
                    'products/product-1?foo=bar',
                    (string)$request->httpRequest()->getUri()
                );

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $params = QueryParams::of()->add('foo', 'bar');

        $repository= $this->getCatalogRepository();
        $repository->update(Product::of()->setId('product-1')->setVersion(1), [
            ProductSetKeyAction::of()->setKey('foobar')
        ], $params);
    }

    public function testCreateProduct()
    {
        $this->client->execute(
            Argument::that(function (ProductCreateRequest $request) {
                static::assertInstanceOf(ProductDraft::class, $request->getObject());
                static::assertInstanceOf(ProductTypeReference::class, $request->getObject()->getProductType());
                static::assertSame('productType-1', $request->getObject()->getProductType()->getId());
                static::assertInstanceOf(LocalizedString::class, $request->getObject()->getName());
                static::assertSame(['en' => 'foo'], $request->getObject()->getName()->toArray());
                static::assertInstanceOf(LocalizedString::class, $request->getObject()->getSlug());
                static::assertSame(['en' => 'bar'], $request->getObject()->getSlug()->toArray());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $productType = ProductTypeReference::ofId('productType-1');

        $repository = $this->getCatalogRepository();
        $repository->createProduct('en', $productType, 'foo', 'bar');
    }

    public function testCreateProductType()
    {
        $this->client->execute(
            Argument::that(function (ProductTypeCreateRequest $request) {
                static::assertInstanceOf(ProductTypeDraft::class, $request->getObject());
                static::assertSame('foo', $request->getObject()->getName());
                static::assertSame('bar', $request->getObject()->getDescription());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getCatalogRepository();
        $repository->createProductType('en', 'foo', 'bar');
    }
}
