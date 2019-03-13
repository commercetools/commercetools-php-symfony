<?php
/**
 *
 */

namespace Commercetools\Symfony\ReviewBundle\Tests\DependencyInjection;

use Commercetools\Symfony\ReviewBundle\DependencyInjection\ReviewExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class ReviewExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new ReviewExtension()
        ];
    }

    public function testExtensionLoads()
    {
        $this->load();

        $this->assertContainerBuilderHasService('Commercetools\Symfony\ReviewBundle\Model\Repository\ReviewRepository');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\ReviewBundle\Manager\ReviewManager');
    }
}
