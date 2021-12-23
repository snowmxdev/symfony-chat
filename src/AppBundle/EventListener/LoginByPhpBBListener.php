<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\ListPhs;
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

class LoginByPhpBBListener implements EventSubscriberInterface
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
        if (!ChatConfig::getPhpBB()) {
            return;
        }
        $event->stopPropagation();
        $cookie = (int)$this->request->cookies->get('phpbb3_1umhw_u');
        $cookieSession = $this->request->cookies->get('phpbb3_1umhw_sid');
        if (!$cookie || $cookie == 1 || !$cookieSession) {
            $event->setController(function () {
                if ($this->request->server->get('HTTPS')) {
                    $path = 'https://';
                } else {
                    $path = 'http://';
                }
                $path .= $this->request->server->get('SERVER_NAME') . '/ucp.php?mode=login';
                return new RedirectResponse($path);
            });
        }
        if (!$this->tokenStorage->getToken() || !($this->tokenStorage->getToken()->getUser() instanceof User)) {
            $connection = $this->em->getConnection()->getWrappedConnection();

            $value = $connection->prepare('SELECT * FROM phpbb_sessions WHERE session_user_id = :id and session_id = :sessionId');
            $value->bindValue(':id', (int)$cookie);
            $value->bindValue('sessionId', $cookieSession);
            $value->execute();
            $value = $value->fetchAll(\PDO::FETCH_ASSOC);

            if ($value) {
                $value2 = $connection->prepare('SELECT * FROM phpbb_users WHERE user_id = :id ');
                $value2->bindValue(':id', (int)$cookie);
                $value2->execute();
                $value2 = $value2->fetchAll(\PDO::FETCH_ASSOC);
                if (!$this->em->find('AppBundle:User', $cookie)) {
                    if ($value2[0]) {
                        $user = new User();
                        $user->setUsername($value2[0]['username']);
                        $user->setId($cookie);
                        $user->setEmail($value2[0]['user_email']);
                        $user->setPassword('');
                        $user->setEnabled(1);

                        $this->setUsersRoles($user, $value2);

                        $this->em->persist($user);

                        $metadata = $this->em->getClassMetaData(get_class($user));
                        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                        $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());

                        $this->em->flush();
                    } else {
                        $event->setController(function () {
                            if ($this->request->server->get('HTTPS')) {
                                $path = 'https://';
                            } else {
                                $path = 'http://';
                            }
                            $path .= $this->request->server->get('SERVER_NAME') . '/ucp.php?mode=login';
                            return new RedirectResponse($path);
                        });
                    }
                } else {
                    $this->logUser($value2);
                    $path = $this->router->generate('add_online');
                    $event->setController(
                        function () use ($path) {
                            return new RedirectResponse($path);
                        }
                    );
                }
            } else {
                $event->setController(function () {
                    if ($this->request->server->get('HTTPS')) {
                        $path = 'https://';
                    } else {
                        $path = 'http://';
                    }
                    $path .= $this->request->server->get('SERVER_NAME') . '/ucp.php?mode=login';
                    return new RedirectResponse($path);
                });
            }
        } else {
            $user = $this->tokenStorage->getToken()->getUser();
            $userFromDb = $this->em->getConnection()
                ->getWrappedConnection()
                ->prepare("SELECT * FROM phpbb_users WHERE user_id = {$user->getId()}");
            $userFromDb->execute();
            $userFromDb = $userFromDb->fetchAll(\PDO::FETCH_ASSOC);
            $this->setUsersRoles($user, $userFromDb);
            $this->em->flush();
        }
    }

// insert into fos_user values (1, 'BOT', 'bot', 'bot1@bot.com', 'bot1@bot.com', 1, '', '', NULL, NULL, NULL, 'a:0:{}');

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

    private function setUsersRoles(User &$user, array $value2)
    {
        switch ($value2[0]['group_id']) {
            case 5:
                $user->setRoles(['ROLE_ADMIN']);
                break;
            case 4:
                $user->setRoles(['ROLE_MODERATOR']);
                break;
            default:
                $user->setRoles(['ROLE_USER']);
        }
    }
}