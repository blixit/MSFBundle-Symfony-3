<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 18:07
 */

namespace Blixit\MSFBundle\Form\Builder;


interface MSFBuilderInterface
{
    public function getForm();

    /**
     * Modify the form
     * @return $this
     */
    public function buildMSF();

    public function addSubmitButton(array $options = []);

    public function addCancelButton(array $options = []);

    public function addNextButton(array $options = []);

    public function addPreviousButton(array $options = []);
}