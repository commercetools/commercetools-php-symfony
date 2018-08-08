<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Model;


use Commercetools\Core\Model\State\StateCollection;
use Commercetools\Symfony\StateBundle\Model\ProcessStates;
use PHPUnit\Framework\TestCase;

class ProcessStatesTest extends TestCase
{
    private $states;

    public function setUp()
    {
        $base64 = '';
        $this->states = unserialize(base64_decode($base64));
    }

    public function testParse()
    {
        $this->assertInstanceOf(StateCollection::class, $this->states);

        $helper = ProcessStates::of();
        $res = $helper->parse($this->states);

        dump($res);
    }
}
