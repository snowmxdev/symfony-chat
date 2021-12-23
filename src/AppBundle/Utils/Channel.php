<?php

namespace AppBundle\Utils;

use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Service to change user's channel on chat
 *
 * Class Channel
 * @package AppBundle\Utils
 */
class Channel
{
    /**
     * @var ChatConfig
     */
    private $config;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var UserOnline
     */
    private $userOnline;

    /**
     * Channel constructor.
     *
     * @param ChatConfig $config
     * @param SessionInterface $session
     * @param UserOnline $userOnline
     */
    public function __construct(ChatConfig $config, SessionInterface $session, UserOnline $userOnline)
    {
        $this->config = $config;
        $this->session = $session;
        $this->userOnline = $userOnline;
    }

    /**
     * Check if channel exists and then update User's information in session and users online in database
     * about User's channel
     *
     * @param User $user User instance
     *
     * @param int $channel channel's Id
     *
     * @return bool status of changing channel
     */
    public function changeChannelOnChat(User $user, int $channel): bool
    {
        if (!$this->checkIfUserCanBeOnThatChannel($user, $channel)) {
            return false;
        }
        $this->userOnline->updateUserOnline($user, $channel, 0);

        $this->session->set('channel', $channel);
        $this->session->set('changedChannel', 1);

        return true;
    }

    public function checkIfUserCanBeOnThatChannel(User $user, ?int $channel): bool
    {
        return array_key_exists($channel, $this->config->getChannels($user));
    }
}