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

    const ACTIONS_SUBMIT = 'msf_btn_submit';
    const ACTIONS_CANCEL = 'msf_btn_cancel';
    const ACTIONS_NEXT = 'msf_btn_next';
    const ACTIONS_PREVIOUS = 'msf_btn_previous';

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
        $config = $this->getLocalConfiguration();

        if(! array_key_exists('entity', $config))
            throw new MSFConfigurationNotFoundException($this->getMsfDataLoader()->getState(),'entity');

        $data = null;
        $undeserialized = $this->getUndeserializedMSFDataLoader();

        if(array_key_exists($this->getState(), $undeserialized))
            $data = $undeserialized[$this->getState()];


        if(! array_key_exists('action', $config)){
            $config['action'] = $this->getConfiguration()['__root'];
        }

        if(! array_key_exists('method', $config)){
            $config['method'] = $this->getConfiguration()['__method'];
        }

        if(! array_key_exists('formtype', $config)){
            if($this->getConfiguration()['__default_formType']){
                $shortname = (new \ReflectionClass($config['entity']))->getShortName();
                try{
                    $defaultNamespace = (new \ReflectionClass(get_class($this)))->getNamespaceName();
                    //Force autoload to fail if the form type is not in the Msf type namespace
                    $dontFails = (new \ReflectionClass($defaultNamespace.'\\'.$shortname.'Type'));
                }catch (\Exception $e){
                    if(! array_key_exists('__default_formType_path',$this->getConfiguration()))
                        throw new \Exception("Use of '__default_formType' requires to put FormType classes into the MSFType classpath or to provide namespace with '__default_formType_path'.");
                    $defaultNamespace = $this->getConfiguration()['__default_formType_path'];
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
        $form = $this->getFormFactory()->create(
            $config['formtype'],
            $data,
            [
                'action'    =>  $config['action'],
                'method'    =>  $config['method'],
            ]
        );

        $this->setCurrentForm($form);

        if( ! $this->isAvailable($this->getLocalConfiguration()['after'])){
            $this->setConfigurationWith('__buttons_have_next',false);
        }
        if( ! $this->isAvailable($this->getLocalConfiguration()['before'])){
            $this->setConfigurationWith('__buttons_have_previous',false);
        }

        //var_dump($this->getConfiguration()); die;


        return $this->getCurrentForm();
    }


    public final function addSubmitButton(array $options = [])
    {
        $this->setConfigurationWith('__buttons_have_submit', true);
        $this->setConfigurationWith(self::ACTIONS_SUBMIT, $options);
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
    public final function addCancelButton(array $options = [])
    {
        $this->setConfigurationWith('__buttons_have_cancel', true);
        $this->setConfigurationWith(self::ACTIONS_CANCEL, $options);
        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public final function addNextButton(array $options = [])
    {
        $this->setConfigurationWith('__buttons_have_next', true);
        $this->setConfigurationWith(self::ACTIONS_NEXT, $options);
        return $this;
    }

    /**
     * Add the button unless the action is provided or a route found in the configuration
     * If the key 'action' is set, the configuration set in the method configure() will be overwritten.
     * @param array $options
     * @return $this
     */
    public final function addPreviousButton(array $options = [])
    {
        $this->setConfigurationWith('__buttons_have_previous', true);
        $this->setConfigurationWith(self::ACTIONS_PREVIOUS, $options);
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

        return $this->getConfiguration()['__title'];
    }

    public function getButtons(){
        $tmpButtons = preg_grep("/^msf_btn_/", array_keys($this->getConfiguration()));
        if(empty($this->getConfiguration()['__root']))
            throw new MSFConfigurationNotFoundException('','',"GetButtons() requires '__root' parameter. ");

        $stepsLinks = $this->getStepsWithLink($this->getConfiguration()['__root'],[],true);

        $buttonsToAdd = [];
        foreach ($tmpButtons as $k => $item){
            if($item == self::ACTIONS_SUBMIT && $this->getConfiguration()['__buttons_have_submit']){
                $buttonsToAdd[$item] = $this->getConfiguration()[$item];
            }
            elseif($item == self::ACTIONS_CANCEL && $this->getConfiguration()['__buttons_have_cancel']){
                $buttonsToAdd[$item] = $this->getConfiguration()[$item];
                $buttonsToAdd[$item]['link'] = $stepsLinks[$this->getState()]['linkcancel'];
            }
            elseif($item == self::ACTIONS_PREVIOUS && $this->getConfiguration()['__buttons_have_previous']){
                $buttonsToAdd[$item] = $this->getConfiguration()[$item];
                $buttonsToAdd[$item]['link'] = $stepsLinks[$this->getState()]['linkbefore'];
            }
            elseif($item == self::ACTIONS_NEXT && $this->getConfiguration()['__buttons_have_next'] ){
                $buttonsToAdd[$item] = $this->getConfiguration()[$item];
                $buttonsToAdd[$item]['link'] = $stepsLinks[$this->getState()]['linkafter'];
            }
        }
        return $buttonsToAdd;
    }
}