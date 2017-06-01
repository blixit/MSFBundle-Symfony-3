<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 25/05/17
 * Time: 18:27
 */

namespace Blixit\MSFBundle\Tests\Form\Type;


use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Entity\MSFDataLoader;
use Blixit\MSFBundle\Form\Type\MSFBaseType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\Routing\Router;

use JMS\Serializer\Serializer;

class FakeType
    extends MSFBaseType{

    public function configure()
    {
        return [
            '__root' => 'FAKEROOT',

            'fake_state'=>[
                'entity' => FakeType::class
            ]
        ];
    }
}

class MSFBaseTypeTest extends WebTestCase
{
    /**
     * @var MSFService
     */
    private $msf;

    private $faketype;

    private $serializer;

    public function setUp() {

        $client = $this->createClient();
        $client->request('POST','/msfbundle',['parameters']);





        $request = $client->getRequest();
        $session = $client->getRequest()->getSession();

        $requestStack = $this->getMockBuilder(RequestStack::class)->getMock();
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods(array('create'))
            ->getMock();


        $formFactory = $this->getMockBuilder(FormFactory::class)->disableOriginalConstructor()->getMock();
        $form = $this
            ->getMockBuilder('Symfony\Tests\Component\Form\FormInterface')
            ->setMethods(array('createView'))
            ->getMock()
        ;
        $form
            ->method('createView')
            ->willReturn(null);
        ;
        $formFactory
            ->method('create')
            ->willReturn($form);

        $serializer = $this->getMockBuilder(Serializer::class)->disableOriginalConstructor()->getMock();
        $serializer->method('serialize')->withAnyParameters()->willReturn([]);
        $serializer->method('deserialize')->withAnyParameters()->willReturn([]);
        $this->serializer = $serializer;

        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $this->msf = $this->getMockBuilder(MSFService::class)
            //->disableOriginalConstructor()
                ->setConstructorArgs([$requestStack,$router,$formFactory,$serializer,$entityManager,$session])
            ->getMock();

        $this->msf
            ->method('getRequestStack')
            ->willReturn($requestStack);
        $this->msf
            ->method('getSession')
            ->willReturn($session);
        $this->msf
            ->method('getSerializer')
            ->willReturn($this->serializer);

        $this->msf
            ->method('create')
            ->with(FakeType::class)
            ->willReturn(new FakeType($this->msf,'fake_state'));

        $this->faketype = $this->msf->create(FakeType::class);
    }


    public function testconfigure()
    {
        $faketype = $this->faketype;

        $this->assertTrue($faketype->getConfiguration()['__root'] == 'FAKEROOT', "default configuration for '__root' not erased.");

        if (!$this->msf->getSession()->has('__msf_dataloader')){
            $this->assertTrue($faketype->getMsfDataLoader()->getState() == 'fake_state', "default state not erased");
        }

        $configuration = $faketype->getConfiguration();
        $this->assertArrayHasKey('fake_state',$configuration);

        $this->assertSame($faketype->getMsfDataLoader()->getState(),'fake_state');

    }

    public function testgetLocalConfiguration(){
        $faketype = $this->faketype;

        $localConfiguration = $faketype->getLocalConfiguration();
        $this->assertArrayHasKey('entity',$localConfiguration);

        //test internal variable of getLocalConfiguration
        $this->assertArrayHasKey('entity', $faketype->getLocalConfiguration());

        try {
            $faketype->getMsfDataLoader()->setState('undefined');
            $faketype->resetLocalConfiguration();

            //this should raise an error since 'undefined' state doesn't exist
            $faketype->getLocalConfiguration();
        }catch (\Exception $e){
            $this->assertSame("Trying to access a non configured state",$e->getMessage());
        }


    }

    public function testgetUndeserializedMSFDataLoader(){
        $expected = [
            'fake_stat' => new \stdClass()
        ];

        $this->serializer->method('deserialize')->withAnyParameters()->willReturn($expected);

        $faketype = $this->faketype;

        $undeserialized = $faketype->getUndeserializedMSFDataLoader();
        $faketype->resetUndeserializedMSFDataLoader();

        try{
            //should file since fake_state store a FakeType object
            $faketype->getMsfDataLoader()->setArrayData($expected,$this->msf->getSerializer());

            $undeserialized = $faketype->getUndeserializedMSFDataLoader();
        }catch (\Exception $e){
            $this->assertStringStartsWith("Failed to undeserialize",$e->getMessage());
        }
    }


}
