<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserOnline
 *
 * @ORM\Table(name="user_online")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserOnlineRepository")
 */
class UserOnline
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", unique=true)
     */
    private $userId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="online_time", type="datetime")
     */
    private $onlineTime;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userOnline")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $userInfo;

    /**
     * $var int
     *
     * @ORM\Column(name="channel", type="integer")
     */
    private $channel;

    /**
     * $var bool
     *
     * @ORM\Column(name="typing", type="boolean", nullable=true)
     */
    private $typing;

    /**
     * @return int Channel's id
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param int $channel Channel's id
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * Gets User's username from relation
     *
     * @return string
     */
    public function getUserName(): string
    {
        return $this->userInfo->getUsername();
    }

    /**
     * Gets User's role from relation
     *
     * @return int Return user's role as text
     */
    public function getRole():string
    {
        return $this->userInfo->getChatRoleAsText();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userId
     *
     * @param int $userId
     *
     * @return UserOnline
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set onlineTime
     *
     * @param \DateTime $onlineTime
     *
     * @return UserOnline
     */
    public function setOnlineTime($onlineTime)
    {
        $this->onlineTime = $onlineTime;

        return $this;
    }

    /**
     * @return User User's info from relation
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * Get onlineTime
     *
     * @return \DateTime
     */
    public function getOnlineTime()
    {
        return $this->onlineTime;
    }

    /**
     * Set userInfo
     *
     * @param User $userInfo
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;
    }

    /**
     * Create array with user's information: id, username and role
     *
     * @return array Array with information about user
     */
    public function createArrayToJson()
    {
        return [
            'user_id' => $this->userId,
            'username' => $this->userInfo->getUsername(),
            'user_role' => $this->userInfo->getChatRoleAsText(),
            'typing' => $this->typing
        ];
    }

    /**
     * @param mixed $typing
     *
     * @return UserOnline
     */
    public function setTyping($typing)
    {
        $this->typing = $typing;

        return $this;
}

    /**
     * @return mixed
     */
    public function getTyping()
    {
        return $this->typing;
    }
}

