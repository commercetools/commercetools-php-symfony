<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Logger;


use Commercetools\Symfony\CtpBundle\Logger\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggerTest extends TestCase
{
    public function testEmergency()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->emergency('foo', [])->shouldBeCalledOnce();
        $ctpLogger = new Logger($logger->reveal());
        $ctpLogger->emergency('foo', []);
    }

    public function testAlert()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->alert('foo', [])->shouldBeCalledOnce();
        $ctpLogger = new Logger($logger->reveal());
        $ctpLogger->alert('foo', []);
    }

    public function testCritical()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->critical('foo', [])->shouldBeCalledOnce();
        $ctpLogger = new Logger($logger->reveal());
        $ctpLogger->critical('foo', []);
    }

    public function testError()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error('foo', [])->shouldBeCalledOnce();
        $ctpLogger = new Logger($logger->reveal());
        $ctpLogger->error('foo', []);
    }

    public function testWarning()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->warning('foo', [])->shouldBeCalledOnce();
        $ctpLogger = new Logger($logger->reveal());
        $ctpLogger->warning('foo', []);
    }

    public function testNotice()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice('foo', [])->shouldBeCalledOnce();
        $ctpLogger = new Logger($logger->reveal());
        $ctpLogger->notice('foo', []);
    }

    public function testInfo()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info('foo', [])->shouldBeCalledOnce();
        $ctpLogger = new Logger($logger->reveal());
        $ctpLogger->info('foo', []);
    }

    public function testDebug()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->debug('foo', [])->shouldBeCalledOnce();
        $ctpLogger = new Logger($logger->reveal());
        $ctpLogger->debug('foo', []);
    }

    public function testLog()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->log(1, 'foo', [])->shouldBeCalledOnce();
        $ctpLogger = new Logger($logger->reveal());
        $ctpLogger->log(1, 'foo', []);
    }
}
