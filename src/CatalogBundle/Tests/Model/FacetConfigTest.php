<?php
/**
 *
 */

namespace Commercetools\Symfony\CatalogBundle\Tests\Model;


use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Product\FacetRangeCollection;
use Commercetools\Core\Model\Product\Search\FilterRange;
use Commercetools\Core\Model\Product\Search\FilterRangeCollection;
use Commercetools\Symfony\CatalogBundle\Model\FacetConfig;
use PHPUnit\Framework\TestCase;

class FacetConfigTest extends TestCase
{
    private $facet;

    public function setUp()
    {
        $this->facet = new FacetConfig('foo');
    }

    public function testGetNameAndDefaultValues()
    {
        $this->assertSame('foo', $this->facet->getName());
        $this->assertSame('foo', $this->facet->getParamName());
        $this->assertSame('foo', $this->facet->getField());
        $this->assertSame('foo', $this->facet->getAlias());
        $this->assertSame(false, $this->facet->isHierarchical());
        $this->assertSame(true, $this->facet->isMultiselect());
        $this->assertSame(null, $this->facet->getType());
        $this->assertSame(null, $this->facet->getDisplay());
        $this->assertSame(null, $this->facet->getRanges());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Facet type not configured for facet foo
     */
    public function testGetFacetFieldWithError()
    {
        $this->facet->getFacetField();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Facet type not configured for facet foo
     */
    public function testGetFilterFieldWithError()
    {
        $this->facet->getFilterField();
    }

    public function testSetRanges()
    {
        $ranges = [
            ['from' => '01-02', 'to' => '03-04'],
            ['from' => '05-06', 'to' => '*']
        ];

        $this->facet->setRanges($ranges);

        $rangeA = FilterRange::ofFromAndTo('01-02', '03-04');
        $rangeB = FilterRange::ofFrom('05-06');

        $rangeCollection = FilterRangeCollection::of()->add($rangeA)->add($rangeB);
        $this->assertEquals($rangeCollection, $this->facet->getRanges());
    }
}
