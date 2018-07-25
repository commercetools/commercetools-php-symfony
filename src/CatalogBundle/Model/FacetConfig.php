<?php
/**
 */

namespace Commercetools\Symfony\CatalogBundle\Model;

use Commercetools\Core\Model\Product\Search\FilterRange;
use Commercetools\Core\Model\Product\Search\FilterRangeCollection;

class FacetConfig
{
    const TYPE_TEXT = 'text';
    const TYPE_RANGE = 'range';
    const TYPE_ENUM = 'enum';
    const TYPE_CATEGORIES = 'categories';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $paramName;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $filterField;

    /**
     * @var string
     */
    private $facetField;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var bool
     */
    private $hierarchical = false;

    /**
     * @var bool
     */
    private $multiSelect = true;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $display;

    /**
     * @var FilterRangeCollection
     */
    private $ranges;

    public function __construct(
        $name,
        $paramName = null,
        $field = null,
        $facetField = null,
        $filterField = null,
        $alias = null
    ) {
        $this->name = $name;
        $this->paramName = $paramName;
        $this->field = $field;
        $this->facetField = $facetField;
        $this->filterField = $filterField;
        $this->alias = $alias;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function getParamName()
    {
        if (is_null($this->paramName)) {
            $this->paramName = $this->name;
        }

        return $this->paramName;
    }
    /**
     * @return mixed
     */
    public function getField()
    {
        if (is_null($this->field)) {
            $this->field = $this->name;
        }
        return $this->field;
    }

    public function getFacetField()
    {
        if (is_null($this->facetField)) {
            switch ($this->type) {
                case static::TYPE_RANGE:
                case static::TYPE_TEXT:
                    $this->facetField = sprintf('variants.attributes.%s', $this->getField());
                    break;
                case static::TYPE_ENUM:
                    $this->facetField = sprintf('variants.attributes.%s.key', $this->getField());
                    break;
                case static::TYPE_CATEGORIES:
                    $this->facetField = 'categories.id';
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Facet type not configured for facet %s', $this->name));
            }
        }

        return $this->facetField;
    }

    public function getFilterField()
    {
        if (is_null($this->filterField)) {
            switch ($this->type) {
                case static::TYPE_RANGE:
                case static::TYPE_TEXT:
                    $this->filterField = sprintf('variants.attributes.%s', $this->getField());
                    break;
                case static::TYPE_ENUM:
                    $this->filterField = sprintf('variants.attributes.%s.key', $this->getField());
                    break;
                case static::TYPE_CATEGORIES:
                    $this->filterField = 'categories.id';
                    break;                    
                default:
                    throw new \InvalidArgumentException(sprintf('Facet type not configured for facet %s', $this->getField()));
            }
        }

        return $this->filterField;
    }

    /**
     * @return string|null
     */
    public function getAlias()
    {
        if (is_null($this->alias)) {
            $this->alias = $this->name;
        }
        return $this->alias;
    }

    /**
     * @return boolean
     */
    public function isHierarchical()
    {
        return $this->hierarchical;
    }

    /**
     * @param boolean $hierarchical
     * @return FacetConfig
     */
    public function setHierarchical($hierarchical)
    {
        $this->hierarchical = $hierarchical;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isMultiSelect()
    {
        return $this->multiSelect;
    }

    /**
     * @param boolean $multiSelect
     * @return FacetConfig
     */
    public function setMultiSelect($multiSelect)
    {
        $this->multiSelect = $multiSelect;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return FacetConfig
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @param string $display
     * @return FacetConfig
     */
    public function setDisplay($display)
    {
        $this->display = $display;
        return $this;
    }

    /**
     * @return FilterRangeCollection|array
     */
    public function getRanges()
    {
        return $this->ranges;
    }

    /**
     * @param FilterRangeCollection $ranges
     * @return $this
     */
    public function setRanges($ranges)
    {
        $this->ranges = $this->createRanges($ranges);
        return $this;
    }

    /**
     * @param $ranges
     * @return FilterRangeCollection
     */
    public function createRanges($ranges)
    {
        if ($ranges instanceof FilterRangeCollection) {
            return $ranges;
        }
        $r = FilterRangeCollection::of();
        if (is_array($ranges)) {
            foreach ($ranges as $range) {
                $r->add($this->createRange($range));
            }
        }
        return $r;
    }

    /**
     * @param $range
     * @return FilterRange
     */
    private function createRange($range)
    {
        if ($range instanceof FilterRange) {
            return $range;
        }
        $rangeObject = FilterRange::of();
        if (is_array($range)) {
            if (isset($range['from']) && $range['from'] != '*') {
                $rangeObject->setFrom($range['from']);
            }
            if (isset($range['to']) && $range['to'] != '*') {
                $rangeObject->setTo($range['to']);
            }

        }
        return $rangeObject;
    }
}
