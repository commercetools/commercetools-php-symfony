<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Model;


use Commercetools\Core\Model\Type\TypeCollection;

class ProcessCustomTypes
{
    public function parse(TypeCollection $typeCollection)
    {
        return [
            'setup' => [
                'custom_types' => $this->parseTypes($typeCollection)
            ]
        ];
    }

    private function parseTypes($typeCollection)
    {
        $types = [];

        foreach ($typeCollection as $type) {
            $types[$type->key] = [
                'name' => $type->name->en,
                'id' => $type->id
            ];
        }

        return $types;
    }

    public static function of()
    {
        return new static();
    }
}
