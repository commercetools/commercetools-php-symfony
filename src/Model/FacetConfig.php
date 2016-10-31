<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Model;

class FacetConfig
{
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
     * @var bool
     */
    private $display;

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
                case 'text':
                    $this->facetField = sprintf('variants.attributes.%s', $this->getField());
                    break;
                case 'enum':
                    $this->facetField = sprintf('variants.attributes.%s.key', $this->getField());
                    break;
                case 'categories':
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
                case 'text':
                    $this->filterField = sprintf('variants.attributes.%s', $this->getField());
                    break;
                case 'enum':
                    $this->filterField = sprintf('variants.attributes.%s.key', $this->getField());
                    break;
                case 'categories':
                    $this->filterField = 'categories.id';
                    break;                    
                default:
                    throw new \InvalidArgumentException(sprintf('Facet type not configured for facet %s', $this->getField()));
            }
        }

        return $this->filterField;
    }

    /**
     * @return null
     */
    public function getAlias()
    {
        if (is_null($this->alias)) {
            $this->alias = $this->name;
        }
        return $this->alias;
    }

    /**
     * @return mixed
     */
    public function getHierarchical()
    {
        return $this->hierarchical;
    }

    /**
     * @param mixed $hierarchical
     */
    public function setHierarchical($hierarchical)
    {
        $this->hierarchical = $hierarchical;
    }

    /**
     * @return mixed
     */
    public function isMultiSelect()
    {
        return $this->multiSelect;
    }

    /**
     * @param mixed $multiSelect
     */
    public function setMultiSelect($multiSelect)
    {
        $this->multiSelect = $multiSelect;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @param mixed $display
     */
    public function setDisplay($display)
    {
        $this->display = $display;
    }
}
