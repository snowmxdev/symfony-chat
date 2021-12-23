<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class AdminControllerTest
 * @package Tests\AppBundle\Controller
 */
class AdminControllerTest extends WebTestCase
{
    /**
     * @var $client
     */
    private $client = null;

    /**
     * Setup for tests
     */
    public function setUp()
    {
        $this->client = static::createClient();
    }

    /**
     * Checks if User that is not admin cannot access admin panel
     *
     * @dataProvider getUrls
     */
    public function testIfNotAdminCanNotAccessAdminPanel($httpMethod, $url)
    {
        $this->logInRandomValidUser('ROLE_USER');

        $this->client->request($httpMethod, $url);
        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode(), 'User CAN access admin panel but should not');
    }

    /**
     * Checks if User that is admin can access admin panel
     *
     * @dataProvider getUrls
     */
    public function testIfAdminCanAccessAdminPanel($httpMethod, $url)
    {
        $this->logInRandomValidUser('ROLE_ADMIN');

        $this->client->request($httpMethod, $url);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), 'Admin CANNOT access admin panel but should');
    }

    /**
     * Checks if User that is admin cannot access not existing route
     */
    public function testIfAdminCanNotAccessNonExistingRoute()
    {
        $this->logInRandomValidUser('ROLE_ADMIN');

        $this->client->request('GET', '/chat/admin/any_non_existing_route');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), 'Admin CAN access non existing route but should not');
    }

    /**
     * Checks if User that is not logged cannot access admin panel
     *
     * @dataProvider getUrls
     */
    public function testIfNotLoggedUserCanNotAccessNonExistingRoute($httpMethod, $url)
    {
        $this->client->request($httpMethod, $url);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode(), 'Not logged user CAN access admin panel but should not');
    }

    public function getUrls()
    {
        yield ['GET', '/chat/admin/'];
        yield ['GET', '/pl/chat/admin/'];
    }
    /**
     * Authenticate random user or admin
     *
     * @param string $role ROLE_USER OR ROLE_ADMIN
     *
     */
    private function logInRandomValidUser(string $role)
    {
        // from: https://stackoverflow.com/a/30555103/6912075
        $session = $this->client->getContainer()->get('session');

        $userManager = $this->client->getContainer()->get('fos_user.user_manager');

        $user = $userManager->findUserByUsername('anyUser');
        if (!$user) {
            $user = $userManager->createUser();

            $user->setEmail('test1@example.com');
            $user->setUsername('anyUser');
            $user->setPlainPassword('foo');
            $user->setEnabled(true);
            $user->addRole($role);

            $userManager->updateUser($user);
        }

        $firewall = 'main';
        $token = new UsernamePasswordToken($user, null, $firewall, [$role]);

        $session->set('_security_'.$firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

}