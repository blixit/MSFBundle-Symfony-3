<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 19:29
 */

namespace Blixit\MSFBundle\Form\Type;


use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Flow\MSFValidationInterface;
use Blixit\MSFBundle\Form\Builder\MSFBuilderInterface;

abstract class MSFAbstractType
    extends MSFBuilderType
    implements MSFValidationInterface
{
    function __construct(MSFService $msf, $defaultState)
    {
        parent::__construct($msf, $defaultState);
    }

    /**
     * @param $dataArray
     * @return boolean
     */
    public final function onNextValidate(&$dataArray)
    {
        $config = $this->getLocalConfiguration();

        //execute validation function
        if(array_key_exists('validation',$config))
            return call_user_func_array($config['validation'],[&$dataArray]);

        return true;
    }

    /**
     * @param $dataArray
     * @return boolean
     */
    public final function onPreviousValidate(&$dataArray)
    {
        // TODO: Implement onPreviousValidate() method.
        return true;
    }

    /**
     * @param $dataArray
     * @return boolean
     */
    public final function onCancelValidate(&$dataArray)
    {
        // TODO: Implement onCancelValidate() method.
        return true;
    }
}