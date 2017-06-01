<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 01/06/17
 * Time: 18:11
 */

namespace Blixit\MSFBundle\Tests\Entity\Example;

use Blixit\MSFBundle\Entity\Example\Blog;

class BlogTest extends \PHPUnit_Framework_TestCase
{
    public function testAll(){
        $blog = new Blog();

        $this->assertNull($blog->getId());

        $blog->setTheme("expectedTheme");
        $this->assertSame("expectedTheme",$blog->getTheme());

        $blog->setAuthorPseudo("expectedAuthor");
        $this->assertSame("expectedAuthor",$blog->getAuthorPseudo());
    }
}
