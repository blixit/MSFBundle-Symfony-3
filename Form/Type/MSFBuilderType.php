<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 18:22
 */

namespace Blixit\MSFBundle\Form\Type;


use Blixit\MSFBundle\Form\Builder\MSFBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

abstract class MSFBuilderType
    extends MSFFlowType
    implements MSFBuilderInterface
{

    public final function getForm()
    {
        $state = $this->getMsfDataLoader()->getState();
        $config = $this->getLocalConfiguration();

        $data = null;
        try{
            //conversion from json to array
            $undeserialized = $this->getMsfDataLoader()->getData();
            $dataArray = $this->getSerializer()->deserialize($undeserialized, 'array', 'json');

            //current form data
            $datajson = json_encode(isset($dataArray[$state]) ? $dataArray[$state] : []);
            $data = $this->getSerializer()->deserialize($datajson,$config['entity'], 'json');

        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }

        /**
         * ici, on pourrait regarder si une fonction init a été fournie. Si oui, utiliser son résultat comme
         * entrée de setcurrentForm()
         */
        $this->setCurrentForm( $this->getFormFactory()->create(
            $config['formtype'],
            $data,
            [
                'action'    =>  $config['action'],
                'method'    =>  $config['method'],
            ]
        ));

        //Adding user fields
        $this->buildMSF();

        return $this->getCurrentForm();
    }


    public final function addSubmitButton(array $options)
    {
        $this->getCurrentForm()->add(self::ACTIONS_SUBMIT,SubmitType::class,[
            'label'  => isset($options['label']) ? $options['label'] : 'Cancel',
            'attr'  => isset($options['attr']) ? $options['attr'] : []
        ]);
        return $this;
    }

    /**
     * Add cancel button to MSF Form
     * Add the button unless the action is provided.
     * cancel route is searched in this order
     * - field 'action' in the provided array
     * - field 'cancel' in the local configuration (see the configure method)
     * - field '__root' in the global configuration
     * @param array $options
     * @return $this
     */
    public final function addCancelButton(array $options)
    {
        //cherche dans toutes les configurations existantes
        if(! array_key_exists('action',$options) ){
            try{
                $action = $this->getCancelPage();
            }catch (\Exception $e){
                $action = null;
            }
        }else
            $action = $options['action'];

        if(is_null($action))
            return $this;

        $this->configuration['__cancel_route'] = $action;

        $this->getCurrentForm()->add(self::ACTIONS_CANCEL,SubmitType::class,[
            'label'  => isset($options['label']) ? $options['label'] : 'Cancel',
            'attr'  => isset($options['attr']) ? $options['attr'] : []
        ]);
        return $this;
    }

    /**
     * Add the button unless the action is provided
     * @param array $options
     * @return $this
     */
    public final function addPreviousButton(array $options)
    {
        if(! array_key_exists('action',$options) ){
            try{
                $action = $this->getPreviousPage();
            }catch (\Exception $e){
                $action = null;
            }
        }else
            $action = $options['action'];

        if(is_null($action))
            return $this;

        $this->configuration['__previous_route'] = $action;

        $this->getCurrentForm()->add(self::ACTIONS_PREVIOUS,SubmitType::class,[
            'label'  => isset($options['label']) ? $options['label'] : 'Previous',
            'attr'  => isset($options['attr']) ? $options['attr'] : []
        ]);
        return $this;
    }
}