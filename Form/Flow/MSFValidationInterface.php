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
     * @param $msfData
     * @param $object
     * @return bool
     */
    public function onNextValidate($msfData, &$object);

    /**
     * @param $msfData
     * @param $object
     * @return bool
     */
    public function onPreviousValidate($msfData, &$object);

    /**
     * @param $msfData
     * @param $object
     * @return bool
     */
    public function onCancelValidate($msfData, &$object);

    /**
     * @param \Exception $e
     * @return mixed
     */
    public function onFailure(\Exception $e);
}