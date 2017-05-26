<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 17:29
 */

namespace Blixit\MSFBundle\Form\Type;


use Blixit\MSFBundle\Core\MSFAssistance;
use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Entity\MSFDataLoader;
use Blixit\MSFBundle\Exception\MSFBadStateException;
use Blixit\MSFBundle\Exception\MSFConfigurationNotFoundException;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Form\FormFactory;
use JMS\Serializer\Serializer;

abstract class MSFBaseType
    implements MSFAssistance
{
    /**
     * @var MSFService
     */
    private $msf;

    /**
     * @var string
     */
    private $defaultState;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @var FormInterface
     */
    private $currentForm;

    /**
     * @var MSFDataLoader
     */
    private $msfDataLoader;

    /**
     * This variable contains the deserialized msfDataLoader to avoid several deserialization of it
     * @var array
     */
    private $undeserializedMSFDataloader;

    /**
     * Tells if the query asks for a new state
     * @var bool
     */
    private $isSetNavigation = false;

    /**
     * MSFAbstractType constructor.
     * @param MSFService $msf
     * @param $defaultState
     */
    function __construct(MSFService $msf, $defaultState)
    {
        $this->msf = $msf;
        $this->defaultState = $defaultState;

        //default values
        $this->configuration = [
            '__method'      => 'POST',
            '__default_paths'      => false,
            '__default_formType'      => true,

            /**
             * Routes
             */
            //the default route form
            '__root'                => 'bad_root',
            //the default route on terminate
            //'__final_redirection'   => 'bad_final_redirection',

            /**
             * Events
             */
            '__on_init'      => [
                'load_session'  => true
            ],

            '__on_terminate'      => [
                'destroy_data'  => true,
            ],
            '__on_previous'      => [
                'save'  => true,
            ],
            '__on_cancel'      => [
                'redirection'  => 'bad_cancel_route',
            ],
            /**
             * Labels
             */
            '__title'   => "MSF Form",
        ];

        $this->msfDataLoader = new MSFDataLoader ();
        $this->msfDataLoader->setState($this->defaultState);

        $userConfiguration = $this->configure();
        $this->configuration = array_merge($this->configuration,$userConfiguration);

        //load the session
        if($this->configuration['__on_init']['load_session'])
            $this->init();
    }

    /**
     * Load MSF data from session
     */
    public function init(){
        if($this->getSession()->has('__msf_dataloader')){
            $this->msfDataLoader = $this->getSession()->get('__msf_dataloader');
        }

    }

    /**
     * --------------------------------------------------------------------------------------------
     *          GETTERS AND SETTERS
     * --------------------------------------------------------------------------------------------
     */

    /**
     * @return MSFService
     */
    public function getMsf()
    {
        return $this->msf;
    }

    /**
     * @return string
     */
    public function getDefaultState()
    {
        return $this->defaultState;
    }

    /**
     * @param string $defaultState
     */
    public function setDefaultState($defaultState)
    {
        $this->defaultState = $defaultState;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param array $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }
    public function setConfigurationWith($key, $value)
    {
        $this->configuration[$key] = $value;
    }

    /**
     * @return FormInterface
     */
    public function getCurrentForm()
    {
        return $this->currentForm;
    }

    /**
     * @param FormInterface $currentForm
     */
    public function setCurrentForm($currentForm)
    {
        $this->currentForm = $currentForm;
    }

    /**
     * @return MSFDataLoader
     */
    public function getMsfDataLoader()
    {
        return $this->msfDataLoader;
    }

    /**
     * @param MSFDataLoader $msfDataLoader
     */
    public function setMsfDataLoader($msfDataLoader)
    {
        $this->msfDataLoader = $msfDataLoader;
    }

    public function isSetNavigation(){
        return $this->isSetNavigation;
    }

    public function setNavigation(){
        $this->isSetNavigation = true;
    }

    /**
     * --------------------------------------------------------------------------------
     *              UTILS
     * --------------------------------------------------------------------------------
     */

    public function getLocalConfiguration(){
        // To prevent useless read actions on configuration array
        if(isset($this->localConfiguration)){
            return $this->localConfiguration;
        }

        try{
            $this->localConfiguration = $this->configuration[$this->msfDataLoader->getState()];
            return $this->localConfiguration;
        }catch (\Exception $e){
            throw new \Exception("Trying to access a non configured state");
        }
    }

    public function setLocalConfiguration($key,$value)
    {
        $this->configuration[$this->getState()][$key] = $value;
        $this->resetLocalConfiguration();
    }

    public function resetLocalConfiguration(){
        $this->localConfiguration = null;
        return $this;
    }

    /**
     * This function avoids to make multiple calls to serializer when it's useless
     * @return array of deserialized objects
     * @throws \Exception
     */
    public function getUndeserializedMSFDataLoader(){
        if(isset($this->undeserializedMSFDataloader)){
            return $this->undeserializedMSFDataloader;
        }

        $this->undeserializedMSFDataloader = [];

        try{
            //conversion from json to array
            $undeserialized = $this->getMsfDataLoader()->getData();
            $dataArray = $this->getSerializer()->deserialize($undeserialized, 'array', 'json');

            foreach ($dataArray as $state => $config){
                //current form data
                $datajson = json_encode(isset($dataArray[$state]) ? $dataArray[$state] : []);
                $this->undeserializedMSFDataloader[$state] = $this->getSerializer()->deserialize($datajson,$this->configuration[$state]['entity'], 'json');
            }
        }catch (\Exception $e){
            throw new \Exception("Failed to undeserialize data from Msf Dataloader. \n".$e->getMessage());
        }

        return $this->undeserializedMSFDataloader;
    }

    /**
     * Reset the variable 'undeserializedMSFDataloader' set up in getUndeserializedMSFDataLoader()
     * @return $this
     */
    public function resetUndeserializedMSFDataLoader(){
        $this->undeserializedMSFDataloader = null;
        return $this;
    }

    /**
     * --------------------------------------------------------------------------------
     *              FACADE FOR MSFService Methods
     * --------------------------------------------------------------------------------
     */
    /**
     * @return RequestStack
     */
    public function getRequestStack()
    {
        return $this->msf->getRequestStack();
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->msf->getRouter();
    }

    /**
     * @return FormFactory
     */
    public function getFormFactory()
    {
        return $this->msf->getFormFactory();
    }

    /**
     * @return Serializer
     */
    public function getSerializer()
    {
        return $this->msf->getSerializer();
    }

    /**
     * @param Serializer $serializer
     */
    public function setSerializer($serializer)
    {
        $this->msf->setSerializer($serializer);
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->msf->getSession();
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->msf->getEntityManager();
    }

    /**
     * @return string
     */
    public function getState(){
        return $this->getMsfDataLoader()->getState();
    }



}