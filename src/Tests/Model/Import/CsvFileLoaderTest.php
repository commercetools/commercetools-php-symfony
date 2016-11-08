<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 07/11/16
 * Time: 16:55
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Model\Import;


use Commercetools\Symfony\CtpBundle\Model\Import\CsvFileLoader;
use Commercetools\Symfony\CtpBundle\Model\Import\CsvToJson;


class CsvFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadComma()
    {
        $transformer = new CsvToJson();
        $csvFileLoader = new CsvFileLoader($transformer);
        $csvFileLoader->setCsvControl(',');

        $data = $csvFileLoader->load(__DIR__ . '/../../fixtures/test-comma.csv');

        $this->assertEquals(['a' => ['c'=>1], 'b' => 2, 'c' => 3], $data->current());
    }

    public function testLoadSemicolon()
    {
        $transformer = new CsvToJson();
        $csvFileLoader = new CsvFileLoader($transformer);
        $csvFileLoader->setCsvControl(';');

        $data = $csvFileLoader->load(__DIR__ . '/../../fixtures/test-semicolon.csv');

        $this->assertEquals(['a' => 1, 'b' => [0=>2]], $data->current());
    }
}
