<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 18:23
 */

namespace Blixit\MSFBundle\Form\Type;


use Blixit\MSFBundle\Exception\MSFFailedToValidateFormException;
use Blixit\MSFBundle\Exception\MSFNextPageNotFoundException;
use Blixit\MSFBundle\Exception\MSFPreviousPageNotFoundException;
use Blixit\MSFBundle\Exception\MSFRedirectException;
use Blixit\MSFBundle\Exception\MSFTransitionBadReturnTypeException;
use Blixit\MSFBundle\Form\Flow\MSFFlowInterface;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\Form\Exception\OutOfBoundsException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

abstract class MSFFlowType_o
    extends MSFBaseType
    implements MSFFlowInterface
{
    const ACTIONS_SUBMIT = 'save'; // default symfony action for submit
    const ACTIONS_CANCEL = 'msf_cancel_action';
    const ACTIONS_PREVIOUS = 'msf_previous_action';

    /**
     * Navigate to the next form, the previous form, cancel or redirect to the set redirection path
     * @return RedirectResponse
     * @throws MSFRedirectException
     */
    public final function done()
    {
        /*
         * I use to try-catch to catch involuntary OutOfBoundsException errors
         * For instance, if submit is clicked, get('cancel') and get('previous') will generate errors         *
         */
        try{
            if($this->getCurrentForm()->get(self::ACTIONS_CANCEL)->isClicked()) {
                $done = $this->hasCancel();
                if ($done instanceof RedirectResponse)
                    throw new MSFRedirectException($done);
                return $done;
            }
        }catch (OutOfBoundsException $e){
        }catch (MSFFailedToValidateFormException $e){
            return $this->onFailure($e);
        }

        try{
            if($this->getCurrentForm()->get(self::ACTIONS_PREVIOUS)->isClicked()) {
                $done = $this->hasPrevious();
                if ($done instanceof RedirectResponse)
                    throw new MSFRedirectException($done);
                return $done;
            }
        }catch (OutOfBoundsException $e){
        }catch (MSFFailedToValidateFormException $e){
            return $this->onFailure($e);
        }

        /*
         * For the last one, no need to catch
         */
        return $this->next();
    }

    public final function hasCancel()
    {
        return new RedirectResponse( $this->getRequestStack()->getCurrentRequest()->getUri() );
    }

    public final function cancel()
    {
        $done = null;
        try{
            if($this->getCurrentForm()->get(self::ACTIONS_CANCEL)->isClicked()) {
                $done = $this->hasCancel();
                if ($done instanceof RedirectResponse)
                    throw new MSFRedirectException($done);
            }
        }catch (OutOfBoundsException $e){
        }catch (MSFFailedToValidateFormException $e){
            return $this->onFailure($e);
        }
        return $done;
    }

    public function getCancelPage()
    {
        $config = $this->getLocalConfiguration();

        if( ! array_key_exists('cancel',$config) ){
            $action = null;
            //$action = $this->configuration['__root'];
        }else{
            $action = $config['cancel'];
            if(is_callable($config['cancel'])){
                $undeserialized = $this->getMsfDataLoader()->getData();
                $dataArray = $this->getSerializer()->deserialize($undeserialized, 'array', 'json');

                $dataArray['__state'] = $this->getMsfDataLoader()->getState();

                try{
                    $action = call_user_func($config['cancel'], $dataArray, $this->getCurrentForm()->getData(), $this->getSerializer());
                }catch (\Exception $e){
                    throw new \Exception("The ".$this->getMsfDataLoader()->getState()." 'cancel callback' raise an exception : \n".$e->getMessage() );
                }
            }
        }

        if(is_bool($action))
            throw new MSFTransitionBadReturnTypeException($this->getMsfDataLoader()->getState(),'cancel');

        return $action;
    }


    /**
     * @return RedirectResponse
     * @throws \Exception
     */
    public final function hasNext()
    {
        //read local msfDataLoader

        $undeserialized = $this->getUndeserializedMSFDataLoader();
        //$undeserialized = $this->getMsfDataLoader()->getData();
        //$dataArray = $this->getSerializer()->deserialize($undeserialized, 'array', 'json');

        $formData = $this->getCurrentForm()->getData();
        try{

            if(! $this->onNextValidate($undeserialized, $formData) )
                throw new MSFFailedToValidateFormException($this->getMsfDataLoader()->getState(), 'validation');

        }catch (\Exception $e){
            throw new MSFFailedToValidateFormException($this->getMsfDataLoader()->getState(), 'validation', $e->getMessage());
        }


        //$dataArray[ $this->getMsfDataLoader()->getState() ] = $formData;

        //write local msfDataLoader
        //$this->getMsfDataLoader()->setArrayData($dataArray,$this->getSerializer());


        $config = $this->getLocalConfiguration();

        $next = $this->getNextPage();

        if( $next === null){ //die('NEXT not found');
            /*
             * Suppression du MSFDataLoader
             * En effet, si la dernière étape est validée,
             */
            if($this->configuration['__on_terminate']['destroy_data'])
            {
                /*
                 * The msfdataloader entity need to have been loaded by EM to be destroy by it
                 *
                 * https://stackoverflow.com/questions/17613684/how-to-determine-if-a-doctrine-entity-is-persisted
                 * https://stackoverflow.com/questions/13441156/why-there-is-the-need-of-detaching-and-merging-entities-in-a-orm
                 */
                if($this->getEntityManager()->contains($this->getMsfDataLoader())) {
                    //$this->entityManager->detach($this->msfDataLoader);
                    $this->getEntityManager()->remove($this->getMsfDataLoader());
                }else{
                    if($this->getMsfDataLoader()->getId()){
                        $msfDataLoader = $this->getEntityManager()->getRepository('BlixitMultiStepFormBundle:MSFDataLoader')
                            ->findOneBy(['id'=>$this->getMsfDataLoader()->getId()]);
                        $this->setMsfDataLoader($msfDataLoader);
                        $this->getEntityManager()->remove($this->getMsfDataLoader());
                        $this->getEntityManager()->flush();
                    }
                }
                //store in session
                $this->getSession()->remove('__msf_dataloader');
            }else{
                //save local data
                //the current state is the last state
                if($this->getMsfDataLoader()->getId())
                    $this->getEntityManager()->merge($this->getMsfDataLoader());
                else
                    $this->getEntityManager()->persist($this->getMsfDataLoader());
                $this->getEntityManager()->flush();
                //store in session
                $this->getSession()->set('__msf_dataloader',$this->getMsfDataLoader());
            }

            try{
                return new RedirectResponse( $config['redirection'] );
            }catch (RouteNotFoundException $e){
                return new RedirectResponse( $this->configuration['__final_redirection'] );
            }catch (ContextErrorException $e){
                if($this->configuration['__default_paths'])
                    return new RedirectResponse( $this->configuration['__final_redirection'] );
                else
                    throw new MSFNextPageNotFoundException($this->getMsfDataLoader()->getState());
            }
        }else{
            //save the new state
            $this->getMsfDataLoader()->setState($next);
            if($this->getMsfDataLoader()->getId())
                $this->getEntityManager()->merge($this->getMsfDataLoader());
            else
                $this->getEntityManager()->persist($this->getMsfDataLoader());
            $this->getEntityManager()->flush();
            //store in session
            $this->getSession()->set('__msf_dataloader',$this->getMsfDataLoader());
        }

        //redirect to refresh the page
        return new RedirectResponse( $this->getRequestStack()->getCurrentRequest()->getUri() );
    }


    /**
     * @return null
     * @throws MSFRedirectException
     */
    public final function next()
    {
        $done = null;
        try{
            //This function is called whatever the clicked button. Then, no need to check what button is clicked.
            //Actually, thanks to the done() method schema, if you click previous or cancel buttons, you won't never reach that fonction

            //if($this->getCurrentForm()->get(self::ACTIONS_SUBMIT)->isClicked()) {
                $done = $this->hasNext();
                if ($done instanceof RedirectResponse)
                    throw new MSFRedirectException($done);
            //}
        }catch (OutOfBoundsException $e){
        }catch (MSFFailedToValidateFormException $e){
            return $this->onFailure($e);
        }
        return $done;
    }

    /**
     * This function returns the name of the next form. Its need then to re-compute the result of the 'after' callback to determine
     * the appropriate transition.
     * This callback takes as parameter a deserialized copy of the current MSFDataLoader data array representation.
     * if you call this function inside a controller for instance, you will have to check the array integrity by yourself.
     * @return mixed|string
     * @throws \Exception
     */
    public function getNextPage()
    {
        $config = $this->getLocalConfiguration();

        if( ! array_key_exists('after',$config) ){
            $action = null;
        }else {
            $action = $config['after'];
            if(is_callable($config['after'])){

                $undeserialized = $this->getMsfDataLoader()->getData();
                $dataArray = $this->getSerializer()->deserialize($undeserialized, 'array', 'json');

                try {
                    $action = call_user_func($config['after'], $dataArray, $this->getCurrentForm()->getData());
                } catch (\Exception $e) {
                    throw new \Exception("The 'after' callback defined on the state '" . $this->getMsfDataLoader()->getState() . "' raised an exception : \n" . $e->getMessage());
                }
            }
        }

        if(is_bool($action))
        throw new MSFTransitionBadReturnTypeException($this->getMsfDataLoader()->getState(),'after');

        return $action;
    }

    public final function hasPrevious()
    {
        $last = $this->getPreviousPage();

        if($last === null){
            throw new MSFPreviousPageNotFoundException($this->getMsfDataLoader()->getState());
        }

        $undeserialized = $this->getUndeserializedMSFDataLoader();
        $formData = $this->getCurrentForm()->getData();
        try{
            if(! $this->onPreviousValidate($undeserialized, $formData))
                throw new MSFFailedToValidateFormException($this->getMsfDataLoader()->getState(), 'previous_validation');

        }catch (\Exception $e){
            throw new MSFFailedToValidateFormException($this->getMsfDataLoader()->getState(), 'previous_validation', $e->getMessage());
        }

        if($this->configuration['__on_previous']['save']){
            //Persistance du data loader
            //save the new state
            $this->getMsfDataLoader()->setState($last);
            if($this->getMsfDataLoader()->getId())
                $this->getEntityManager()->merge($this->getMsfDataLoader());
            else
                $this->getEntityManager()->persist($this->getMsfDataLoader());
            $this->getEntityManager()->flush();
        }

        //redirect to refresh the page
        return new RedirectResponse( $this->getRequestStack()->getCurrentRequest()->getUri() );
    }

    public final function previous()
    {
        $done = null;
        try{
            if($this->getCurrentForm()->get(self::ACTIONS_PREVIOUS)->isClicked()) {
                $done = $this->hasPrevious();
                if ($done instanceof RedirectResponse)
                    throw new MSFRedirectException($done);
            }
        }catch (OutOfBoundsException $e){
        }catch (MSFFailedToValidateFormException $e){
            return $this->onFailure($e);
        }
        return $done;
    }

    public function getPreviousPage()
    {
        $config = $this->getLocalConfiguration();

        if( ! array_key_exists('before',$config) ){
            $action = null;
        }else{
            $action = $config['before'];
            if(is_callable($config['before'])){
                $undeserialized = $this->getMsfDataLoader()->getData();
                $dataArray = $this->getSerializer()->deserialize($undeserialized, 'array', 'json');

                try{
                    $action = call_user_func($config['before'], $dataArray, $this->getCurrentForm()->getData());
                }catch (\Exception $e){
                    throw new \Exception("The 'before' callback defined on the state '".$this->getMsfDataLoader()->getState()."' raised an exception : \n".$e->getMessage() );
                }
            }
        }

        if(is_bool($action))
            throw new MSFTransitionBadReturnTypeException($this->getMsfDataLoader()->getState(),'before');

        return $action;
    }
}