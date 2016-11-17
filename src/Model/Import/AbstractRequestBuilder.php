<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 17/11/16
 * Time: 14:26
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;


abstract class AbstractRequestBuilder
{
    public function arrayDiffRecursive($arr1, $arr2)
    {
        $outputDiff = [];

        foreach ($arr1 as $key => $value) {
            //if the key exists in the second array, recursively call this function
            //if it is an array, otherwise check if the value is in arr2
            if (array_key_exists($key, $arr2)) {
                if (is_array($value)) {
                    $recursiveDiff = $this->arrayDiffRecursive($value, $arr2[$key]);

                    if (count($recursiveDiff)) {
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
    public function arrayIntersectRecursive($arr1, $arr2)
    {
        $outputIntersect = [];

        foreach ($arr1 as $key => $value) {
            if (array_key_exists($key, $arr2)) {
                if (is_array($value)) {
                    $intersect = $this->arrayIntersectRecursive($value, $arr2[$key]);

                    if (count($intersect)) {
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
