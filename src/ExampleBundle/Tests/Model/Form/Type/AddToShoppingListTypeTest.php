<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Model\Form\Type;


use Commercetools\Symfony\ExampleBundle\Entity\ProductToShoppingList;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToShoppingListType;
use Symfony\Component\Form\Test\TypeTestCase;

class AddToShoppingListTypeTest extends TypeTestCase
{
    public function testSubmitValidDataWithVariantId()
    {
        $formData = [
            'productId' => 'foo',
            'variantId' => 'bar-1',
            'shoppingListId' => 'list-1'
        ];

        $productEntity = new ProductToShoppingList();

        $form = $this->factory->create(AddToShoppingListType::class, $productEntity);

        $expectedProductEntity = new ProductToShoppingList();
        $expectedProductEntity
            ->setProductId('foo')
            ->setVariantId('bar-1')
            ->setShoppingListId('list-1');

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
        ];

        $productEntity = new ProductToShoppingList();
        $productEntity->setAvailableShoppingLists([
            '1' => 'one',
            '2' => 'two'
        ]);
        $productEntity->setAllVariants([
            '1' => 'one',
            '2' => 'two'
        ]);

        $form = $this->factory->create(AddToShoppingListType::class, $productEntity);

        $expectedProductEntity = new ProductToShoppingList();
        $expectedProductEntity
            ->setProductId('foo')
            ->setAvailableShoppingLists([
                '1' => 'one',
                '2' => 'two'
            ])
            ->setAllVariants([
                '1' => 'one',
                '2' => 'two'
            ]);;

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
