<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Model\Form\Type;


use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToShoppingListType;
use Symfony\Component\Form\Test\TypeTestCase;

class AddToShoppingListTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
//        $this->markTestIncomplete();

        $formData = [
            '_productId' => 'foo',
            'shopping_lists' => ['list-1'],
            'data' => [
                'shopping_lists' => ['list-1']
            ],
            '_variantId' => 'variant-1',
            '_shoppingListId' => 'shopping-list-1'
        ];

        $form = $this->factory->create(AddToShoppingListType::class, $formData);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData());

        dump($form->getData());

        $view = $form->createView();
        $children = $view->children;

        dump(array_keys($children));

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

}
