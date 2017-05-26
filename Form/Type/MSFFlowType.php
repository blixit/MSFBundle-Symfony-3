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
use Blixit\MSFBundle\Exception\MSFPageNotFoundException;
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
            if(is_string($action)) {
                $redirection = $this->getRouteOrUrl('__root',[
                    '__msf_nvg' => '',
                    '__msf_state' => $action,
                ],'?__msf_nvg&__msf_state="'.$action.'"');

            }else{
                $redirection = $this->getConfiguration()['__on_cancel']['redirection'];
            }

            throw new MSFRedirectException(new RedirectResponse($redirection));
        }
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
        if(! is_string($page))
            throw new MSFTransitionBadReturnTypeException($this->getState(),'cancel');
        $this->setLocalConfiguration('cancel',$page);
    }

    public final function getNextPage()
    {
        $config = $this->getLocalConfiguration();
        if(! array_key_exists('after',$config))
            throw new MSFConfigurationNotFoundException($this->getState(),'after');

        return $config['after'];
    }

    public final function setNextPage($page)
    {
        if(! is_string($page))
            throw new MSFTransitionBadReturnTypeException($this->getState(),'after');
        $this->setLocalConfiguration('after',$page);
    }

    public final function getPreviousPage()
    {
        $config = $this->getLocalConfiguration();
        if(! array_key_exists('before',$config))
            throw new MSFConfigurationNotFoundException($this->getState(),'before');

        return $config['before'];
    }

    public final function setPreviousPage($page)
    {
        if(! is_string($page))
            throw new MSFTransitionBadReturnTypeException($this->getState(),'before');
        $this->setLocalConfiguration('before',$page);
    }

    /**
     * Get names of states
     * @return array
     */
    public final function getSteps()
    {
        if(isset($this->steps))
            return $this->steps;

        $tmp = preg_grep("/^[a-zA-Z0-9]/", array_keys($this->getConfiguration()));
        //we loop to remove bad numerical indexes
        $this->steps = [];
        $i = 0;
        foreach ($tmp as $item) {
            array_push($this->steps,[
                'index' =>  $i,
                'name' =>  $item,
            ]);
            $i++;
        }
        return $this->steps;
    }

    /**
     * Get steps with related links
     * @param $routeName
     * @param array $parameters
     * @return mixed
     */
    public function getStepsWithLink($routeName, array $parameters)
    {
        if(isset($this->stepsWithLink))
            return $this->stepsWithLink;

        foreach ($this->getSteps() as $key => $step){
            $parameters['__msf_nvg'] = '';
            $parameters['__msf_state'] = $step['name'];
            $this->steps[$key]['link'] = $this->getRouter()->generate($routeName, $parameters);
        }
        $this->stepsWithLink = $this->steps;
        $this->steps = null; // to force reload on getSteps()
        return $this->stepsWithLink;
    }

    public function initTransitions()
    {
        $config = $this->getLocalConfiguration();

        /**
         * If keys don't exist, we look in the default configuration
         */

        //after
        if(! array_key_exists('after',$config)){
            $this->setLocalConfiguration('after', null);
        }else{
            $action = $this->getLocalConfiguration()['after'];
            if(is_callable($action)){
                $action = call_user_func($config['after'], $this->getUndeserializedMSFDataLoader() );
            }
            if(!is_string($action)){
                $action = null;
            }
            $this->setLocalConfiguration('after', $action);
        }
        //before
        if(! array_key_exists('before',$config)){
            $this->setLocalConfiguration('before', null);
        }else{
            $action = $this->getLocalConfiguration()['before'];
            if(is_callable($action)){
                $action = call_user_func($config['before'], $this->getUndeserializedMSFDataLoader() );
            }
            if(!is_string($action)){
                $action = null;
            }
            $this->setLocalConfiguration('before', $action);
        }
        //cancel
        if(! array_key_exists('cancel',$config)){
            $this->setLocalConfiguration('cancel', null);
        }else{
            $action = $this->getLocalConfiguration()['cancel'];
            if(is_callable($action)){
                $action = call_user_func($config['cancel'], $this->getUndeserializedMSFDataLoader() );
            }
            if(!is_string($action)){
                $action = null;
            }
            $this->setLocalConfiguration('cancel', $action);
        }

        //redirection
        if(! array_key_exists('redirection',$config)) {
            if ($this->getConfiguration()['__default_paths']) {
                $this->setLocalConfiguration('redirection', $this->getConfiguration()['__final_redirection']);
            } else {
                $this->setLocalConfiguration('redirection', null);
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

    public function getRouteOrUrl($key, $parameters=[], $asUrlParameters=''){
        $keyAsUrl = $key.'AsUrl';
        $route = null;

        if(array_key_exists($keyAsUrl,$this->getConfiguration())){
            $route =  $this->getConfiguration()[$keyAsUrl].$asUrlParameters;
        }else if(array_key_exists($key,$this->getConfiguration())){
            $route = $this->getRouter()->generate($this->getConfiguration()[$key],$parameters);
        }
        return $route;
    }
}