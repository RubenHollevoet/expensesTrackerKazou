<?php
namespace Tests\AppBundle\Util;

use AppBundle\Entity\Link;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function testName()
    {
        $link = new Link();

        $link->setName('Hello World');

        // assert that your calculator added the numbers correctly!
        $this->assertEquals('Hello World', $link->getName());
    }
}
