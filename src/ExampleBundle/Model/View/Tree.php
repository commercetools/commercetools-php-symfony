<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Model\View;

use Commercetools\Symfony\ExampleBundle\Model\ViewDataCollection;

class Tree extends Url
{
    /**
     * @var ViewDataCollection
     */
    protected $children;

    public function __construct($text, $url)
    {
        parent::__construct($text, $url);
        $this->children = new ViewDataCollection();
    }

    public function addNode(Url $url)
    {
        $this->children->add($url);
    }
}
