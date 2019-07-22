<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Model;

class ViewDataCollection implements ArraySerializable, \IteratorAggregate
{
    protected $data = [];

    public function toArray()
    {
        if (count($this->data) == 0) {
            return [];
        }
        return array_map(
            function ($value) {
                if ($value instanceof ArraySerializable) {
                    return $value->toArray();
                } return $value;
            },
            $this->data
        );
    }

    public function getAt($key)
    {
        if (!isset($this->data[$key])) {
            return null;
        }
        return $this->data[$key];
    }

    public function add($value, $key = null)
    {
        if (is_null($key)) {
            $this->data[] = $value;
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}
