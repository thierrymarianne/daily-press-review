<?php

namespace App\Api\Repository;

use App\Api\Entity\Whisperer;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class WhispererRepository extends ResourceRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     * @param string         $aggregate
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        string $aggregateClass
    )
    {
        parent::__construct($managerRegistry, $aggregateClass);
    }

    /**
     * @param Whisperer $whisperer
     * @return Whisperer
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareWhisperer(Whisperer $whisperer)
    {
        $preExistingWhisperer = $this->findOneBy(['name' => $whisperer->getName()]);

        if ($preExistingWhisperer instanceof Whisperer) {
            $preExistingWhisperer->setExpectedWhispers($whisperer->getExpectedWhispers());
            $whisperer = $this->rotateWhispers($preExistingWhisperer, $whisperer->getWhispers());
        }

        return $this->saveWhisperer($whisperer);
    }

    /**
     * @param Whisperer $whisperer
     * @param int $whispers
     * @return Whisperer
     */
    protected function rotateWhispers(Whisperer $whisperer, $whispers)
    {
        $whisperer->setPreviousWhispers($whisperer->getWhispers());

        return $whisperer->setWhispers($whispers);
    }

    /**
     * @param Whisperer $whisperer
     * @return Whisperer
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveWhisperer(Whisperer $whisperer)
    {
        $whisperer->setUpdatedAt(new \DateTime());

        $this->getEntityManager()->persist($whisperer);
        $this->getEntityManager()->flush();

        return $whisperer;
    }

    /**
     * @param $whisperer
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function forgetAboutWhisperer(Whisperer $whisperer)
    {
        $this->getEntityManager()->remove($whisperer);
        $this->getEntityManager()->flush();
    }
}