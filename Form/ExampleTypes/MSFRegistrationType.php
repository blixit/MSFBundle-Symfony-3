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
use Blixit\MSFBundle\Exception\MSFFailedToValidateFormException;
use Blixit\MSFBundle\Form\Type\MSFAbstractType;

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
            '__default_paths'=> false,
            '__default_formType'=> true,
            '__final_redirection'=> $this->getRouter()->generate('homepage'),

            'defaultState'=>[
                'entity'    =>  Article::class,
                'validation'=> [$this,'defaultState_validation_callback'],
                'after'     => [$this, 'defaultState_after_callback']
            ],
            'secondState'=>[
                'entity'    =>  Blog::class,
                'before'    =>  [$this, 'secondState_before_callback'],
                'previous_validation'    =>  [$this, 'secondState_previous_validation_callback'],
                'after'=> null,
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
                //'action'    => 'defaultState',
                'attr'      => [
                    'class' => 'btn btn-danger'
                ]
            ]);
        return $this;
    }

    /**
     * Callback for validating the 'defaultState' state
     * @param $msfData
     * @param Article $article
     * @return bool
     * @throws \Exception
     */
    protected function defaultState_validation_callback($msfData, Article &$article){
        if($article->getName() == "Blixit")
            throw new \Exception("Blixit can't be the author");

        return true;
    }

    /**
     * Callback for transiting to the next state
     * @param $msfData
     * @param Article $article
     * @return string
     */
    protected function defaultState_after_callback($msfData, Article $article){
        return 'secondState';
    }

    /**
     * Callback for validating the 'secondState' state on previous
     * @param $msfData
     * @param Blog $blog
     * @return bool
     * @throws \Exception
     */
    protected function secondState_previous_validation_callback($msfData, Blog &$blog){
        if($blog->getTheme() == "blog")
            throw new \Exception("'blog' can't be the theme of the blog");

        return true;
    }

    /**
     * Callback for transiting to the previous state
     * @param $msfData
     * @param Blog|null $blog
     * @return string
     */
    protected function secondState_before_callback($msfData, Blog $blog=null){
       // die("dont go back");

        return 'defaultState';
    }

}