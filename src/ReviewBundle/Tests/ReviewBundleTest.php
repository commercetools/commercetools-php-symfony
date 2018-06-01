<?php
/**
 *
 */

namespace Commercetools\Symfony\ReviewBundle\Tests;

use Commercetools\Symfony\ReviewBundle\DependencyInjection\ReviewExtension;
use Commercetools\Symfony\ReviewBundle\ReviewBundle;
use PHPUnit\Framework\TestCase;

class ReviewBundleTest extends TestCase
{
    public function testGetContainerExtension()
    {
        $reviewBundle = new ReviewBundle();
        $this->assertInstanceOf(ReviewExtension::class, $reviewBundle->getContainerExtension());
    }
}
