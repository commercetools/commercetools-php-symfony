<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Model\Form\Type;

use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddReviewType;
use Symfony\Component\Form\Test\TypeTestCase;

class AddReviewTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $formData = [
            'text' => 'foo',
            'rating' => 5
        ];

        $form = $this->factory->create(AddReviewType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
