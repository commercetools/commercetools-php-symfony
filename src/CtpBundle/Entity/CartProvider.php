<?php
/**
 * it's not in use anywhere
 */

namespace Commercetools\Symfony\CtpBundle\Entity;


class CartProvider
{
    public $name;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
