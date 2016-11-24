<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 07/11/16
 * Time: 12:26
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;


class CsvToJson
{

    public function transform($data, $headings)
    {
        $category = [];
        foreach ($headings as $heading => $column) {
            $headingParts = explode('.', $heading);
            $columnData = isset($data[$column]) ? $data[$column] : '';
            $category = $this->transformData($headingParts, $category, $columnData);
        }

        return $category;
    }

    private function transformData($parts, $context, $data)
    {
        $actualPart = array_shift($parts);

        if (count($parts) > 0) {
            if (!isset($context[$actualPart])) {
                $context[$actualPart] = [];
            }
            $context[$actualPart] = $this->transformData($parts, $context[$actualPart], $data);
        } else {
            $context[$actualPart] = $data;
        }

        return $context;
    }
}
