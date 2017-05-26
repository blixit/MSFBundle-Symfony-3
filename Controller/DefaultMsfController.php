<?php

namespace Blixit\MSFBundle\Controller;

use Blixit\MSFBundle\Entity\Example\Blog;
use Blixit\MSFBundle\Entity\MSFDataLoader;
use Blixit\MSFBundle\Exception\MSFPreviousPageNotFoundException;
use Blixit\MSFBundle\Form\ExampleTypes\ArticleType;
use Blixit\MSFBundle\Form\ExampleTypes\MSFRegistrationLinearType;
use Blixit\MSFBundle\Form\ExampleTypes\MSFRegistrationType;
use Blixit\MSFBundle\Form\TemplateTypes\MSFLinearType;
use Blixit\MSFBundle\Form\TemplateTypes\MSFTesterType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DefaultMsfController extends Controller
{

    /**
     * @Route("/msfbundle", name="msfbundle")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request){

        $jms = $this->container->get('jms_serializer');
        $session = $this->container->get('session');

        //$session->remove('__msf_dataloader');
        //$session->save();


        /**
         * Blixit MSFBundle Form
         */
        $msf = $this->container->get('msf')->create(MSFTesterType::class,"blog");


        /*
        $form = $msf->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() ) {

            //$msf->previous() ;
            $msf->next() ;
        }
        */


        return $this->render('BlixitMultiStepFormBundle:Default:default.html.twig',[
            //'form'  =>  $form->createView(),
            //'title' => $msf->getLabel(),
            //'msf_steps'=> $msf->getStepsWithLink('msfbundle',[])
        ]);
    }
}
