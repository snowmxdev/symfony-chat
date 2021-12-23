<?php

namespace AppBundle\EventListener;

use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

//https://stackoverflow.com/a/41473697
class RegistrationCompleteListener implements EventSubscriberInterface
{

    private $router;

    public  function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::REGISTRATION_SUCCESS => ['onUserRegistrationComplete', -30],
        );
    }

    /**
     * @param FormEvent $event
     * When the user registration is completed redirect
     * to the add user online to database page
     */
    public  function  onUserRegistrationComplete(FormEvent $event)
    {
        $url = $this->router->generate('add_online');

        $event->setResponse(new RedirectResponse($url));

    }

}