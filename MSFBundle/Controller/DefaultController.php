<?php

namespace Blixit\MSFBundle\Controller;

use Blixit\MSFBundle\Entity\MSFDataLoader;
use Blixit\MSFBundle\Exception\MSFPreviousPageNotFoundException;
use Blixit\MSFBundle\Form\ExampleTypes\ArticleType;
use Blixit\MSFBundle\Form\ExampleTypes\MSFRegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{

    /**
     * @Route("/msfbundle", name="msfbundle")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request){

        /**
         * Symfony Form
         */
        $form = $this->createForm(ArticleType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //return $this->redirectToRoute('ads_show', array('id' => $ad->getId()));
        }

        /**
         * Blixit MSFBundle Form
         */
        $msf = $this->container->get('msf')->create(MSFRegistrationType::class);
        $form2 = $msf->getForm();

        $form2->handleRequest($request);

        if($form2->isSubmitted() && $form2->isValid() ) {

            $msf->done() ;
        }


        return $this->render('BlixitMultiStepFormBundle:Default:default.html.twig',[
            'form'  =>  $form->createView(),
            'form2'  =>  $form2->createView()
        ]);
    }


















    public function msfAction(Request $request){

        $msf = $this->container->get('msf')
            ->create(MSFRegistrationType::class);

        $form = $msf->getForm();
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() ) {
            try{
                $msf->previous();
            }catch (MSFPreviousPageNotFoundException $e){  }

            $msf->next();
        }

        return $this->render('BlixitMultiStepFormBundle:Default:default.html.twig',[
            'form'      => $form->createView()
        ]);
    }

    public function zindexAction(Request $request)
    {
        //$msfdataloader = $this->getDoctrine()->getRepository('BlixitMultiStepFormBundle:MSFDataLoader')
        //    ->findOneBy(['id'=>2]);

        //using MSF service
        $msf = $this->container->get('msf.registration');
        $msf->init('username');
        $msf_form = $msf->getForm();

        $msf_form->handleRequest($request);

        if($msf_form->isSubmitted() && $msf_form->isValid() ){
            try{
                $msf->previous();
            }catch (MSFPreviousPageNotFoundException $e){  }

            //for instance, persist entity
            //you can also use configuration to set a callback to use for the form validation

            //go to the following form or be redirected to configurated location
            $msf->next();
        }
        //var_dump($msf_form);

        return $this->render('BlixitMultiStepFormBundle:Default:default.html.twig',[
            'form'      => $msf_form->createView()
        ]);
    }
}
