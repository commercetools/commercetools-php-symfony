<?php
/**
 */

namespace Commercetools\Symfony\SetupBundle\Model;

class ArrayHelper
{
    public function arrayDiffRecursive(array $arr1, array $arr2)
    {
        $outputDiff = [];

        foreach ($arr1 as $key => $value) {
            if (isset($arr2[$key]) || array_key_exists($key, $arr2)) {
                if (is_array($value) && is_array($arr2[$key])) {
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
                if (is_array($value) && is_array($arr2[$key])) {
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

    public function arrayCamelizeKeys(array $arr)
    {
        foreach ($arr as $key => $value){
            if(is_string($key)){
                $fixedKey = $this->camelize($key);
            } else {
                $fixedKey = $key;
            }

            if (is_array($value)) {
                $value = $this->arrayCamelizeKeys($value);
            }

            if ($key !== $fixedKey){
                $arr[$fixedKey] = $value;
                unset($arr[$key]);
            } else {
                $arr[$key] = $value;
            }
        }

        return $arr;
    }

    public function camelize($scored)
    {
        return lcfirst(
            implode(
                '',
                array_map(
                    'ucfirst',
                    array_map(
                        'strtolower',
                        explode('_', $scored)
                    )
                )
            )
        );
    }

    public function crossDiffRecursive(array $arr1, array $arr2)
    {
        $changed1 = $this->arrayDiffRecursive($arr1, $arr2);
        $changed2 = $this->arrayDiffRecursive($arr2, $arr1);

        return array_unique(array_merge(array_keys($changed1), array_keys($changed2)));
    }
}
