<?php

namespace Blixit\MSFBundle\Tests\Controller;

use \Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultMsfControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/msfbundle');

        //$this->assertContains('Hello World', $client->getResponse()->getContent());
    }
}
