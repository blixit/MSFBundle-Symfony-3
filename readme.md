# MSF Bundle (Symfony 3) #
A bundle to manage MultiStepsForms in Symfony 3


During a simple project, I found that there were not any bundle providing the Multiple Step Form feature. Actually, I found one, 
https://github.com/craue/CraueFormFlowBundle . But for me, this bundle focuses too much on the Forms themselves, which was not relevant to me. For instance, I don't use to handle Form Event.
Then, I came to the idea I could create a bundle which let developper interact with each form separately and then use a little well-configured system to manage the set of forms, the transitions between them, hydratation, ... 

Therefore I created the MSFBundle. It's a little one with currently 5 or 6 classes and works out as a service. Its usage is very similar to form themselves to reduce learning time.

Bonus : with this system, it's easy to manage transitions even between MSF. You just have to configure actions at each step.

## Example of action ##

``` php 
  //using MSF service
  $msf = $this->container->get('msf')->create(MSFRegistrationType::class);
  $form = $msf->getForm();

  $form->handleRequest($request);

  if($form->isSubmitted() && $form->isValid() ) {


      $msf->done() ;
  } 
  
  return $this->render('MSF/default.html.twig', [
      'form'      => $form->createView()
  ]);
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
- Transitions between forms thanks to 3 configurable callbacks : before, after, cancel. These 
methods are invoked by previous, next() and cancel() 
- [Optional] Validation callback 
- [Optional] Buttons navigation (previous, next, cancel)
- Temporary Storage (Database or Session)
- The code is Symfony form like 

## Upcoming Features ##
- Buttons configuration improvement (previous, next, cancel)

## Performances ##
I have not studied performance yet. My main doubt is about use of Serializer. To make easy the use of my service, I let developpers define their own validation methods. To do that, they need to access the whole MSF Data which is stored as a JSON string and to deserialize it. 

## Others usages ##

1. You can attach one of your entities to a MSFDataLoader
For instance, let create a MSF form to manage user registration. Let say the msf contains user, contact and role forms.
You can add a MSFDataLoader field to your user entity. To dynamically attach the created user to this msfdataloader, go to the validation method of the 'user' configuration ( see method configure() ), set the field on the user object and then persist the user.
 
 
Tutos:

- how to add service to the container