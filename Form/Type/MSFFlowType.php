<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 26/05/17
 * Time: 16:10
 */

namespace Blixit\MSFBundle\Form\Type;


use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Exception\MSFBadStateException;
use Blixit\MSFBundle\Exception\MSFConfigurationNotFoundException;
use Blixit\MSFBundle\Exception\MSFRedirectException;
use Blixit\MSFBundle\Exception\MSFTransitionBadReturnTypeException;
use Blixit\MSFBundle\Form\Builder\MSFBuilderInterface;
use Blixit\MSFBundle\Form\Flow\MSFFlowInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class MSFFlowType
    extends MSFBaseType
    implements MSFFlowInterface
{
    function __construct(MSFService $msf, $defaultState)
    {
        parent::__construct($msf, $defaultState);


        /**
         * __msf_nvg = navigate option
         */
        $navigationActive = $this->getRequestStack()->getCurrentRequest()->get('__msf_nvg');
        $queriedState = $this->getRequestStack()->getCurrentRequest()->get('__msf_state');
        $cancelQuery = $this->getRequestStack()->getCurrentRequest()->get('__msf_cncl');

        if(! is_null($navigationActive)){
            if(empty($this->getState())){
                throw new MSFBadStateException($this->getState());
            }

            $this->getMsfDataLoader()->setState($queriedState);
            $this->setNavigation();

            //Deserialize dataloader : will throw an error if the new state is not configured
            $this->getUndeserializedMSFDataLoader();

            if( ! $this->isAvailable($this->getState()))
                throw new MSFBadStateException($this->getState());

        }else{

            //Deserialize dataloader
            $this->getUndeserializedMSFDataLoader();
        }

        $this->initTransitions();

        if(! is_null($cancelQuery)){
            $action = $this->getCancelPage();

            //Execute action if callable or redirect to new state
            //else global redirect

            throw new MSFRedirectException(new RedirectResponse($this->getConfiguration()['__on_cancel']['redirection']));
        }
        var_dump($this->getConfiguration());
        die;





    }

    public final function getCancelPage()
    {
        $config = $this->getLocalConfiguration();
        if(! array_key_exists('cancel',$config))
            throw new MSFConfigurationNotFoundException($this->getState(),'cancel');

        return $config['cancel'];
    }

    public final function setCancelPage($page)
    {
        throw new Exception("Not implemented");
    }

    public final function getNextPage()
    {
        throw new Exception("Not implemented");
    }

    public final function setNextPage($page)
    {
        throw new Exception("Not implemented");
    }

    public final function getPreviousPage()
    {
        throw new Exception("Not implemented");
    }

    public final function setPreviousPage($page)
    {
        throw new Exception("Not implemented");
    }

    public final function getSteps()
    {
        throw new Exception("Not implemented");
    }

    public final function getStepsWithLink()
    {
        throw new Exception("Not implemented");
    }

    public function initTransitions()
    {
        $config = $this->getLocalConfiguration();

        /**
         * If keys don't exist, we look in the default configuration
         */

        //after
        if(! array_key_exists('after',$config)){
            $this->getLocalConfiguration()['after'] = null;
        }else{
            $action = $this->getLocalConfiguration()['after'];
            if(is_null($action)){
                throw new \Exception('Not implemented : is null');
            }else if(is_string($action)){
                throw new \Exception('Not implemented : is string');
            }else if(is_callable($action)){
                throw new \Exception('Not implemented : is callable');
            }else{
                throw new MSFTransitionBadReturnTypeException($this->getState(),'after');
            }
        }
        //before
        if(! array_key_exists('before',$config)){
            $this->getLocalConfiguration()['before'] = null;
        }else{
            $action = $this->getLocalConfiguration()['before'];
            if(is_null($action)){
                throw new \Exception('Not implemented : is null');
            }else if(is_string($action)){
                throw new \Exception('Not implemented : is string');
            }else if(is_callable($action)){
                throw new \Exception('Not implemented : is callable');
            }else{
                throw new MSFTransitionBadReturnTypeException($this->getState(),'before');
            }
        }
        //cancel
        if(! array_key_exists('cancel',$config)){
            $this->getLocalConfiguration()['cancel'] = null;
        }else{
            $action = $this->getLocalConfiguration()['cancel'];
            if(is_null($action)){
                throw new \Exception('Not implemented : is null');
            }else if(is_string($action)){
                throw new \Exception('Not implemented : is string');
            }else if(is_callable($action)){
                throw new \Exception('Not implemented : is callable');
            }else{
                throw new MSFTransitionBadReturnTypeException($this->getState(),'cancel');
            }
        }
        //redirection
        if(! array_key_exists('redirection',$config)) {
            if ($this->getConfiguration()['__default_paths']) {
                $this->getLocalConfiguration()['redirection'] = $this->getConfiguration()['__final_redirection'];
            } else {
                $this->getLocalConfiguration()['redirection'] = null;
            }
        }
        $this->getConfiguration()[$this->getState()] = $this->getLocalConfiguration();
    }

    public final function isAvailable($state)
    {
        if (! array_key_exists($state,$this->getUndeserializedMSFDataLoader()) ){
            return ($this->getDefaultState() == $state);
        }
        return true;
    }
}