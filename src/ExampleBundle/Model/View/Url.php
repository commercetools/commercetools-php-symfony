<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Model\View;

use Commercetools\Symfony\ExampleBundle\Model\ViewData;

class Url extends ViewData
{
    public $text;
    public $url;

    public function __construct($text, $url)
    {
        $this->text = $text;
        $this->url = $url;
    }
}
