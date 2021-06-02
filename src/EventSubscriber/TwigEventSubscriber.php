<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment; 
use App\Repository\ConferenceRepository;

class TwigEventSubscriber implements EventSubscriberInterface
{
    private $twig;
    private $conferenceRepository;

    public function __construct(Environment $twig, ConferenceRepository $conferenceRepository){
        $this->twig = $twig;
        $this->conferenceRepository = $conferenceRepository;
    }
    
    public function onKernelController(ControllerEvent $event)
    {
        $conferences = $this->conferenceRepository->findAll();
        $this->twig->addGlobal('conferences', $conferences);
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }
}
