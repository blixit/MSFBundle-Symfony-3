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

    const ACTIONS_SUBMIT = 'save'; // default symfony action for submit
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
        try{
            $undeserialized = $this->getUndeserializedMSFDataLoader();
            if(array_key_exists($this->getState(), $undeserialized))
                $data = $undeserialized[$this->getState()];

        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }

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
        $form = $this->getFormFactory()->createBuilder(
            $config['formtype'],
            $data,
            [
                'action'    =>  $config['action'],
                'method'    =>  $config['method'],
            ]
        )->getForm();

        $this->setCurrentForm($form);

        if( ! $this->isAvailable($this->getLocalConfiguration()['after'])){
            /*if(! empty($this->getConfiguration()['msf_btn_next']['active'])){
                $this->addButton(self::ACTIONS_NEXT, $this->getConfiguration()['msf_btn_next']);
            }*/
            $conf = $this->getConfiguration()[self::ACTIONS_NEXT];
            $conf['active'] = false;
            $this->setConfigurationWith(self::ACTIONS_NEXT,$conf);
        }
        if( ! $this->isAvailable($this->getLocalConfiguration()['before'])){
            /*if(! empty($this->getConfiguration()['msf_btn_previous']['active'])){
                $this->addButton(self::ACTIONS_PREVIOUS, $this->getConfiguration()['msf_btn_previous']);
            }*/
            $conf = $this->getConfiguration()[self::ACTIONS_PREVIOUS];
            $conf['active'] = false;
            $this->setConfigurationWith(self::ACTIONS_PREVIOUS,$conf);
        }
        /*
        if(array_key_exists('msf_btn_cancel',$this->getConfiguration())){
            //if( $this->addCancel){
            $this->addButton(self::ACTIONS_CANCEL, $this->getConfiguration()['msf_btn_cancel']);
            //}
        }
        if(array_key_exists('msf_btn_submit',$this->getConfiguration())){
            $this->addButton(self::ACTIONS_SUBMIT, $this->getConfiguration()['msf_btn_submit']);
        }
        */



        return $this->getCurrentForm();
    }


    public final function addSubmitButton(array $options = [])
    {
        $options['active'] = true;
        $this->setConfigurationWith('msf_btn_submit', $options);
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
        $options['active'] = true;
        $this->setConfigurationWith('msf_btn_cancel', $options);
        if(array_key_exists('action',$options)){
            $this->setLocalConfiguration('cancel',$options['action']);
        }
        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public final function addNextButton(array $options = [])
    {
        $options['active'] = true;
        $this->setConfigurationWith('msf_btn_next', $options);
        if(array_key_exists('action',$options)){
            $this->setLocalConfiguration('after',$options['after']);
        }
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
        $options['active'] = true;
        $this->setConfigurationWith('msf_btn_previous', $options);
        if(array_key_exists('action',$options)){
            $this->setLocalConfiguration('before',$options['before']);
        }
        return $this;
    }

    private function addButton($name, array $options){
        $this->getCurrentForm()->add($name,SubmitType::class,[
            'label'  => isset($options['label']) ? $options['label'] : 'Button',
            'attr'  => isset($options['attr']) ? $options['attr'] : []
        ]);
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
        $tmp = preg_grep("/^msf_btn_/", array_keys($this->getConfiguration()));
        if(empty($this->getConfiguration()['__root']))
            throw new MSFConfigurationNotFoundException('','',"GetButtons() requires '__root' parameter. ");

        $stepsLinks = $this->getStepsWithLink($this->getConfiguration()['__root'],[],true);

        foreach ($tmp as $item){
            $tmp[$item] = $this->getConfiguration()[$item];
            if($item == self::ACTIONS_CANCEL)
                $tmp[$item]['link'] = $stepsLinks[$this->getState()]['linkcancel'];
            if($item == self::ACTIONS_PREVIOUS)
                $tmp[$item]['link'] = $stepsLinks[$this->getState()]['linkbefore'];
            if($item == self::ACTIONS_NEXT)
                $tmp[$item]['link'] = $stepsLinks[$this->getState()]['linkafter'];
        }
        return $tmp;
    }
}