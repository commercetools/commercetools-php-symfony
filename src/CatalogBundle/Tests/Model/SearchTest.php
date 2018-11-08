<?php
/**
 *
 */

namespace Commercetools\Symfony\CatalogBundle\Tests\Model;


use Commercetools\Core\Model\Product\Search\Facet;
use Commercetools\Core\Model\Product\Search\Filter;
use Commercetools\Core\Request\Products\ProductProjectionSearchRequest;
use Commercetools\Symfony\CatalogBundle\Model\FacetConfig;
use Commercetools\Symfony\CatalogBundle\Model\Search;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\UriInterface;

class SearchTest extends TestCase
{
    private $search;

    public function setUp()
    {
        $this->search = new Search([
            'fooSearch' => [
                'paramName' => null,
                'field' => null,
                'facetField' => null,
                'filterField' => null,
                'alias' => null,
                'type' => FacetConfig::TYPE_TEXT,
                'multiSelect' => false,
                'hierarchical' => false,
                'display' => false,
                'ranges' => false,
            ]
        ]);
    }

    public function testGetFacetConfigs()
    {
        $facetConfigsArray = $this->search->getFacetConfigs();
        $this->assertArrayHasKey('fooSearch', $facetConfigsArray);
        $this->assertInstanceOf(FacetConfig::class, $facetConfigsArray['fooSearch']);
    }

    public function testSelectedValues()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getQuery()->willReturn('fooSearch=bar&random=value')->shouldBeCalledOnce();

        $selected = $this->search->getSelectedValues($uri->reveal());
        $this->assertCount(1, $selected);
        $this->assertArrayHasKey('fooSearch', $selected);
        $this->assertSame('bar', $selected['fooSearch']);
    }

    public function testAddFacets()
    {
        $request = $this->prophesize(ProductProjectionSearchRequest::class);
        $request->addFacet(Argument::type(Facet::class))->shouldBeCalledOnce();
        $request->addFilterQuery(Argument::type(Filter::class))->shouldBeCalledOnce();

        $selectedValues = [
            'facet-1' => 'foo',
            'fooSearch' => 'bar'
        ];

        $this->search->addFacets($request->reveal(), $selectedValues);
    }

    public function testAddFacetsWithRangeTypeAndMultiSelect()
    {
        $this->search = new Search([
            'fooSearch' => [
                'paramName' => null,
                'field' => null,
                'facetField' => null,
                'filterField' => null,
                'alias' => null,
                'type' => FacetConfig::TYPE_RANGE,
                'multiSelect' => true,
                'hierarchical' => false,
                'display' => false,
                'ranges' => false,
            ]
        ]);

        $request = $this->prophesize(ProductProjectionSearchRequest::class);
        $request->addFacet(Argument::type(Facet::class))->shouldBeCalledOnce();
        $request->addFilterQuery(Argument::type(Filter::class))->shouldNotBeCalled();
        $request->addFilter(Argument::type(Filter::class))->shouldBeCalledOnce();
        $request->addFilterFacets(Argument::type(Filter::class))->shouldBeCalledOnce();

        $selectedValues = [
            'facet-1' => 'foo',
            'fooSearch' => 'bar'
        ];

        $this->search->addFacets($request->reveal(), $selectedValues);
    }

    public function testAddFacetsHierarchicalAndMultiSelect()
    {
        $this->search = new Search([
            'fooSearch' => [
                'paramName' => null,
                'field' => null,
                'facetField' => null,
                'filterField' => null,
                'alias' => null,
                'type' => FacetConfig::TYPE_TEXT,
                'multiSelect' => true,
                'hierarchical' => true,
                'display' => false,
                'ranges' => false,
            ]
        ]);

        $request = $this->prophesize(ProductProjectionSearchRequest::class);
        $request->addFacet(Argument::type(Facet::class))->shouldBeCalledOnce();
        $request->addFilterQuery(Argument::type(Filter::class))->shouldNotBeCalled();
        $request->addFilter(Argument::type(Filter::class))->shouldBeCalledOnce();
        $request->addFilterFacets(Argument::type(Filter::class))->shouldBeCalledOnce();

        $selectedValues = [
            'facet-1' => 'foo',
            'fooSearch' => 'bar'
        ];

        $this->search->addFacets($request->reveal(), $selectedValues);
    }

}
