<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 25/05/17
 * Time: 18:27
 */

namespace Blixit\MSFBundle\Tests\Form\Type;


use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Exception\MSFConfigurationNotFoundException;
use Blixit\MSFBundle\Exception\MSFRedirectException;
use Blixit\MSFBundle\Exception\MSFStateUnreachableException;
use Blixit\MSFBundle\Exception\MSFTransitionBadReturnTypeException;
use Blixit\MSFBundle\Form\Type\MSFFlowType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\HttpKernel\Client;
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
                'entity' => EntityTest::class,
                'cancel' => 'fake_state'
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

    /**
     * @var Client
     */
    private $client;

    private $expected;

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

    /**
     * @param bool $createMSF
     * @return FakeTypeFlow
     */
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

    private function buildConstructorTest(){
        $this->mockDependencies($this->client);

        $this->serializer->method('deserialize')->withAnyParameters()->willReturn([]);
        $this->serializer->method('serialize')->willReturn(json_encode([]));

        $expectedOnDefaultRoute = "http://fakeroute.fr";
        $this->router->method('generate')->withAnyParameters()->willReturn($expectedOnDefaultRoute);
        $this->msf
            ->method('getRouter')
            ->willReturn($this->router);
    }

    public function testConstructorWithoutCancel(){

        $this->client = $this->createClient();
        $this->client->request('POST','/msfbundle',[
            '__msf_nvg'=>''
        ]);

        $this->buildConstructorTest();
        try{
            //will throw MSFStateUnreachableException since the destination state (__msf_state) weren't provided along with the navigation parameter (__msf_nvg)
            $this->mockCreateMsf();

            $faketype = $this->msf->create(FakeTypeFlow::class,'fake_state');
        }catch (\Exception $e){
            $this->assertInstanceOf(MSFStateUnreachableException::class,$e);
        }
    }

    public function testConstructorWithCancel(){

        $this->client = $this->createClient();
        $this->client->request('POST','/msfbundle',[
            '__msf_cncl'=>'fake_state'
        ]);

        $this->buildConstructorTest();
        try{
            //should throw MSFRedirectException since the cancel parameter (__msf_cncl)
            $this->mockCreateMsf();

            $faketype = $this->msf->create(FakeTypeFlow::class,'fake_state');
        }catch (\Exception $e){
            $this->assertInstanceOf(MSFRedirectException::class,$e);
        }
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

    public function testgetRouteOrUrl(){
        $this->mockSetSimpleFakeType(false);
        $expectedOnDefaultRoute = "http://route.fr?__destination=__defaultRoute";
        $this->router->method('generate')->with('homepage',array())->willReturn($expectedOnDefaultRoute);
        $this->msf
            ->method('getRouter')
            ->willReturn($this->router);

        $this->mockCreateMsf();
        $faketype = $this->msf->create(FakeTypeFlow::class,'fake_state');

        $expectedUrl = "http://url.com";
        $faketype->setConfigurationWith('__routeAsUrl',$expectedUrl);

        //testing asUrl
        $route = $faketype->getRouteOrUrl('__route');
        $this->assertSame($expectedUrl, $route);

        //testing routename
        $faketype->setConfigurationWith('__defaultRoute','homepage');
        $route = $faketype->getRouteOrUrl('__defaultRoute');
        $this->assertSame($expectedOnDefaultRoute, $route);

        $route = $faketype->getRouteOrUrl('__unknown');
        $this->assertNull($route);
    }

    /**
     * @param array $config
     * @param FakeTypeFlow $faketype
     */
    private function executeInitTransitionsWithErrors(array $config, &$faketype){
        $faketype->setConfigurationWith('state',$config);

        try{
            //will throw an error since the returned states are not available
            $faketype->initTransitions();
        }catch (\Exception $e){

        }
    }

    /**
     * @param array $config
     * @param $transition
     * @param FakeTypeFlow $faketype
     */
    private function executeInitTransitionsWithOutErrors(array $config, $transition, &$faketype){

        $faketype->setConfigurationWith('state',$config);
        $faketype->initTransitions();

        $this->assertSame('state',$faketype->getConfiguration()['state'][$transition]);
    }

    public function testinitTransitions(){
        $faketype = $this->mockSetSimpleFakeType();

        //THROW ERRORS
        $this->executeInitTransitionsWithErrors([
            'after' => function($msfData){
                return 'nextState';
            }
        ],$faketype);

        $this->executeInitTransitionsWithErrors([
            'before' => function($msfData){
                return 'previousState';
            }
        ],$faketype);

        $this->executeInitTransitionsWithErrors([
            'cancel' => function($msfData){
                return 'cancelState';
            }
        ],$faketype);

        //PASS
        $this->executeInitTransitionsWithOutErrors([
            'after' => function($msfData){
                return 'state';
            }
        ],'after',$faketype);

        $this->executeInitTransitionsWithOutErrors([
            'before' => function($msfData){
                return 'state';
            }
        ],'before',$faketype);

        $this->executeInitTransitionsWithOutErrors([
            'cancel' => function($msfData){
                return 'state';
            }
        ],'cancel',$faketype);

    }

    public function testGetMenu(){
        $this->mockSetSimpleFakeType(false);
        $expectedOnDefaultRoute = "http://fakeroute.fr";
        $this->router->method('generate')->withAnyParameters()->willReturn($expectedOnDefaultRoute);
        $this->msf
            ->method('getRouter')
            ->willReturn($this->router);

        $this->mockCreateMsf();
        $faketype = $this->msf->create(FakeTypeFlow::class,'fake_state');

        $menu = $faketype->getMenu('msfform');

        $this->assertCount(count($faketype->getSteps()),$menu);
        foreach ($menu as $k => $item){
            $this->assertArrayHasKey('name',$item);
            $this->assertArrayHasKey('link',$item);
        }
    }

    public function testGetStepsWithLink(){
        $this->mockSetSimpleFakeType(false);
        $expectedRoute = "http://fakeroute.fr";
        $this->router->method('generate')->withAnyParameters()->willReturn($expectedRoute);
        $this->msf
            ->method('getRouter')
            ->willReturn($this->router);

        $this->mockCreateMsf();
        $faketype = $this->msf->create(FakeTypeFlow::class,'fake_state');

        $steps = $faketype->getSteps();

        // ----------------------------- Without buttons ------------------------------------------
        $stepsWithLink = $faketype->getStepsWithLink('msfform',[]);
        $this->assertCount(count($steps),$stepsWithLink);

        // ----------------------------- With buttons ------------------------------------------

        //Transitions not defined, Without default_path
        $stepsWithLink = $faketype->getStepsWithLink('msfform',[],true);

        foreach ($stepsWithLink as $k => $item){
            if(is_null($faketype->getConfiguration()[$k]['before']))
                $this->assertSame('#',$item['linkbefore']);
            if(is_null($faketype->getConfiguration()[$k]['after']))
                $this->assertSame('#',$item['linkafter']);
            if(is_null($faketype->getConfiguration()[$k]['cancel']))
                $this->assertSame('#',$item['linkcancel']);
        }

        //Transitions not defined, With default_path
        $faketype->setConfigurationWith('__default_paths',true);
        $faketype->setConfigurationWith('__root','expectedRoot');
        $faketype->setConfigurationWith('__final_redirection','expectedFRedirection');
        $faketype->setConfigurationWith('__on_cancel',[
            'redirection'   =>  'expectedCancelRedirection'
        ]);

        $stepsWithLink = $faketype->getStepsWithLink('msfform',[],true);

        foreach ($stepsWithLink as $k => $item){
            if(is_null($faketype->getConfiguration()[$k]['before']))
                $this->assertSame($faketype->getConfiguration()['__root'],$item['linkbefore']);
            if(is_null($faketype->getConfiguration()[$k]['after']))
                $this->assertSame($faketype->getConfiguration()['__final_redirection'],$item['linkafter']);
            if(is_null($faketype->getConfiguration()[$k]['cancel']))
                $this->assertSame($faketype->getCancelRedirectionPage(null),$item['linkcancel']);
        }


        //Transitions defined
        foreach ($steps as $k => $step){
            $faketype->setConfigurationWith($step['name'],[
                'before'    =>  'fake_state',
                'after'    =>  'fake_state',
                'cancel'    =>  'fake_state',
            ]);
        }

        $stepsWithLink = $faketype->getStepsWithLink('msfform',[],true);

        foreach ($stepsWithLink as $k => $item){
            if(! is_null($faketype->getConfiguration()[$k]['before'])){
                $this->assertSame($expectedRoute,$item['linkbefore']);
            }
            if(! is_null($faketype->getConfiguration()[$k]['after']))
                $this->assertSame($expectedRoute,$item['linkafter']);
            if(! is_null($faketype->getConfiguration()[$k]['cancel']))
                $this->assertSame($expectedRoute,$item['linkcancel']);
        }

    }
}
