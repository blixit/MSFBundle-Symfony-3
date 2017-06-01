<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 25/05/17
 * Time: 17:25
 */

namespace Blixit\MSFBundle\Tests\Core;

use Blixit\MSFBundle\Core\MSFService;
use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FakeType{
    function __construct(MSFService $msf, $defaultState='')
    {
    }
}

class MSFServiceTest extends KernelTestCase
{
    static $container;
    /**
     * @var MSFService
     */
    private $msf;

    public function setUp()
    {
        $kernel = $this->createKernel();
        $kernel->boot();

        self::$container = $kernel->getContainer();

        $this->msf = self::$container->get('msf');

    }

    public function testGettersSetters(){

        $any = "any";

        $this->msf->setRequestStack($any);
        $this->assertSame($any, $this->msf->getRequestStack());

        $this->msf->setRouter($any);
        $this->assertSame($any, $this->msf->getRouter());

        $this->msf->setFormFactory($any);
        $this->assertSame($any, $this->msf->getFormFactory());

        $this->msf->setSerializer($any);
        $this->assertSame($any, $this->msf->getSerializer());

        $this->msf->setSession($any);
        $this->assertSame($any, $this->msf->getSession());

        $this->msf->setEntityManager($any);
        $this->assertSame($any, $this->msf->getEntityManager());
    }

    public function testcreate()
    {
        $createResult = $this->msf->create(FakeType::class);
        $createResult2 = $this->msf->create(FakeType::class,'withDefaultStateProvided');

        $this->assertInstanceOf(FakeType::class,$createResult);
        $this->assertInstanceOf(FakeType::class,$createResult2);
    }
}
