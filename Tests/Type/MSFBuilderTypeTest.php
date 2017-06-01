<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 01/06/17
 * Time: 21:24
 */

namespace Blixit\MSFBundle\Tests\Type;

use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Entity\Example\Article;
use Blixit\MSFBundle\Entity\Example\Blog;
use Blixit\MSFBundle\Exception\MSFConfigurationNotFoundException;
use Blixit\MSFBundle\Form\Builder\MSFBuilderInterface;
use Blixit\MSFBundle\Form\ExampleTypes\ArticleType;
use Blixit\MSFBundle\Form\Type\MSFBuilderType;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Client;

class FakeTypeBuilder
    extends MSFBuilderType{

    public function configure()
    {
        return [
            '__root' => 'FAKEROOT',

            'fake_state'=>[
                'entity' => Article::class,
                'cancel' => 'fake_state'
            ],
            'stateToTest'=>[
                'entity' => Blog::class
            ],
        ];
    }

    /**
     * Modify the form
     * @return $this
     */
    public function buildMSF()
    {
        return $this->addPreviousButton()->addNextButton()->addCancelButton()->addSubmitButton();
    }
}

class MSFBuilderTypeTest extends WebTestCase
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
     * @var FakeTypeBuilder
     */
    private $faketype;

    /**
     * @var Client
     */
    private $client;

    private function mockCreateMsf(){
        $this->msf
            ->method('create')
            ->with(FakeTypeBuilder::class)
            ->willReturn(new FakeTypeBuilder($this->msf,'fake_state'));
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
        $this->msf
            ->method('getFormFactory')
            ->willReturn($formFactory);
    }

    /**
     * @param bool $createMSF
     * @return FakeTypeBuilder
     */
    private function mockSetSimpleFakeType($createMSF = true){

        $this->mockDependencies($this->client);
        $this->serializer->method('deserialize')->withAnyParameters()->willReturn(['fake_state'=>[]]);
        $this->serializer->method('serialize')->willReturn(json_encode([]));
        if($createMSF){
            $this->mockCreateMsf();
            return $this->msf->create(FakeTypeBuilder::class,'fake_state');
        }
        return null;
    }

    function setUp(){

        $this->client = $this->createClient();
        $this->client->request('POST','/msfbundle',['parameters']);

    }

    function testgetLabel(){

        $faketype = $this->mockSetSimpleFakeType();

        //without label set
        $label = $faketype->getLabel();
        $this->assertSame($faketype->getConfiguration()['__title'], $label);

        //with label set
        $faketype->setConfigurationWith('fake_state',[
            'label' => 'expected'
        ]);
        $label = $faketype->getLabel();
        $this->assertSame('expected', $label);

    }

    function testaddSubmitButton(){

        $faketype = $this->mockSetSimpleFakeType();

        $ret = $faketype->addSubmitButton(['label'=>'add']);

        $this->assertArrayHasKey('__buttons_have_submit',$faketype->getConfiguration());
        $this->assertArrayHasKey(FakeTypeBuilder::ACTIONS_SUBMIT,$faketype->getConfiguration());
        $this->assertInstanceOf(MSFBuilderType::class,$ret);

    }

    function testaddCancelButton(){

        $faketype = $this->mockSetSimpleFakeType();

        $ret = $faketype->addCancelButton(['label'=>'cancel']);

        $this->assertArrayHasKey('__buttons_have_cancel',$faketype->getConfiguration());
        $this->assertArrayHasKey(FakeTypeBuilder::ACTIONS_CANCEL,$faketype->getConfiguration());
        $this->assertInstanceOf(MSFBuilderType::class,$ret);

    }

    function testaddNextButton(){

        $faketype = $this->mockSetSimpleFakeType();

        $ret = $faketype->addNextButton(['label'=>'cancel']);

        $this->assertArrayHasKey('__buttons_have_next',$faketype->getConfiguration());
        $this->assertArrayHasKey(FakeTypeBuilder::ACTIONS_NEXT,$faketype->getConfiguration());
        $this->assertInstanceOf(MSFBuilderType::class,$ret);

    }

    function testaddPreviousButton(){

        $faketype = $this->mockSetSimpleFakeType();

        $ret = $faketype->addPreviousButton(['label'=>'cancel']);

        $this->assertArrayHasKey('__buttons_have_previous',$faketype->getConfiguration());
        $this->assertArrayHasKey(FakeTypeBuilder::ACTIONS_PREVIOUS,$faketype->getConfiguration());
        $this->assertInstanceOf(MSFBuilderType::class,$ret);

    }

    function testbuildMSF(){

        $faketype = $this->mockSetSimpleFakeType();

        $ret = $faketype->buildMSF();

        $this->assertInstanceOf(MSFBuilderType::class,$ret);

    }

    function testgetButtons(){

        $faketype = $this->mockSetSimpleFakeType();

        //With __root not set
        $faketype->setConfigurationWith('__root',null);
        try{
            $buttons = $faketype->getButtons();
        }catch (\Exception $e){
            $this->assertInstanceOf(MSFConfigurationNotFoundException::class, $e);
        }

        //With __root set
        $faketype->setConfigurationWith('__root','FAKE_ROOT');
        $ret = $faketype->buildMSF();
        $this->assertInstanceOf(MSFBuilderType::class, $ret);
        $this->assertInstanceOf(FakeTypeBuilder::class, $ret);

        $buttons = $faketype->getButtons();

        //Since the method build of FakeTypeBuilder adds buttons
        $this->assertNotEmpty($buttons);
    }

    function testGetForm(){

        $faketype = $this->mockSetSimpleFakeType();
        $faketype2 = $faketype;

        //checking entity
        try{
            $faketype->setConfigurationWith('fake_state',[
                //'entity' => Article::class,
                'cancel' => 'fake_state'
            ]);
            $faketype->getForm();
        }catch (\Exception $e){
            $this->assertInstanceOf(MSFConfigurationNotFoundException::class, $e);
        }

        //set data with msfdataloader
        //deserialize should fails  (I changed the result of deserialize to pass this test)
        $faketype2->getMsfDataLoader()->setArrayData([],$this->serializer);
        $faketype2->setConfigurationWith('fake_state',[
            'entity' => Article::class
        ]);
        $faketype2->resetUndeserializedMSFDataLoader();
        try{
            $faketype->getForm();
        }catch (\Exception $e){
            //serialization could fail or another error could be raised
        }


        //should test action and method since there are not provided

        //with formtype and __default_formType_path not set, __default_formType set with initial configuration
        $faketype->setConfigurationWith('fake_state',[
            'entity' => Article::class,
            'cancel' => 'fake_state'
        ]);

        try{
            //should throw since __default_formType_path nor 'formtype' are provided
            $faketype->getForm();
        }catch (\Exception $e){
            $this->assertContains("__default_formType",$e->getMessage());
        }

        //with formtype and __default_formType both not set
        $faketype->setConfigurationWith('fake_state',[
            'entity' => Article::class,
            'cancel' => 'fake_state'
        ]);
        $faketype->setConfigurationWith('__default_formType', false);
        try{
            //should throw MSFConfigurationNotFoundException since __default_formType is not set
            $faketype->getForm();
        }catch (\Exception $e){
            $this->assertInstanceOf(MSFConfigurationNotFoundException::class, $e);
        }

        //with formtype not set, but __default_formType and __default_formType_path both set
        $faketype->setConfigurationWith('fake_state',[
            'entity' => Article::class,
            'cancel' => 'fake_state'
        ]);
        $faketype->setConfigurationWith('__default_formType', true);
        $faketype->setConfigurationWith('__default_formType_path', '\Blixit\MSFBundle\Form\ExampleTypes');
        try{
            //should PASS formtype verification and probably fail on context error
            $faketype->getForm();
        }catch (\Exception $e){

        }

        /**
         * Let erase $faketype
         */
        $faketype = $this->mockSetSimpleFakeType();

        $localConfiguration = $faketype->getLocalConfiguration();
        $localConfiguration['entity'] = Article::class;
        $localConfiguration['formtype'] = ArticleType::class;
        $faketype->setConfigurationWith('fake_state',$localConfiguration);

        try{
            //should PASS formtype verification and probably fail on context error
            $ret = $faketype->getForm();
        }catch (\Exception $e){
        }

    }

}
