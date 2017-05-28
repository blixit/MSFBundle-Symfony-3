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
use Blixit\MSFBundle\Exception\MSFStateUnreachableException;
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
                throw new MSFStateUnreachableException($this->getState());

        }else{

            //Deserialize dataloader
            $this->getUndeserializedMSFDataLoader();
        }

        $this->initTransitions();

        if(! is_null($cancelQuery)){
            $action = $this->getCancelPage();
            $redirection = $this->getCancelRedirectionPage($action);

            throw new MSFRedirectException(new RedirectResponse($redirection));
        }
    }

    public final function getCancelRedirectionPage($action){
        if(is_string($action)) {
            $redirection = $this->getRouteOrUrl('__root',[
                '__msf_nvg' => '',
                '__msf_state' => $action,
            ],'?__msf_nvg&__msf_state="'.$action.'"');

        }else{
            $redirection = $this->getConfiguration()['__on_cancel']['redirection'];
        }
        return $redirection;
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

        $states = preg_grep("/(^[a-zA-Z0-9])/", array_keys($this->getConfiguration()));
        //we loop to remove bad numerical indexes
        $this->steps = [];
        $i = 0;
        foreach ($states as $item) {
            if(str_replace("msf_btn","",$item) != $item)
                continue;
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
     * @param bool $buttonsLink
     * @return mixed
     * @throws MSFConfigurationNotFoundException
     */
    public function getStepsWithLink($routeName, array $parameters, $buttonsLink = false)
    {
        if(isset($this->stepsWithLink))
            return $this->stepsWithLink;
        $steps = [];

        foreach ($this->getSteps() as $key => $step){
            $key = $step['name'];
            $parameters['__msf_nvg'] = '';
            $parameters['__msf_state'] = $step['name'];

            //state link
            $steps[$key]['link'] = $this->getRouter()->generate($routeName, $parameters);

            if($buttonsLink){
                if(!is_null($this->getConfiguration()[$step['name']]['before']))
                {
                    $parameters['__msf_state'] = $this->getConfiguration()[$step['name']]['before'];
                    $this->executeTransition($parameters['__msf_state'],'before');
                    $steps[$key]['linkbefore'] = $this->getRouter()->generate($routeName, $parameters);
                }else{
                    if($this->getConfiguration()['__default_paths']){
                        $steps[$key]['linkbefore'] = $this->getConfiguration()['__root'];
                    }else{
                        $steps[$key]['linkbefore'] = "#";
                    }
                }

                if(!is_null($this->getConfiguration()[$step['name']]['after']))
                {
                    $parameters['__msf_state'] = $this->getConfiguration()[$step['name']]['after'];
                    $this->executeTransition($parameters['__msf_state'],'after');
                    $steps[$key]['linkafter'] = $this->getRouter()->generate($routeName, $parameters);
                }else{
                    if($this->getConfiguration()['__default_paths']){
                        $steps[$key]['linkafter'] = $this->getConfiguration()['__final_redirection'];
                    }else{
                        $steps[$key]['linkafter'] = "#";
                    }
                }

                if(!is_null($this->getConfiguration()[$step['name']]['cancel']))
                {
                    $parameters['__msf_state'] = $this->getConfiguration()[$step['name']]['cancel'];
                    $this->executeTransition($parameters['__msf_state'],'cancel');
                    $steps[$key]['linkcancel'] = $this->getRouter()->generate($routeName, $parameters);
                }else{
                    if($this->getConfiguration()['__default_paths']){
                        $steps[$key]['linkcancel'] = $this->getCancelRedirectionPage(null);
                    }else{
                        if($this->getConfiguration()['__buttons_have_cancel'])
                            throw new MSFConfigurationNotFoundException($step['name'],'cancel');
                        else
                            $steps[$key]['linkcancel'] = "#";
                    }
                }
            }
        }
        $this->stepsWithLink = $steps;
        return $this->stepsWithLink;
    }

    public function initTransitions()
    {
        foreach ($this->getSteps() as $key => $step){
            $state = $step['name'];

            //after
            $config = $this->getConfiguration()[$state];
            if (!array_key_exists('after', $config)) {
                //$this->setLocalConfiguration('after', null);
                $config['after'] = null;
                $this->setConfigurationWith($state,$config);
            } else {
                $this->executeTransition($state,'after');
            }

            //before
            $config = $this->getConfiguration()[$state];
            if (!array_key_exists('before', $config)) {
                //$this->setLocalConfiguration('before', null);
                $config['before'] = null;
                $this->setConfigurationWith($state,$config);
            } else {
                $this->executeTransition($state,'before');
            }

            //cancel
            $config = $this->getConfiguration()[$state];
            if (!array_key_exists('cancel', $config)) {
                //$this->setLocalConfiguration('cancel', null);
                $config['cancel'] = null;
                $this->setConfigurationWith($state,$config);
            } else {
                $this->executeTransition($state,'cancel');
            }

            //redirection
            $config = $this->getConfiguration()[$state];
            if (!array_key_exists('redirection', $config)) {
                if ($this->getConfiguration()['__default_paths']) {
                    //$this->setLocalConfiguration('redirection', $this->getConfiguration()['__final_redirection']);
                    $config['redirection'] = $this->getConfiguration()['__final_redirection'];
                    $this->setConfigurationWith($state,$config);
                } else {
                    //$this->setLocalConfiguration('redirection', null);
                    $config['redirection'] = null;
                    $this->setConfigurationWith($state,$config);
                }
            }

            //$this->getConfiguration()[$this->getState()] = $this->getLocalConfiguration();
        }
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

    /**
     * Set transition value be available with the good type (string or null)
     * If the transition is callable its code is ran
     * @param $state
     * @param $transition
     * @throws MSFBadStateException
     */
    private function executeTransition($state,$transition){

        $config = $this->getConfiguration()[$state];
        $action = $config[$transition];

        if(is_callable($action)){
            $action = call_user_func($action, $this->getUndeserializedMSFDataLoader() );
            if((!is_null($action)) && (! array_key_exists($action,$this->getConfiguration())))
                throw new MSFBadStateException($action);
        }
        if(!is_string($action)){
            $action = null;
        }
        $config[$transition] = $action;
        $this->setConfigurationWith($state, $config);
    }
}