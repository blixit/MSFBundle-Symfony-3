<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 24/05/17
 * Time: 13:36
 */

namespace Blixit\MSFBundle\Form\TemplateTypes;


interface MSFLinearTypeInterface
{
    public function getSteps();

    public function getStepsWithLink($routeName, array $parameters);

    public function getStatesCount();

    public function initTransitions();
}