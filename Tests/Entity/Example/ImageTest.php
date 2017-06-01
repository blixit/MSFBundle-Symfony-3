<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 01/06/17
 * Time: 18:13
 */

namespace Blixit\MSFBundle\Tests\Entity\Example;

use Blixit\MSFBundle\Entity\Example\Image;

class ImageTest extends \PHPUnit_Framework_TestCase
{
    public function testAll(){

        $image = new Image();

        $this->assertNull($image->getId());

        $image->setFilename("expectedfile");
        $this->assertSame("expectedfile",$image->getFilename());

        $image->setMimeType("expectedMime");
        $this->assertSame("expectedMime",$image->getMimeType());

        $image->setUrl("expectedUrl");
        $this->assertSame("expectedUrl",$image->getUrl());
    }
}
