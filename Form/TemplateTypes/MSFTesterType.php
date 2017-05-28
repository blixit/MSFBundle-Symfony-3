<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 26/05/17
 * Time: 14:56
 */

namespace Blixit\MSFBundle\Form\TemplateTypes;

use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Entity\Example\Article;
use Blixit\MSFBundle\Entity\Example\Blog;
use Blixit\MSFBundle\Form\Type\MSFAbstractType;

class MSFTesterType
    extends MSFAbstractType
{
    function __construct(MSFService $msf, $defaultState)
    {
        parent::__construct($msf, $defaultState);
    }

    public function configure()
    {
        return [
            '__default_paths'=>true,
            '__default_formType_path'=>'\Blixit\MSFBundle\Form\ExampleTypes',
            '__root'=>'msfbundle',
            '__final_redirection'=>'msfbundle',
            '__on_cancel'=>['redirection'=>$this->getRouter()->generate('msfbundle')],
            '__on_terminate'=>['destroy_data'=>true],

            'blog'  =>  [
                'label'=> "Blog",
                'entity'    => Blog::class,
                'after'    => function($msfData){

                    return 'article';
                },
                'validation' => function(){
                    return true;
                }
            ],
            'article'  =>  [
                'label'=> "Article",
                'entity'    => Article::class,
                'before'    => 'blog',
                'after'    => function($msfData){

                    return null;
                },
                'validation' => function(){
                    return true;
                }
            ]
        ];
    }

    /**
     * Modify the form
     * @return $this
     */
    public function buildMSF()
    {
        return $this->addSubmitButton([
            'label'=>'Valider',
            'attr'=>[
                'class'=>"inline btn btn-primary"
            ]
            ])->addCancelButton([
                'label'=>'Annuler',
                'attr'=>[
                    'class'=>"inline btn btn-danger"
                ]
            ])->addNextButton([
                'label'=>'Suivant',
                'attr'=>[
                    'class'=>"inline btn btn-warning pull-right"
                ]
            ])
            ->addPreviousButton([
            'label'=>'Précédent',
            'attr'=>[
                'class'=>"inline btn btn-warning"
                ]
            ])
            ;
    }

}