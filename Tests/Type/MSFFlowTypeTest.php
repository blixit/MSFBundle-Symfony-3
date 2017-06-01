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
use Blixit\MSFBundle\Exception\MSFConfigurationNotFoundException;
use Blixit\MSFBundle\Exception\MSFTransitionBadReturnTypeException;
use Blixit\MSFBundle\Form\Type\MSFFlowType;
use MyProject\Proxies\__CG__\stdClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\Routing\Router;

use JMS\Serializer\Serializer;

class EntityTest{
    private $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}

class FakeTypeFlow
    extends MSFFlowType{

    public function configure()
    {
        return [
            '__root' => 'FAKEROOT',

            'fake_state'=>[
                'entity' => EntityTest::class
            ],
            'stateToTest'=>[
                'entity' => EntityTest::class
            ],
        ];
    }
}

class MSFFlowTypeTest extends WebTestCase
{
    /**
     * @var MSFService
     */
    private $msf;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var FakeTypeFlow
     */
    private $faketype;

    private $expected;
    private $client;

    private function mockCreateMsf(){
        $this->msf
            ->method('create')
            ->with(FakeTypeFlow::class)
            ->willReturn(new FakeTypeFlow($this->msf,'fake_state'));
    }

    /**
     * Mock all dependencies
     * @param $client
     */
    private function mockDependencies($client){

        $request = $client->getRequest();
        $session = $client->getRequest()->getSession();

        $requestStack = $this->getMockBuilder(RequestStack::class)->getMock();
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods(array('generate'))
            ->getMock();
        $this->router = $router;


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
        //$serializer->method('serialize')->withAnyParameters()->willReturn([]);
        //$serializer->method('deserialize')->withAnyParameters()->willReturn([]);
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
            ->method('getRouter')
            ->willReturn($this->router);
    }

    private function mockSetSimpleFakeType($createMSF = true){

        $this->mockDependencies($this->client);
        $this->serializer->method('deserialize')->withAnyParameters()->willReturn([]);
        $this->serializer->method('serialize')->willReturn(json_encode([]));
        if($createMSF){
            $this->mockCreateMsf();
            return $this->msf->create(FakeTypeFlow::class,'fake_state');
        }
        return null;
    }
    /**
     * Setup test
     */
    public function setUp() {

        $this->expected = array(
            'fake_stat' => new \stdClass()
        );

        $this->client = $this->createClient();
        $this->client->request('POST','/msfbundle',['parameters']);
    }

    public function testgetCancelRedirectionPage(){
        $this->mockSetSimpleFakeType(false);
        $this->router->method('generate')->with('FAKEROOT')->willReturn("http://point.fr?__msf_nvg=&__msf_state=testing");
        $this->msf
            ->method('getRouter')
            ->willReturn($this->router);

        $this->mockCreateMsf();
        $faketype = $this->msf->create(FakeTypeFlow::class,'fake_state');


        $expectedOnString = "__msf_state";
        $expectedOnString2 = "__msf_nvg";
        $url = $faketype->getCancelRedirectionPage('fake_state');

        $this->assertContains($expectedOnString,$url);
        $this->assertContains($expectedOnString2,$url);

        $expectedOnNonString = $faketype->getConfiguration()['__on_cancel']['redirection'];
        $notAString = 15;
        $url = $faketype->getCancelRedirectionPage($notAString);

        $this->assertSame($expectedOnNonString,$url);

    }

    public function testFoundPages(){
        $faketype = $this->mockSetSimpleFakeType();

        $faketype->setNextPage("newpage");
        $this->assertSame("newpage",$faketype->getNextPage());

        $faketype->setPreviousPage("newpage");
        $this->assertSame("newpage",$faketype->getPreviousPage());

        $faketype->setCancelPage("newpage");
        $this->assertSame("newpage",$faketype->getCancelPage());
    }

    public function testNotFoundPages(){

        $faketype = $this->mockSetSimpleFakeType();
        //delete local configuration to provoke the below errors
        $faketype->setConfigurationWith($faketype->getState(),[]);

        //NEXT
        try{
            $faketype->getNextPage();
        }catch (\Exception $e){
            $this->assertInstanceOf(MSFConfigurationNotFoundException::class,$e);
        }
        try{
            $notAString = 15;
            $faketype->setNextPage($notAString);
        }catch (\Exception $e){
            $this->assertInstanceOf(MSFTransitionBadReturnTypeException::class,$e);
        }

        //Previous
        try{
            $faketype->getPreviousPage();
        }catch (\Exception $e){
            $this->assertInstanceOf(MSFConfigurationNotFoundException::class,$e);
        }
        try{
            $notAString = 15;
            $faketype->setPreviousPage($notAString);
        }catch (\Exception $e){
            $this->assertInstanceOf(MSFTransitionBadReturnTypeException::class,$e);
        }

        //cancel
        try{
            $faketype->getCancelPage();
        }catch (\Exception $e){
            $this->assertInstanceOf(MSFConfigurationNotFoundException::class,$e);
        }
        try{
            $notAString = 15;
            $faketype->setCancelPage($notAString);
        }catch (\Exception $e){
            $this->assertInstanceOf(MSFTransitionBadReturnTypeException::class,$e);
        }

    }

    public function testIsAvailable()
    {
        $this->mockDependencies($this->client);

        $expected = [
            'stateToTest' => json_encode(['name'=>'blixit'])
        ];

        $this->serializer->method('deserialize')->withAnyParameters()->willReturn($expected);
        $this->serializer->method('serialize')->willReturn(json_encode($expected));

        $this->msf
            ->method('getSerializer')
            ->willReturn($this->serializer);
        $this->msf
            ->method('create')
            ->with(FakeTypeFlow::class,'stateToTest')
            ->willReturn(new FakeTypeFlow($this->msf,'stateToTest'));

        $this->faketype = $this->msf->create(FakeTypeFlow::class,'stateToTest');

        $faketype = $this->faketype;
        $faketype->getMsfDataLoader()->setArrayData($expected,$this->serializer);

        $faketype->resetUndeserializedMSFDataLoader();

        $is = $faketype->isAvailable('stateToTest');
        $this->assertTrue($is,"Failed to assert state is available.");

        $is = $faketype->isAvailable('unknown');
        $this->assertFalse($is,"Failed to assert state is not available.");


    }



}
