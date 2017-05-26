<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 18:22
 */

namespace Blixit\MSFBundle\Form\Type;


use Blixit\MSFBundle\Exception\MSFConfigurationNotFoundException;
use Blixit\MSFBundle\Form\Builder\MSFBuilderInterface;
use Symfony\Component\Debug\Exception\ClassNotFoundException;
use Symfony\Component\Form\Exception\OutOfBoundsException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

abstract class MSFBuilderType
    extends MSFFlowType
    implements MSFBuilderInterface
{
    /**
     * Builds the form
     * - injects loaded data into the form, these data will be erase if a form is submitted
     * - define http action and method
     * - default formtype class is looked at this class path
     * @return \Symfony\Component\Form\FormInterface
     * @throws \Exception
     */
    public final function getForm()
    {
        $state = $this->getMsfDataLoader()->getState();
        $config = $this->getLocalConfiguration();

        if(! array_key_exists('entity', $config))
            throw new MSFConfigurationNotFoundException($this->getMsfDataLoader()->getState(),'entity');

        $data = null;
        try{
            $undeserialized = $this->getUndeserializedMSFDataLoader();
            if(array_key_exists($this->getState(), $undeserialized))
                $data = $undeserialized[$this->getState()];

        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }

        if(! array_key_exists('action', $config)){
            $config['action'] = $this->configuration['__root'];
        }

        if(! array_key_exists('method', $config)){
            $config['method'] = $this->configuration['__method'];
        }

        if(! array_key_exists('formtype', $config)){
            if($this->configuration['__default_formType']){
                $shortname = (new \ReflectionClass($config['entity']))->getShortName();
                try{
                    $defaultNamespace = (new \ReflectionClass(get_class($this)))->getNamespaceName();
                    //Force autoload to fail if the form type is not in the Msf type namespace
                    $dontFails = (new \ReflectionClass($defaultNamespace.'\\'.$shortname.'Type'));
                }catch (\Exception $e){
                    if(! array_key_exists('__default_formType_path',$this->configuration))
                        throw new \Exception("Use of '__default_formType' requires to put FormType classes into the MSFType classpath or to provide namespace with '__default_formType_path'.");
                    $defaultNamespace = $this->configuration['__default_formType_path'];
                }
                $config['formtype'] = $defaultNamespace.'\\'.$shortname.'Type';
            }
            else
                throw new MSFConfigurationNotFoundException($this->getMsfDataLoader()->getState(),'formtype');
        }

        //Adding user fields
        $this->buildMSF();

        /**
         * ici, on pourrait regarder si une fonction init a été fournie. Si oui, utiliser son résultat comme
         * entrée de setcurrentForm()
         */
        $builderInterface = $this->getFormFactory()->createBuilder(
            $config['formtype'],
            $data,
            [
                'action'    =>  $config['action'],
                'method'    =>  $config['method'],
            ]
        );

        $builderInterface->addEventListener(FormEvents::SUBMIT,function (FormEvent $event){

            $config = $this->getLocalConfiguration();
            $undeserialized = $this->getUndeserializedMSFDataLoader();

            try{
                if($event->getForm()->get(self::ACTIONS_PREVIOUS)->isClicked()) {
                    if(array_key_exists('before',$config) ){
                        if(is_callable($config['before']))
                            $action = call_user_func($config['before'], $undeserialized, $event->getData());
                        else
                            $action = $config['before'];

                        if(! empty($action)){
                            $this->configuration[$this->getState()]['before'] = $action;
                        }
                    }
                }
            }catch (OutOfBoundsException $e){}

        });

        $this->setCurrentForm($builderInterface->getForm() );

        if(isset($this->configuration[$this->getState()]['before'])){
            //if( $this->addPrevious){
                $this->addButton(self::ACTIONS_PREVIOUS, $this->configuration['__button_previous']);
            //}
        }
        if(isset($this->configuration[$this->getState()]['cancel'])){
            //if( $this->addCancel){
                $this->addButton(self::ACTIONS_CANCEL, $this->configuration['__button_cancel']);
            //}
        }
        if( ! $builderInterface->getForm()->has(self::ACTIONS_SUBMIT)){
            $this->addButton(self::ACTIONS_SUBMIT, $this->configuration['__button_submit']);
        }



        return $this->getCurrentForm();
    }


    public final function addSubmitButton(array $options)
    {
        $this->configuration['__button_submit'] = $options;
        return $this;
    }

    /**
     * Add cancel button to MSF Form
     * Add the button unless the action is provided or a route found in the configuration
     * cancel route is searched in this order
     * - field 'action' in the provided array
     * - field 'cancel' in the local configuration (see the configure method)
     * - field '__root' in the global configuration
     * @param array $options
     * @return $this
     */
    public final function addCancelButton(array $options)
    {
        $this->configuration['__button_cancel'] = $options;
        if(array_key_exists('action',$options)){
            $this->configuration[$this->getState()]['cancel'] = $options['action'];
            $this->resetLocalConfiguration();
        }

        return $this;
    }

    /**
     * Add the button unless the action is provided or a route found in the configuration
     * If the key 'action' is set, the configuration set in the method configure() will be overwritten.
     * @param array $options
     * @return $this
     */
    public final function addPreviousButton(array $options)
    {
        $this->configuration['__button_previous'] = $options;
        if(array_key_exists('action',$options)) {
            $this->configuration[$this->getState()]['before'] = $options['action'];
            $this->resetLocalConfiguration();
        }

        return $this;
    }

    /**
     * Return the title of the current MSF Page
     * @return mixed
     */
    public final function getLabel(){
        $config = $this->getLocalConfiguration();

        if(array_key_exists('label',$config)){
            return $config['label'];
        }

        return $this->configuration['__title'];
    }

    private function addButton($name, array $options){
        $this->getCurrentForm()->add($name,SubmitType::class,[
            'label'  => isset($options['label']) ? $options['label'] : 'Button',
            'attr'  => isset($options['attr']) ? $options['attr'] : []
        ]);
    }
}