<?php
//
//namespace AppBundle\Event;
//
//use AppBundle\Entity\User;
//use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
//use Symfony\Component\EventDispatcher\EventSubscriberInterface;
//use Symfony\Component\EventDispatcher\GenericEvent;
//use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
//use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
//use Symfony\Component\Security\Core\Exception\AccessDeniedException;
//
//class EasyAdminSubscriber implements EventSubscriberInterface
//{
//    private $tokenStorage;
//    private $authorizationChecker;
//
//    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
//    {
//        $this->tokenStorage = $tokenStorage;
//        $this->authorizationChecker = $authorizationChecker;
//    }
//
//    public static function getSubscribedEvents()
//    {
//        return [
//            EasyAdminEvents::PRE_EDIT => 'onPreEdit',
//            EasyAdminEvents::PRE_UPDATE => 'onPreUpdate',
//        ];
//    }
//
//    public function onPreEdit(GenericEvent $event)
//    {
//        $user = $this->tokenStorage->getToken()->getUser();
//        $entity = $event->getSubject();
//    }
//
//    public function onPreUpdate(GenericEvent $event)
//    {
//        $user = $this->tokenStorage->getToken()->getUser();
//        $entity = $event->getSubject();
//    }
//}
