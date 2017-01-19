<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 17/11/16
 * Time: 14:26
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;


use Commercetools\Core\Model\Common\AbstractJsonDeserializeObject;
use Commercetools\Core\Model\Common\JsonDeserializeInterface;
use Commercetools\Core\Model\Common\Price;

abstract class AbstractRequestBuilder
{
    public function arrayDiffRecursive(array $arr1, array $arr2)
    {
        $outputDiff = [];

        foreach ($arr1 as $key => $value) {
            //if the key exists in the second array, recursively call this function
            //if it is an array, otherwise check if the value is in arr2
            if (isset($arr2[$key]) || array_key_exists($key, $arr2)) {
                if (is_array($value)) {
                    $arr2Value = $arr2[$key];
                    $recursiveDiff = $this->arrayDiffRecursive($value, $arr2Value);

                    if (!empty($recursiveDiff)) {
                        $outputDiff[$key] = $recursiveDiff;
                    }
                } elseif ($value != $arr2[$key]) {
                    $outputDiff[$key] = $value;
                }
            } else {
                $outputDiff[$key] = $value;
            }
        }

        return $outputDiff;
    }
    public function arrayIntersectRecursive(array $arr1, array $arr2)
    {
        $outputIntersect = [];

        foreach ($arr1 as $key => $value) {
            if (isset($arr2[$key]) || array_key_exists($key, $arr2)) {
                if (is_array($value)) {
                    $arr2Value = $arr2[$key];
                    $intersect = $this->arrayIntersectRecursive($value, $arr2Value);
                    if (!empty($intersect)) {
                        $outputIntersect[$key] = $intersect;
                    }
                } elseif ($value == $arr2[$key]) {
                    $outputIntersect[$key] = $value;
                }
            }
        }

        return $outputIntersect;
    }
}
