<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 08/11/16
 * Time: 13:33
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;


class JsonFileLoader
{
    public function load($fileName)
    {
        $fileContent = file_get_contents($fileName);
        $fileContentAsArray = json_decode($fileContent, true);
        return $fileContentAsArray;
    }
}
