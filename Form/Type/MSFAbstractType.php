<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 19:29
 */

namespace Blixit\MSFBundle\Form\Type;


use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Exception\MSFBadStateException;
use Blixit\MSFBundle\Exception\MSFFailedToValidateFormException;
use Blixit\MSFBundle\Exception\MSFNextPageNotFoundException;
use Blixit\MSFBundle\Exception\MSFRedirectException;
use Blixit\MSFBundle\Form\Flow\MSFValidationInterface;
use Symfony\Component\Form\Exception\OutOfBoundsException;
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

    public function done($userRoute = '', $nextState = '')
    {
        $done = null;
        try{
            $undeserialized = $this->getUndeserializedMSFDataLoader();
            $formdata = $this->getCurrentForm()->getData();

            $done = $this->validate($undeserialized, $formdata);
            if(is_bool($done)){
                $action = $this->getNextPage();
                if( ! empty($userRoute)){
                    $link = $userRoute;
                    if(empty($nextState) || ! array_key_exists($nextState,$this->getConfiguration()))
                        throw new MSFBadStateException($nextState);
                    $action = $nextState;

                }elseif (! is_null($action)){
                    $link = $this->getRouter()->generate($this->getConfiguration()['__root'],[
                        '__msf_nvg'=>'',
                        '__msf_state'=>$action,
                    ]);
                }else{
                    if($this->getConfiguration()['__default_paths'])
                        $link = $this->getConfiguration()['__final_redirection'];
                    else
                        throw new MSFNextPageNotFoundException();
                }

                //Valide transitional link
                //then we can save the dataloader
                $undeserialized[$this->getState()] = $formdata;
                if(!empty($action))
                    $undeserialized[$action] = (new \ReflectionClass($this->getConfiguration()[$action]['entity']))->newInstance();

                $this->getMsfDataLoader()->setArrayData($undeserialized,$this->getSerializer());

                if(str_replace("__msf_nvg","",$link) != $link){

                    $this->getSession()->set('__msf_dataloader',$this->getMsfDataLoader());
                }else{
                    if($this->getConfiguration()['__on_terminate']['destroy_data']){
                        $this->getSession()->remove('__msf_dataloader');
                    }else{
                        $this->getSession()->set('__msf_dataloader',$this->getMsfDataLoader());
                    }
                }
                throw new MSFRedirectException(new RedirectResponse($link));
            }else{
                throw new \Exception("The validation method should return a boolean.");
            }

        }catch (MSFFailedToValidateFormException $e){
            return $this->onFailure($e);
        }
    }

    /**
     * @param $msfData
     * @param object $formData
     * @return bool
     */
    public final function validate($msfData, &$formData)
    {
        $config = $this->getLocalConfiguration();

        //execute validation function
        if(array_key_exists('validation',$config))
            return call_user_func_array($config['validation'],[$msfData, &$formData]);

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