<?php
/**
 *
 */

namespace Commercetools\Symfony\CatalogBundle\Tests\Model;

use Commercetools\Core\Error\InvalidArgumentException;
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

    public function testHierarchical()
    {
        $this->facet->setHierarchical(true);
        $this->assertSame(true, $this->facet->isHierarchical());
    }

    public function testMultiSelect()
    {
        $this->facet->setMultiSelect(false);
        $this->assertSame(false, $this->facet->isMultiSelect());
    }

    public function testType()
    {
        $this->facet->setType('foo');
        $this->assertSame('foo', $this->facet->getType());
    }

    public function testDisplay()
    {
        $this->facet->setDisplay('foo');
        $this->assertSame('foo', $this->facet->getDisplay());
    }

    public function testGetFacetFieldForTextType()
    {
        $this->facet->setType(FacetConfig::TYPE_TEXT);
        $this->assertSame('variants.attributes.foo', $this->facet->getFacetField());
    }

    public function testGetFacetFieldForEnumType()
    {
        $this->facet->setType(FacetConfig::TYPE_ENUM);
        $this->assertSame('variants.attributes.foo.key', $this->facet->getFacetField());
    }

    public function testGetFacetFieldForCategoriesType()
    {
        $this->facet->setType(FacetConfig::TYPE_CATEGORIES);
        $this->assertSame('categories.id', $this->facet->getFacetField());
    }

    public function testGetFilterFieldForTextType()
    {
        $this->facet->setType(FacetConfig::TYPE_TEXT);
        $this->assertSame('variants.attributes.foo', $this->facet->getFilterField());
    }

    public function testGetFilterFieldForEnumType()
    {
        $this->facet->setType(FacetConfig::TYPE_ENUM);
        $this->assertSame('variants.attributes.foo.key', $this->facet->getFilterField());
    }

    public function testGetFilterFieldForCategoriesType()
    {
        $this->facet->setType(FacetConfig::TYPE_CATEGORIES);
        $this->assertSame('categories.id', $this->facet->getFilterField());
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
        $from1 = '01-02';
        $to1 = '03-04';
        $from2 = '05-06';
        $ranges = [
            ['from' => $from1, 'to' => $to1],
            ['from' => $from2, 'to' => null]
        ];

        $this->facet->setRanges($ranges);

        $rangeA = FilterRange::ofFromAndTo($from1, $to1);
        $rangeB = FilterRange::ofFromAndTo($from2, null);

        $rangeCollection = FilterRangeCollection::of()->add($rangeA)->add($rangeB);
        $this->assertInstanceOf(FilterRangeCollection::class, $this->facet->getRanges());
        $this->assertEquals($rangeCollection, $this->facet->getRanges());

        $this->facet->setRanges($rangeCollection);
        $this->assertInstanceOf(FilterRangeCollection::class, $this->facet->getRanges());
        $this->assertSame($rangeCollection, $this->facet->getRanges());

        $this->facet->setRanges([$rangeA, $rangeB]);
        $this->assertInstanceOf(FilterRangeCollection::class, $this->facet->getRanges());
        $this->assertEquals($rangeCollection, $this->facet->getRanges());
    }
}
