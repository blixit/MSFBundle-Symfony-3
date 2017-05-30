<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 30/05/17
 * Time: 19:28
 */

namespace Blixit\MSFBundle\Tests\Entity;

use Blixit\MSFBundle\Entity\MSFDataLoader;
use JMS\Serializer\Serializer;

class MSFDataLoaderTest extends \PHPUnit_Framework_TestCase
{
    private  $serializer;
    
    protected function setUp()
    {
        $this->serializer = $this->getMockBuilder(Serializer::class)->disableOriginalConstructor()->getMock();
    }

    public function testgetArrayData(){

        $this->serializer->method('deserialize')->withAnyParameters()->willReturn([
            'key' => 'to test'
        ]);

        $msfdl = new MSFDataLoader();
        $array = $msfdl->getArrayData($this->serializer);

        $this->assertArrayHasKey('key',$array,"getArrayData failed.");
    }

    public function testsetArrayData(){

        $this->serializer->method('serialize')->withAnyParameters()->willReturn(json_encode([
            'key' => 'to test'
        ]));

        $msfdl = new MSFDataLoader();

        $msfdl->setArrayData([], $this->serializer);

        $arrayString = $msfdl->getData();
        //$msfdl->setObjectData(null,$this->serializer);

        $this->assertTrue(is_string($arrayString),"getArrayData failed.");
    }
}
