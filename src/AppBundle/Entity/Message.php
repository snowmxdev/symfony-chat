<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Message
 *
 * @ORM\Table(name="message")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MessageRepository")
 */
class Message
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
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string", length=1000)
     */
    private $text;

    /**
     * @var int
     *
     * @ORM\Column(name="channel", type="integer")
     */
    private $channel;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userMessage")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $userInfo;

    /**
     * Adds User to relation
     *
     * @param User $userInfo User instance
     *
     * @return $this
     */
    public function setUserInfo(User $userInfo)
    {
        $this->userInfo = $userInfo;

        return $this;
    }

    /**
     * Gets User's username from relation
     *
     * @return string Username
     */
    public function getUsername(): string
    {
        return $this->userInfo->getUsername();
    }

    /**
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
     * @param integer $userId
     *
     * @return Message
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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Message
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

    /**
     * Set text
     *
     * @param string $text
     *
     * @return Message
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set channel
     *
     * @param integer $channel
     *
     * @return Message
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Get channel
     *
     * @return int
     */
    public function getChannel()
    {
        return $this->channel;
    }

}

