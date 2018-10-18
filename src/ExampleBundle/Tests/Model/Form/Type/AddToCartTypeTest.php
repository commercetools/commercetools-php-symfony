<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Model\Form\Type;


use Commercetools\Symfony\ExampleBundle\Entity\ProductToCart;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToCartType;
use Symfony\Component\Form\Test\TypeTestCase;

class AddToCartTypeTest extends TypeTestCase
{
    public function testSubmitValidDataWithVariantId()
    {
        $formData = [
            'productId' => 'foo',
            'variantId' => 'bar-1',
            'quantity' => 2,
            'slug' => '/foo/bar',
        ];

        $productEntity = new ProductToCart();
        $productEntity->setVariantIdText(true);

        $form = $this->factory->create(AddToCartType::class, $productEntity);

        $expectedProductEntity = new ProductToCart();
        $expectedProductEntity
            ->setProductId('foo')
            ->setVariantId('bar-1')
            ->setVariantIdText(true)
            ->setQuantity(2)
            ->setSlug('/foo/bar');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedProductEntity, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }

    }

    public function testSubmitValidDataWithoutVariant()
    {
        $formData = [
            'productId' => 'foo',
            'quantity' => 2,
            'slug' => '/foo/bar'
        ];

        $productEntity = new ProductToCart();
        $productEntity->setAllVariants([
            '1' => 'one',
            '2' => 'two'
        ]);

        $form = $this->factory->create(AddToCartType::class, $productEntity);

        $expectedProductEntity = new ProductToCart();
        $expectedProductEntity
            ->setProductId('foo')
            ->setQuantity(2)
            ->setSlug('/foo/bar')
            ->setAllVariants([
                '1' => 'one',
                '2' => 'two'
            ]);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedProductEntity, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

}
