<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Model\Form\Type;


use Commercetools\Symfony\ExampleBundle\Entity\CartEntity;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToCartType;
use Symfony\Component\Form\Test\TypeTestCase;

class AddToCartTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $this->markTestIncomplete();

        $formData = [
            'shippingAddress' => 'foo',
            'billingAddress' => 'bar',
            'name' => 'foobar',
            'check' => true
        ];

        $cartEntity = new CartEntity();

        $form = $this->factory->create(AddToCartType::class, $cartEntity);

        $expectedCartEntity = new CartEntity();
        $expectedCartEntity
            ->setCheck(true)
            ->setName('foobar')
            ->setBillingAddress('bar')
            ->setShippingAddress('foo');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedCartEntity, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }

    }

}
