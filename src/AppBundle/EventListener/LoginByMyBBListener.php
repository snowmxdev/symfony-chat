<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\User;
use AppBundle\Utils\ChatConfig;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class LoginByMyBBListener implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var RequestStack
     */
    private $request;
    /**
     * @var UserManagerInterface
     */
    private $userManager;

    public function __construct(Session $session, EntityManagerInterface $em, TokenStorageInterface $tokenStorage,
        RouterInterface $router, RequestStack $request, UserManagerInterface $userManager)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->session = $session;
        $this->userManager = $userManager;
        $this->request = $request->getCurrentRequest();
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if (!ChatConfig::getMyBB()) {
            return;
        }
        $event->stopPropagation();
        $cookie = $this->request->cookies->get('mybbuser');
        if (!$cookie) {
            $event->setController(function()  {
                if ($this->request->server->get('HTTPS')) {
                    $path = 'https://';
                } else {
                    $path = 'http://';
                }
                $path .= $this->request->server->get('SERVER_NAME') . '/member.php?action=login';
                return new RedirectResponse($path);
            });
        }
        if (!($this->tokenStorage->getToken()->getUser() instanceof User)) {
            $connection = $this->em->getConnection()->getWrappedConnection();
            $cookieParts = explode('_', $cookie);
            $userId = $cookieParts[0];

            $value = $connection->prepare('SELECT * FROM mybb_users WHERE uid = :id');
            $value->bindValue(':id', $userId);
            $value->execute();
            $value = $value->fetchAll(\PDO::FETCH_ASSOC);

            if ($value[0]['loginkey'] == $cookieParts[1]) {
                if (!$this->em->find('AppBundle:User', $userId)) {
                    $user = new User();
                    $user->setUsername($value[0]['username']);
                    $user->setId($userId);
                    $user->setEmail($value[0]['email']);
                    $user->setPassword('');
                    if ($value[0]['usergroup'] == 4) {
                        $user->setRoles(['ROLE_ADMIN']);
                    } elseif ($value[0]['usergroup'] == 3) {
                        $user->setRoles(['ROLE_MODERATOR']);
                    } else {
                        $user->setRoles(['ROLE_USER']);
                    }

                    $this->em->persist($user);

                    $metadata = $this->em->getClassMetaData(get_class($user));
                    $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                    $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());

                    $this->em->flush();
                } else {
                    $this->logUser($value);
                    $path = $this->router->generate('add_online');
                    $event->setController(function() use ($path) {
                        return new RedirectResponse($path);
                    });
                }

            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    private function logUser(array $value)
    {
        $user = $this->userManager->findUserByUsername($value[0]['username']);

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        $this->session->set('_security_main', serialize($token));
    }
}