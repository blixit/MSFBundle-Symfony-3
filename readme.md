# MSF Bundle (Symfony 3) #
A bundle to manage MultiStepsForms in Symfony 3


During a simple project, I found that there were not any bundle providing the Multiple Step Form feature. Actually, I found one, 
https://github.com/craue/CraueFormFlowBundle . But for me, this bundle focuses too much on the Forms themselves, which was not relevant to me. For instance, I don't use to handle Form Event.
Then, I came to the idea I could create a bundle which let developper interact with each form separately and then use a little well-configured system to manage the set of forms, the transitions between them, hydratation, ... 

Therefore I created the MSFBundle. It's a little one with currently 5 or 6 classes and works out as a service. Its usage is very similar to forms themselves to reduce learning time.

You just need ONE MsfFormType CLASS to go !!

Bonus : with this system, it's easy to manage transitions even between MSF. You just have to configure actions at each step.

## Example of Controller ##

``` php 
/**
 * @Route("/msfbundle", name="msfbundle")
 * @return \Symfony\Component\HttpFoundation\Response
 */
public function indexAction(Request $request){ 
    
    //getting service and creating form
    $msf = $this->container->get('msf')->create(MSFTesterType::class,"blog");
    
    //getting symfony form
    $form = $msf->getForm(); 

    //request handling
    $form->handleRequest($request);

    if($form->isSubmitted() && $form->isValid() ) {
        //validation
        $msf->done() ;
    }

    return $this->render('BlixitMultiStepFormBundle:Default:default.html.twig',[
        'form'  =>  $form->createView(),
        'title' => $msf->getLabel(),
        'buttons' => $msf->getButtons(), 
        'menu'  => $msf->getMenu('msfbundle'),
    ]);
}
```

## Example of MSFFormType : MSFTesterType with transition callbacks ##

``` php 
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
```

## Dependencies ##
- Symfony 3
- JMS Serializer (Need to install JMS)
- Doctrine Orm Entity Manager
- Router 
- Request Stack
- Form Factory
- Session

Use of third-party php libraries is not recommended following the symfony best practices. However, I assume
use of JMS/Serializer (See https://symfony.com/doc/current/bundles/best_practices.html#vendors )

## Features ##
- Transitions between forms thanks to 3 configurable callbacks : before, after, cancel.  
- [Optional] Validation callback 
- [Optional] Buttons navigation (submit, previous, next, cancel)
- Session Storage 
- The code is Symfony form like  

## Performances ##
I have not studied performance yet. My main doubt is about use of Serializer. To make easy the use of my service, I let developpers define their own validation methods. To do that, they need to access the whole MSF Data which is stored as a JSON string and to deserialize it.
My second doubt is about use of arrays.

## Others usages ##

1. You can attach one of your entities to a MSFDataLoader
For instance, let create a MSF form to manage user registration. Let say the msf contains user, contact and role forms.
You can add a MSFDataLoader field to your user entity. To dynamically attach the created user to this msfdataloader, go to the validation method of the 'user' configuration ( see method configure() ), set the field on the user object and then persist the user.
