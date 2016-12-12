<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 07/11/16
 * Time: 15:54
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Model\Import;

use Commercetools\Symfony\CtpBundle\Model\Import\CsvToJson;

class CsvToJsonTest extends \PHPUnit_Framework_TestCase
{
    public function getData()
    {
        return [
            [
                ['a'],
                [1],
                ['a' => 1],
            ],
            [
                ['a.b'],
                [1],
                ['a' => [ 'b' =>1]],
            ],
            [
                ['a.b.c'],
                ["abc"],
                ['a' => ['b' =>['c'=>"abc"]]],
            ],
            [
                ['a.b.0.c'],
                ["abc"],
                ['a' => ['b' =>[0 => ['c'=>"abc"]]]],
            ],
            [
                ['a.b.c', 'd'],
                [1, 2],
                ['a' => ['b' =>['c'=>1]],'d'=>2]
            ],
            [
                ['d', 'a.b.c'],
                [1, 2],
                ['d' => 1,'a'=> ['b' =>['c'=>2]]]
            ],
            [
                ['a.b.d', 'a.b.c'],
                [1, 2],
                ['a'=> ['b' =>['d'=>1,'c'=>2]]]
            ],
            [
                ['description.en'],
                [''],
                ['description' => []]
            ]
        ];
    }
    /**
     * @dataProvider getData
     */
    public function testTransform($headings, $values, $expected)
    {
        $transformer = new CsvToJson();

        $this->assertEquals($expected, $transformer->transform($values, array_flip($headings)));
    }
}
