<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 17:08
 */

namespace Blixit\MSFBundle\Core;


use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Form\FormFactory;
use JMS\Serializer\Serializer;
use Doctrine\ORM\EntityManager;

class MSFService
{
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var Router
     */
    private $router;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * RegistrationMSFType constructor. To add your own dependencies, setup the service
     * @param RequestStack $requestStack
     * @param Router $router
     * @param FormFactory $formFactory
     * @param Serializer $serializer
     * @param EntityManager $entityManager
     * @param Session $session
     */
    function __construct(
        RequestStack $requestStack,
        Router $router,
        FormFactory $formFactory,
        Serializer $serializer,
        EntityManager $entityManager,
        Session $session
    )
    {
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->formFactory = $formFactory;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->session = $session;

    }

    public function create($MSFFormType, $defaultState = '__msf__'){
        if($defaultState == '__msf__')
            return (new \ReflectionClass($MSFFormType))->newInstance($this);
        else
            return (new \ReflectionClass($MSFFormType))->newInstance($this, $defaultState);
    }

    /**
     * @return RequestStack
     */
    public function getRequestStack()
    {
        return $this->requestStack;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack($requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param Router $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @return FormFactory
     */
    public function getFormFactory()
    {
        return $this->formFactory;
    }

    /**
     * @param FormFactory $formFactory
     */
    public function setFormFactory($formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @return Serializer
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @param Serializer $serializer
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param Session $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }


}