<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 17:30
 */

namespace Blixit\MSFBundle\Form\ExampleTypes;


use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Entity\Example\Article;
use Blixit\MSFBundle\Entity\Example\Blog;
use Blixit\MSFBundle\Form\Type\MSFAbstractType;
use JMS\Serializer\Serializer;

class MSFRegistrationType
    extends MSFAbstractType
{

    /**
     * RegistrationType constructor.
     * @param MSFService $msf
     */
    function __construct(MSFService $msf, $defaultState = 'defaultState')
    {
        parent::__construct($msf, $defaultState);
    }

    public function configure()
    {
        return [
            '__default_paths'=> true,
            '__final_redirection'=> $this->getRouter()->generate('homepage'),

            'defaultState'=>[
                'formtype'  =>  ArticleType::class,
                'entity'    =>  Article::class,
                'action'    =>  $this->getRouter()->generate('msfbundle'),
                'method'    =>  'POST',
                'after'     => function ($msfData, Article $article, Serializer $serializer){
                    //$serialized = json_encode($msfData['username']);
                    //$user = $serializer->deserialize($serialized, FosUsers::class,'json');

                    return 'secondState';
                },
            ],
            'secondState'=>[
                'formtype'  =>  BlogType::class,
                'entity'    =>  Blog::class,
                'action'    =>  $this->getRouter()->generate('msfbundle'),
                'method'    =>  'POST',
                'before'    =>  'defaultState',
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
            'label'     => 'Soumettre'
        ])
            ->addCancelButton([
                'label'     => 'Annuler',
                'action'    =>  $this->getRouter()->generate('homepage'),
                'attr'      => [
                    'class' => 'btn btn-primary'
                ]
            ])
            ->addPreviousButton([
                'label'     => 'Retour',
                //'action'    =>  $this->getRouter()->generate('homepage'),
                'attr'      => [
                    'class' => 'btn btn-danger'
                ]
            ]);
        return $this;
    }

}