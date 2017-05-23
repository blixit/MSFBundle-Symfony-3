<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 19:29
 */

namespace Blixit\MSFBundle\Form\Type;


use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Form\Flow\MSFValidationInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class MSFAbstractType
    extends MSFBuilderType
    implements MSFValidationInterface
{

    function __construct(MSFService $msf, $defaultState)
    {
        parent::__construct($msf, $defaultState);
    }

    /**
     * @param $formData
     * @return boolean
     */
    public final function onNextValidate(&$formData)
    {
        $config = $this->getLocalConfiguration();

        //execute validation function
        if(array_key_exists('validation',$config))
            return call_user_func_array($config['validation'],[&$formData]);

        return true;
    }

    /**
     * @param $formData
     * @return boolean
     */
    public final function onPreviousValidate(&$formData)
    {
        $config = $this->getLocalConfiguration();

        //execute validation function
        if(array_key_exists('previous_validation',$config))
            return call_user_func_array($config['previous_validation'],[&$formData]);

        return true;
    }

    /**
     * @param $formData
     * @return boolean
     */
    public final function onCancelValidate(&$formData)
    {
        $config = $this->getLocalConfiguration();

        //execute validation function
        if(array_key_exists('cancel_validation',$config))
            return call_user_func_array($config['cancel_validation'],[&$formData]);

        return true;
    }

    /**
     * @param \Exception $e
     * @return mixed|RedirectResponse
     */
    public function onFailure(\Exception $e)
    {
        $this->getCurrentForm()->addError(new FormError($e->getMessage()));
        return new RedirectResponse( $this->getRequestStack()->getCurrentRequest()->getUri()  );
    }
}