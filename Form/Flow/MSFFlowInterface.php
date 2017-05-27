<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 17:53
 */

namespace Blixit\MSFBundle\Form\Flow;


interface MSFFlowInterface
{
    public function getCancelPage();

    public function setCancelPage($page);

    public function getNextPage();

    public function setNextPage($page);

    public function getPreviousPage();

    public function setPreviousPage($page);

    public function getSteps();

    public function getStepsWithLink($routeName, array $parameters, $buttonsLink = false);

    public function initTransitions();

    public function isAvailable($state);


}