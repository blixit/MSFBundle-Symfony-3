<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 24/05/17
 * Time: 13:39
 */

namespace Blixit\MSFBundle\Form\TemplateTypes;


use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Entity\Example\Article;
use Blixit\MSFBundle\Entity\Example\Blog;
use Blixit\MSFBundle\Form\Builder\MSFBuilderInterface;
use Blixit\MSFBundle\Form\Type\MSFAbstractType;

abstract class MSFLinearType
    extends MSFAbstractType
    implements MSFLinearTypeInterface
{

    /**
     * Get names of states
     * @return array
     */
    public function getSteps()
    {
        if(isset($this->steps))
            return $this->steps;

        $tmp = preg_grep("/^[a-zA-Z0-9]/", array_keys($this->configuration));
        //we loop to remove bad indexes
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
            $parameters['__get_msf_state'] = $step['name'];
            $this->steps[$key]['link'] = $this->getRouter()->generate($routeName, $parameters);
        }
        $this->stepsWithLink = $this->steps;
        $this->steps = null; // to force reload on getSteps()
        return $this->stepsWithLink;
    }

    /**
     * Return the count of states in this bundle
     * @return int
     */
    public function getStatesCount()
    {
        return count($this->getSteps());
    }

    /**
     * @param $msfData
     * @param object $formData
     * @return mixed|null
     * @inheritdoc
     */
    public function getNextPage()
    {
        $liste = $this->getSteps();
        $next = false;
        $stepReturned = null;

        foreach ($liste as $stepArr) {
            $step = $stepArr['name'];
            if($next){
                $stepReturned = $step;
                break;
            }
            if($step == $this->getState()){
                $next = true;
            }
        }

        return $next ? $stepReturned : null;
    }

    /**
     * @return mixed|null
     */
    public function getPreviousPage(){
        $liste = $this->getSteps();
        $last = count($liste)-1;
        $previous = false;
        $stepReturned = null;

        for ($i = $last; $i>=0; $i-- ){
            if($previous){
                $stepReturned = $liste[$i]['name'];
                break;
            }
            if($liste[$i]['name'] == $this->getState())
                $previous = true;
        }

        return $stepReturned;
    }

    public function initTransitions()
    {
        $liste = $this->getSteps();
        $sz = count($liste);
        if(! $sz)
            return $this;

        $lastStep = $liste[0]['name'];
        $this->configuration[$lastStep]['before'] = null;

        for( $i = 1; $i < $sz; $i++){
            $this->configuration[$liste[$i]['name']]['before'] = $lastStep;
            $lastStep = $liste[$i]['name'];
            if($i < ($sz-1)){
                $this->configuration[$liste[$i]['name']]['after'] = $liste[$i+1]['name'];
            }else
                $this->configuration[$liste[$i]['name']]['after'] = null;
        }
        return $this;
    }
}