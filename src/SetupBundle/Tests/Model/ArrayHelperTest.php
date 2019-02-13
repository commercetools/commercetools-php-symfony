<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Tests\Model;

use Commercetools\Symfony\SetupBundle\Model\ArrayHelper;
use PHPUnit\Framework\TestCase;

class ArrayHelperTest extends TestCase
{
    private $arrayHelper;

    protected function setUp()
    {
        $this->arrayHelper = new ArrayHelper();
    }

    public function testArrayDiffRecursive()
    {
        $array1 = ['foo' => 'bar'];
        $array2 = ['foo' => 'bob'];

        $result1 = $this->arrayHelper->arrayDiffRecursive($array1, $array2);
        $result2 = $this->arrayHelper->arrayDiffRecursive($array2, $array1);

        $this->assertEquals($array1, $result1);
        $this->assertEquals($array2, $result2);
    }

    public function testArrayDiffRecursiveWithMoreLevels()
    {
        $array1 = [
            'foo' => [
                'level1' => [
                    'level2' => 'bar'
                ]
            ],
            'key' => 'value'
        ];

        $array2 = [
            'foo' => [
              'level1' => 'fail',
            ],
            'key' => 'value'

        ];

        $result1 = $this->arrayHelper->arrayDiffRecursive($array1, $array2);
        $result2 = $this->arrayHelper->arrayDiffRecursive($array2, $array1);

        $this->assertEquals([
            'foo' => [
                'level1' => [
                    'level2' => 'bar'
                ],
            ]], $result1);
        $this->assertEquals([
            'foo' => [
                'level1' => 'fail'
            ]], $result2);
    }

    public function testArrayIntersectRecursive()
    {
        $array1 = ['foo' => 'bar'];
        $array2 = ['foo' => 'bob'];

        $result1 = $this->arrayHelper->arrayIntersectRecursive($array1, $array2);
        $result2 = $this->arrayHelper->arrayIntersectRecursive($array2, $array1);

        $this->assertEquals([], $result1);
        $this->assertEquals([], $result2);
    }

    public function testArrayIntersectRecursiveWithMoreLevels()
    {
        $array1 = [
            'foo' => [
                'level1' => [
                    'level2' => 'bar'
                ]
            ],
            'key' => 'value'
        ];

        $array2 = [
            'foo' => [
                'level1' => 'fail',
            ],
            'key' => 'value'

        ];

        $result1 = $this->arrayHelper->arrayIntersectRecursive($array1, $array2);

        $this->assertEquals([
            'key' => 'value'
        ], $result1);
    }


    public function testCamelize()
    {
        $value1 = 'simple_value';
        $value2 = 'Another_Option';
        $value3 = '_STRange_TeXt';

        $this->assertEquals('simpleValue', $this->arrayHelper->camelize($value1));
        $this->assertEquals('anotherOption', $this->arrayHelper->camelize($value2));
        $this->assertEquals('strangeText', $this->arrayHelper->camelize($value3));
    }
}
