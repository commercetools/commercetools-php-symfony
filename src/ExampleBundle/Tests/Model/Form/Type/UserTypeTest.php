<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Model\Form\Type;


use Commercetools\Symfony\ExampleBundle\Entity\UserDetails;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\UserType;
use Symfony\Component\Form\Test\TypeTestCase;

class UserTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $formData = [
            'firstName' => 'foo',
            'lastName' => 'bar',
            'email' => 'user@localhost',
            'currentPassword' => 'passw0rt',
            'newPassword' => [
                'first_options' => 'foobar',
                'second_options' => 'foobar'
            ]
        ];

        $userDetails = new UserDetails();

        $form = $this->factory->create(UserType::class, $userDetails);

        $expectedUserDetails = new UserDetails();
        $expectedUserDetails
            ->setFirstName('foo')
            ->setLastName('bar')
            ->setEmail('user@localhost')
            ->setCurrentPassword('passw0rt');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedUserDetails, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

}
