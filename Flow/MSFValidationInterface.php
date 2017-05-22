<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 19:43
 */

namespace Blixit\MSFBundle\Flow;


interface MSFValidationInterface
{
    /**
     * @param $dataArray
     * @return boolean
     */
    public function onNextValidate(&$dataArray);

    /**
     * @param $dataArray
     * @return boolean
     */
    public function onPreviousValidate(&$dataArray);

    /**
     * @param $dataArray
     * @return boolean
     */
    public function onCancelValidate(&$dataArray);
}