<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 19:43
 */

namespace Blixit\MSFBundle\Form\Flow;


interface MSFValidationInterface
{
    /**
     * @param $object
     * @return boolean
     */
    public function onNextValidate(&$object);

    /**
     * @param $object
     * @return boolean
     */
    public function onPreviousValidate(&$object);

    /**
     * @param $object
     * @return boolean
     */
    public function onCancelValidate(&$object);

    /**
     * @param \Exception $e
     * @return mixed
     */
    public function onFailure(\Exception $e);
}