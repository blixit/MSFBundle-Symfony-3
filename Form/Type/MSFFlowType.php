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
     * Create links for getStepsWithLink method
     * @param $currentStep
     * @param $transition
     * @param $routeName
     * @param $parameters
     * @return string
     */
    private function executeAndSetStep($currentStep, $transition, $routeName, $parameters){
        $parameters['__msf_state'] = $this->getConfiguration()[$currentStep][$transition];
        $this->executeTransition($parameters['__msf_state'],$transition);
        return $this->getRouter()->generate($routeName, $parameters);
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
                    $steps[$key]['linkbefore'] = $this->executeAndSetStep($step['name'],'before',$routeName,$parameters);
                }else{
                    if($this->getConfiguration()['__default_paths']){
                        $steps[$key]['linkbefore'] = $this->getConfiguration()['__root'];
                    }else{
                        $steps[$key]['linkbefore'] = "#";
                    }
                }

                if(!is_null($this->getConfiguration()[$step['name']]['after']))
                {
                    $steps[$key]['linkafter'] = $this->executeAndSetStep($step['name'],'after',$routeName,$parameters);
                }else{
                    if($this->getConfiguration()['__default_paths']){
                        $steps[$key]['linkafter'] = $this->getConfiguration()['__final_redirection'];
                    }else{
                        $steps[$key]['linkafter'] = "#";
                    }
                }

                if(!is_null($this->getConfiguration()[$step['name']]['cancel']))
                {
                    $steps[$key]['linkcancel'] = $this->executeAndSetStep($step['name'],'cancel',$routeName,$parameters);
                }else{
                    if($this->getConfiguration()['__default_paths']){
                        $steps[$key]['linkcancel'] = $this->getCancelRedirectionPage(null);
                    }else{
                        if($this->getConfiguration()['__buttons_have_cancel'])
                            $steps[$key]['linkcancel'] = $this->getConfiguration()['__root'];
                        else
                            $steps[$key]['linkcancel'] = "#";
                    }
                }
            }
        }
        $this->stepsWithLink = $steps;
        return $this->stepsWithLink;
    }

    /**
     * Returns menu
     * @param $routeName
     * @return array
     */
    public function getMenu($routeName){
        $states = $this->getSteps();

        $menu = [];
        foreach ($states as $key => $step){
            $key = $step['name'];
            $parameters = [
                '__msf_nvg' => '',
                '__msf_state' => $step['name']
            ];

            //name
            $menu[$key]['name'] = $key;

            //state link
            if($this->isAvailable($key)){
                $menu[$key]['link'] = $this->getRouter()->generate($routeName, $parameters);
            }else
                $menu[$key]['link'] = null;
        }

        return $menu;
    }

    /**
     * Set or execute (if callable) a transition
     * @param array $config
     * @param $state
     * @param $transition
     */
    private function setOrExecuteTrantition(array $config, $state, $transition){

        $config = $this->getConfiguration()[$state];
        if (!array_key_exists($transition, $config)) {
            //$this->setLocalConfiguration('after', null);
            $config[$transition] = null;
            $this->setConfigurationWith($state,$config);
        } else {
            $this->executeTransition($state,$transition);
        }
    }

    /**
     * Initialize all the transitions
     */
    public function initTransitions()
    {
        foreach ($this->getSteps() as $key => $step){
            $state = $step['name'];

            //after
            $config = $this->getConfiguration()[$state];
            $this->setOrExecuteTrantition($config,$state,'after');

            //before
            $config = $this->getConfiguration()[$state];
            $this->setOrExecuteTrantition($config,$state,'before');

            //cancel
            $config = $this->getConfiguration()[$state];
            $this->setOrExecuteTrantition($config,$state,'cancel');

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