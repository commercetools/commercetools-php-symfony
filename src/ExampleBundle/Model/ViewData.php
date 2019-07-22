<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Model;

class ViewData implements ArraySerializable
{
    public function toArray()
    {
        $data = get_object_vars($this);
        $data = array_map(
            function ($value) {
                if ($value instanceof ArraySerializable) {
                    return $value->toArray();
                } return $value;
            },
            $data
        );
        return $data;
    }
}
