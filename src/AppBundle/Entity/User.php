<?php

namespace AppBundle\Entity;

use AppBundle\Utils\ChatConfig;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="UserOnline", mappedBy="userInfo")
     */
    protected $userOnline;

    /**
     * @ORM\OneToMany(targetEntity="Message", mappedBy="userInfo")
     */
    protected $userMessage;

    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->userMessage = new ArrayCollection();
    }

    /**
     * @param int $id
     * Changing id available only if integrating with forum.
     * Otherwise use autoincrementing
     */
    public function setId(int $id)
    {
        if (ChatConfig::getPhpBB() || ChatConfig::getMyBB()) {
            $this->id = $id;
        }
    }

    /**
     * @return string User's role change to text (used as css class)
     */
    public function getChatRoleAsText()
    {
        $role = $this->getRoles();
        switch ($role[0]) {
            case 'ROLE_ADMIN':
                return 'administrator';
            case 'ROLE_MODERATOR':
                return 'moderator';
            default:
                return 'user';
        }

    }

    /**
     * Changes User's role, removes other roles
     *
     * @param string $role Role that User will have
     *
     * @return $this
     */
    public function changeRole($role)
    {
        switch ($role) {
            case 'user':
                $this->removeRole('ROLE_ADMIN');
                $this->removeRole('ROLE_MODERATOR');
                break;
            case 'moderator':
                $this->removeRole('ROLE_ADMIN');
                $this->removeRole('ROLE_USER');
                $this->addRole('ROLE_MODERATOR');
                break;
            case 'administrator':
                $this->removeRole('ROLE_MODERATOR');
                $this->removeRole('ROLE_USER');
                $this->addRole('ROLE_ADMIN');
                break;
        }

        return $this;
    }
}
