<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 01/06/17
 * Time: 18:08
 */

namespace Blixit\MSFBundle\Tests\Entity\Example;

use Blixit\MSFBundle\Entity\Example\Article;

class ArticleTest extends \PHPUnit_Framework_TestCase
{
    public function testAll(){
        $article = new Article();

        $this->assertNull($article->getId());

        $article->setName("expected");
        $this->assertSame("expected",$article->getName());
    }
}
