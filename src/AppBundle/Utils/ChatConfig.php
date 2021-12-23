<?php

namespace AppBundle\Utils;

use AppBundle\Entity\User;
use AppBundle\Repository\InviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ChatConfig
{
    /**
     * @var int time in second when user is logout from chat when he is inactivity
     */
    private const INACTIVE_TIME = 180;

    /**
     * @var int Messages limit that can be displayed on first refreshing chat
     */
    private const MESSAGE_LIMIT = 64;

    /**
     * @var array array of channels
     * DO NOT CHANGE FIRST CHANNEL
     */
    private const DEFAULT_CHANNELS = [
        1 => 'Default',
    ];

    /**
     * @var bool Login by MyBB forum user
     */
    private const MYBB = 0;

    /**
     * @var bool Login by phpBB forum user
     */
    private const PHPBB = 0;

    /**
     * @var int moderator channel id
     */
    private const MODERATOR_CHANNEL_ID = 3;

    /**
     * @var int admin channel id
     */
    private const ADMIN_CHANNEL_ID = 4;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $auth;

    /**
     * @var int Bot Id
     */
    private const BOT_ID = 1;

    /**
     * @var int added to private channel id
     */
    private const PRIVATE_CHANNEL_ADD = 1000000;

    /**
     * @var int added to private message channel id
     */
    private const PRIVATE_MESSAGE_ADD = 500000;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(AuthorizationCheckerInterface $auth, EntityManagerInterface $em, SessionInterface $session)
    {
        $this->auth = $auth;
        $this->em = $em;
        $this->session = $session;
    }

    /**
     * @param User $user
     *
     * @return array Array of channels
     */
    public function getChannels(User $user): array
    {
        return self::DEFAULT_CHANNELS +
            $this->specialChannels() +
            $this->getUserPrivateChannel($user) +
            $this->getChannelsFromInvitations($user);
    }

    public static function getBotId(): int
    {
        return self::BOT_ID;
    }

    public static function getMyBB()
    {
        return self::MYBB;
    }

    public static function getPhpBB()
    {
        return self::PHPBB;
    }

    public function getInactiveTime(): int
    {
        return self::INACTIVE_TIME;
    }

    public function getMessageLimit(): int
    {
        return self::MESSAGE_LIMIT;
    }

    public function getUserPrivateChannel(User $user): array
    {
        $channelId = self::PRIVATE_CHANNEL_ADD + $user->getId();
        return [
            $channelId => 'Private'
        ];
    }

    public function getUserPrivateChannelId(User $user): int
    {
        return self::PRIVATE_CHANNEL_ADD + $user->getId();
    }

    public function getUserPrivateMessageChannelId(User $user): int
    {
        return self::PRIVATE_MESSAGE_ADD + $user->getId();
    }

    private function specialChannels(): array
    {
        $array = [];
        if ($this->auth->isGranted('ROLE_ADMIN')) {
            $array[self::ADMIN_CHANNEL_ID] = $this->getChannelName(self::ADMIN_CHANNEL_ID);
        }
        if ($this->auth->isGranted('ROLE_MODERATOR')) {
            $array[self::MODERATOR_CHANNEL_ID] = $this->getChannelName(self::MODERATOR_CHANNEL_ID);
        }
        return $array;
    }

    private function getChannelsFromInvitations(User $user): array
    {
        $invitations = $this->em->getRepository('AppBundle:Invite')->findBy([
            'userId' => $user->getId()
        ]);
        if (!$invitations) {
            return [];
        }

        $return = [];
        foreach ($invitations as $invitation) {
            $return[$invitation->getChannelId()] = $this->getChannelName($invitation->getChannelId());
        }
        return $return;
    }

    private function getChannelName(int $id): string
    {
        switch ($id) {
            case self::ADMIN_CHANNEL_ID:
                return 'Admin';
            case self::MODERATOR_CHANNEL_ID:
                return 'Moderator';
            default:
                return $this->getUserPrivateChannelName($id);
        }
    }

    private function getUserPrivateChannelName(int $id): string
    {
        $id = $id - self::PRIVATE_CHANNEL_ADD;
        return $this->em->find('AppBundle:User', $id)->getUsername();
    }

}