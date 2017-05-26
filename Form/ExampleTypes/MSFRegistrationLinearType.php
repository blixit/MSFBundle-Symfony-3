<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 24/05/17
 * Time: 16:31
 */

namespace Blixit\MSFBundle\Form\ExampleTypes;


use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Entity\Example\Article;
use Blixit\MSFBundle\Entity\Example\Blog;
use Blixit\MSFBundle\Entity\Example\Image;
use Blixit\MSFBundle\Form\TemplateTypes\MSFLinearType;

class MSFRegistrationLinearType
    extends MSFLinearType
{

    /**
     * RegistrationType constructor.
     * @param MSFService $msf
     */
    function __construct(MSFService $msf, $defaultState = 'blog')
    {
        parent::__construct($msf, $defaultState);
    }

    public function configure()
    {
        return [
            '__default_paths'=> false,
            '__default_formType'=> true,
            '__default_formType_path'=> "\Blixit\MSFBundle\Form\ExampleTypes",
            '__final_redirection'=> $this->getRouter()->generate('homepage'),

            'blog'=>[
                'label'     => "Création du blog",
                'entity'    =>  Blog::class,
                'validation'=> function($formData, &$blog){return true;}
            ],
            'article'=>[
                'label'     => "Ajout d'un article",
                'entity'    =>  Article::class,
                'previous_validation'    =>  function($formData, &$article){return true;},
            ],
            'image'=>[
                'label'     => "Ajout d'une image pour l'article",
                'entity'    =>  Image::class,
                'previous_validation'    =>  function($formData, &$image){return true;},
                'redirection'    =>  $this->getRouter()->generate('homepage'),
            ]
        ];
    }

    /**
     * Modify the form
     * @return $this
     */
    public function buildMSF()
    {
        $this->addSubmitButton([
            'label'     => 'Submit',
            'attr'      => [
                'class' => 'btn btn-primary'
            ]
        ])
            ->addCancelButton([
                'label'     => 'Cancel',
                'attr'      => [
                    'class' => 'btn btn-primary'
                ]
            ])
            ->addPreviousButton([
                'label'     => 'Previous',
                'attr'      => [
                    'class' => 'btn btn-danger'
                ]
            ]);
        $this->initTransitions();

        return $this;
    }
}