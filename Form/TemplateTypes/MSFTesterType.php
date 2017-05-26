<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 26/05/17
 * Time: 14:56
 */

namespace Blixit\MSFBundle\Form\TemplateTypes;

use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Entity\Example\Blog;
use Blixit\MSFBundle\Form\Type\MSFFlowType;

class MSFTesterType
    extends MSFFlowType
{
    function __construct(MSFService $msf, $defaultState)
    {
        parent::__construct($msf, $defaultState);
    }

    public function configure()
    {
        return [
            '__default_formType_path'=>'\Blixit\MSFBundle\Form\ExampleTypes',
            '__root'=>'msfbundle',
            '__on_cancel'=>['redirection'=>$this->getRouter()->generate('msfbundle')],

            'blog'  =>  [
                'entity'    => Blog::class,
                'after'    => function($msfData){

                    return 'blog';
                },
            ]
        ];
    }

    /**
     * Modify the form
     * @return $this
     */
    public function buildMSF()
    {
        // TODO: Implement buildMSF() method.
    }

}