<?php

namespace AppBundle\Repository;

/**
 * UserOnlineRepository
 *
 */
class UserOnlineRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Delete users online from database, except User, if date in database is older than (now - inactive time from config)
     *
     * @param \DateTime $date date when user must be kicked from chat
     * @param int $id User's id
     * @param int $channel Channel's id
     *
     */
    public function deleteInactiveUsers(\DateTime $date, int $id, int $channel)
    {
        $this->createQueryBuilder('u')
            ->delete()
            ->where('u.onlineTime <= :date')
            ->andWhere('u.userId != :id')
            ->andWhere('u.channel = :channel')
            ->setParameter('id', $id)
            ->setParameter('date', $date)
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets online users except User
     *
     * @param int $id User's id
     * @param int $channel Channel's id
     *
     * @return array|null Array of users online or null if no users online
     */
    public function findAllOnlineUserExceptUser(int $id, int $channel)
    {
        return $this->createQueryBuilder('u')
                ->where('u.userId != :id')
                ->andWhere('u.channel = :channel')
                ->setParameter('id', $id)
                ->setParameter('channel', $channel)
                ->getQuery()
                ->getResult();
    }

}
