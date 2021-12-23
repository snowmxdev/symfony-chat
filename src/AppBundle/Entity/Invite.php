<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invite
 *
 * @ORM\Table(name="invite")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\InviteRepository")
 */
class Invite
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
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="channel_id", type="integer")
     */
    private $channelId;

    /**
     * @var int
     *
     * @ORM\Column(name="inviter_id", type="integer")
     */
    private $inviterId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;


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
     * @param integer $userId
     *
     * @return Invite
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
     * Set channelId
     *
     * @param integer $channelId
     *
     * @return Invite
     */
    public function setChannelId($channelId)
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * Get channelId
     *
     * @return int
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * Set inviterId
     *
     * @param integer $inviterId
     *
     * @return Invite
     */
    public function setInviterId($inviterId)
    {
        $this->inviterId = $inviterId;

        return $this;
    }

    /**
     * Get inviterId
     *
     * @return int
     */
    public function getInviterId()
    {
        return $this->inviterId;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Invite
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
}

