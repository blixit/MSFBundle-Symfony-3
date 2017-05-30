<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 25/05/17
 * Time: 17:25
 */

namespace Blixit\MSFBundle\Tests\Core;

use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Form\ExampleTypes\MSFRegistrationType;
use Blixit\MSFBundle\Form\Type\MSFBaseType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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
        $kernel = static::createKernel();
        $kernel->boot();

        self::$container = $kernel->getContainer();

        $this->msf = self::$container->get('msf');

    }

    public function testcreate()
    {
        $createResult = $this->msf->create(FakeType::class);
        $createResult2 = $this->msf->create(FakeType::class,'withDefaultStateProvided');

        $this->assertInstanceOf(FakeType::class,$createResult);
        $this->assertInstanceOf(FakeType::class,$createResult2);
    }
}
