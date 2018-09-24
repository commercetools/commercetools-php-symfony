<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Model;


use Commercetools\Core\Request\Query\Parameter;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use PHPUnit\Framework\TestCase;

class QueryParamsTest extends TestCase
{
    public function testQueryParams()
    {
        $queryParams = new QueryParams();
        $param = new Parameter('foo', 'bar');

        $queryParams->addParamObject($param);
        $queryParams->add('param-name', 'param-value');

        $this->assertCount(2, $queryParams->getParams());

        foreach ($queryParams->getParams() as $param) {
            $this->assertInstanceOf(Parameter::class, $param);
        }

        $first = current($queryParams->getParams());
        $this->assertSame('foo', $first->getId());
        $this->assertSame('bar', $first->getValue());
    }

}
