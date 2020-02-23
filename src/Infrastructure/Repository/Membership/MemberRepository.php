<?php
declare(strict_types=1);

namespace App\Infrastructure\Repository\Membership;

use App\Aggregate\Controller\SearchParams;
use App\Aggregate\Repository\PaginationAwareTrait;
use App\Api\Entity\Status;
use App\Domain\Resource\MemberIdentity;
use App\Membership\Entity\MemberInterface;
use App\Membership\Exception\InvalidMemberIdentifier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use App\Api\Repository\PublicationListRepository;
use App\Twitter\Exception\NotFoundMemberException;
use App\Membership\Entity\Member;
use Psr\Log\LoggerInterface;
use stdClass;
use function is_numeric;
use function sprintf;

/**
 * @package App\Membership\Repository
 *
 * @method MemberInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method MemberInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method MemberInterface[]    findAll()
 * @method MemberInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MemberRepository extends ServiceEntityRepository implements MemberRepositoryInterface
{
    private const TABLE_ALIAS = 'm';

    /** @var PublicationListRepository */
    public PublicationListRepository $aggregateRepository;

    /**
     * @var LoggerInterface
     */
    public LoggerInterface $logger;

    use PaginationAwareTrait;

    /**
     * @param string      $twitterId
     * @param string      $screenName
     * @param bool        $protected
     * @param bool        $suspended
     * @param string|null $description
     * @param int         $totalSubscriptions
     * @param int         $totalSubscribees
     *
     * @return Member
     * @throws InvalidMemberIdentifier
     */
    public function make(
        string $twitterId,
        string $screenName,
        bool $protected = false,
        bool $suspended = false,
        string $description = null,
        int $totalSubscriptions = 0,
        int $totalSubscribees = 0
    ) {
        $member = new Member();

        if (is_numeric($twitterId)) {
            if ((int) $twitterId === 0) {
                throw new InvalidMemberIdentifier(
                    'An identifier should be distinct from 0.'
                );
            }

            $member->setTwitterID($twitterId);
        }

        $member->setTwitterUsername($screenName);

        $member->setEnabled(false);
        $member->setLocked(false);
        $member->setEmail('@' . $screenName);

        $member->setProtected($protected);
        $member->setSuspended($suspended);
        $member->setNotFound(false);

        if ($description !== null) {
            $member->description = $description;
        }

        $member->totalSubscribees = $totalSubscribees;
        $member->totalSubscriptions = $totalSubscriptions;

        return $member;
    }

    /**
     * @param $identifier
     *
     * @return MemberInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function suspendMemberByScreenNameOrIdentifier($identifier)
    {
        if (is_numeric($identifier)) {
            return $this->suspendMemberByIdentifier($identifier);
        }

        return $this->suspendMember($identifier);
    }

    /**
     * @param $screenName
     *
     * @return MemberInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function suspendMember(string $screenName)
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);

        if ($member instanceof MemberInterface) {
            $member->setSuspended(true);

            return $this->saveUser($member);
        }

        $member = new Member();
        $member->setTwitterUsername($screenName);
        $member->setTwitterID(0);
        $member->setEnabled(false);
        $member->setLocked(false);
        $member->setEmail('@' . $screenName);
        $member->setEnabled(0);
        $member->setProtected(false);
        $member->setSuspended(true);

        return $this->saveUser($member);
    }

    /**
     * @param $screenName
     * @return MemberInterface|null
     * @throws OptimisticLockException
     */
    public function declareUserAsNotFoundByUsername($screenName): ?MemberInterface
    {
        $user = $this->findOneBy(['twitter_username' => $screenName]);

        if (!$user instanceof MemberInterface) {
            return null;
        }

        return $this->declareUserAsNotFound($user);
    }

    /**
     * @param $screenName
     * @return MemberInterface|null
     * @throws OptimisticLockException
     */
    public function declareMemberAsSuspended(string $screenName): ?MemberInterface
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);

        if (!$member instanceof MemberInterface) {
            return null;
        }

        /** @var MemberInterface $member */
        return $this->suspendMember($member);
    }

    /**
     * @param MemberInterface $user
     * @return MemberInterface
     * @throws OptimisticLockException
     */
    public function declareUserAsNotFound(MemberInterface $user)
    {
        $user->setNotFound(true);

        return $this->saveUser($user);
    }

    /**
     * @param MemberInterface $user
     * @return MemberInterface
     * @throws OptimisticLockException
     */
    public function declareUserAsFound(MemberInterface $user)
    {
        $user->setNotFound(false);

        return $this->saveUser($user);
    }

    /**
     * @param string $screenName
     *
     * @return Member|MemberInterface
     * @throws InvalidMemberIdentifier
     * @throws OptimisticLockException
     */
    public function declareUserAsProtected(string $screenName)
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);
        if (!$member instanceof MemberInterface) {
            return $this->make(
                '0',
                $screenName,
                $protected = true
            );
        }

        $member->setProtected(true);

        return $this->saveMember($member);
    }

    /**
     * @param MemberInterface $member
     *
     * @return MemberInterface
     * @throws OptimisticLockException*
     * @throws ORMException
     */
    public function saveMember(MemberInterface $member)
    {
        $entityManager = $this->getEntityManager();

        $entityManager->persist($member);
        $entityManager->flush();

        return $member;
    }

    /**
     * @deprecated
     *
     * @param MemberInterface $member
     *
     * @return MemberInterface
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function saveUser(MemberInterface $member)
    {
        return $this->saveMember($member);
    }

    /**
     * @param string $maxStatusId
     * @param string $screenName
     *
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareMaxStatusIdForMemberWithScreenName(string $maxStatusId, string $screenName)
    {
        $member = $this->ensureMemberExists($screenName);

        if (is_null($member->maxStatusId) || ((int) $maxStatusId > (int) $member->maxStatusId)) {
            $member->maxStatusId = $maxStatusId;
        }

        return $this->saveMember($member);
    }

    /**
     * @param string $minStatusId
     * @param string $screenName
     *
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareMinStatusIdForMemberWithScreenName(string $minStatusId, string $screenName)
    {
        $member = $this->ensureMemberExists($screenName);

        if (is_null($member->minStatusId) || ((int) $minStatusId < (int) $member->minStatusId)) {
            $member->minStatusId = $minStatusId;
        }

        return $this->saveMember($member);
    }

    /**
     * @param string $maxLikeId
     * @param string $screenName
     *
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareMaxLikeIdForMemberWithScreenName(string $maxLikeId, string $screenName): MemberInterface
    {
        $member = $this->ensureMemberExists($screenName);

        if (is_null($member->maxLikeId) || ((int) $maxLikeId > (int) $member->maxLikeId)) {
            $member->maxLikeId = $maxLikeId;
        }

        return $this->saveMember($member);
    }

    /**
     * @param string $minLikeId
     * @param string $screenName
     *
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareMinLikeIdForMemberWithScreenName(string $minLikeId, string $screenName): MemberInterface
    {
        $member = $this->ensureMemberExists($screenName);

        if (is_null($member->minLikeId) || ((int) $minLikeId < (int) $member->minLikeId)) {
            $member->minLikeId = $minLikeId;
        }

        return $this->saveMember($member);
    }

    /**
     * @param int    $totalStatuses
     * @param string $screenName
     *
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareTotalStatusesOfMemberWithName(int $totalStatuses, string $screenName): MemberInterface
    {
        $member = $this->ensureMemberExists($screenName);

        if ($totalStatuses > $member->totalStatuses) {
            $member->totalStatuses = $totalStatuses;

            $this->saveMember($member);
        }

        return $member;
    }

    /**
     * @param int    $totalLikes
     * @param string $memberName
     *
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareTotalLikesOfMemberWithName(int $totalLikes, string $memberName): MemberInterface
    {
        $member = $this->ensureMemberExists($memberName);

        if ($totalLikes > $member->totalLikes) {
            $member->totalLikes = $totalLikes;

            $this->saveMember($member);
        }

        return $member;
    }

    /**
     * @param int    $statusesToBeAdded
     * @param string $memberName
     *
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function incrementTotalStatusesOfMemberWithName(
        int $statusesToBeAdded,
        string $memberName
    ): MemberInterface {
        $member = $this->ensureMemberExists($memberName);

        $member->totalStatuses = $member->totalStatuses + $statusesToBeAdded;
        $this->saveMember($member);

        return $member;
    }

    /**
     * @param int    $likesToBeAdded
     * @param string $memberName
     *
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function incrementTotalLikesOfMemberWithName(
        int $likesToBeAdded,
        string $memberName
    ): MemberInterface {
        $member = $this->ensureMemberExists($memberName);

        $member->totalLikes = $member->totalLikes + $likesToBeAdded;
        $this->saveMember($member);

        return $member;
    }

    /**
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getMemberHavingApiKey()
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder->andWhere('u.apiKey is not null');

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param string $memberName
     *
     * @return object|null
     * @throws NotFoundMemberException
     */
    private function ensureMemberExists(string $memberName)
    {
        $member = $this->findOneBy(['twitter_username' => $memberName]);
        if (!$member instanceof MemberInterface) {
            NotFoundMemberException::raiseExceptionAboutNotFoundMemberHavingScreenName($memberName);
        }

        return $member;
    }

    /**
     * @param int $identifier
     *
     * @return MemberInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function suspendMemberByIdentifier(int $identifier): MemberInterface
    {
        $suspendedMember = $this->findOneBy(['twitterID' => $identifier]);

        if ($suspendedMember instanceof MemberInterface) {
            $suspendedMember->setSuspended(true);

            return $this->saveUser($suspendedMember);
        }

        $suspendedMember = new Member();
        $suspendedMember->setTwitterUsername((string) $identifier);
        $suspendedMember->setTwitterID((string) $identifier);
        $suspendedMember->setEnabled(false);
        $suspendedMember->setLocked(false);
        $suspendedMember->setEmail('@' . $identifier);
        $suspendedMember->setEnabled(false);
        $suspendedMember->setProtected(false);
        $suspendedMember->setSuspended(true);

        return $this->saveUser($suspendedMember);
    }

    /**
     * @param string $screenName
     *
     * @return MemberInterface
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareMemberHavingScreenNameNotFound(string $screenName): MemberInterface
    {
        $notFoundMember = $this->make(
            '0',
            $screenName
        );
        $notFoundMember->setNotFound(true);

        return $this->saveMember($notFoundMember);
    }

    /**
     * @param array $tokenInfo
     * @return MemberInterface|null
     * @throws DBALException
     */
    public function findByAuthenticationToken(array $tokenInfo): ?MemberInterface
    {
        /** @var Connection $connection */
        $connection = $this->getEntityManager()->getConnection();
        $query = <<<QUERY
            SELECT usr_id member_id
            FROM authentication_token a
            LEFT JOIN weaving_user m
            ON a.member_id = m.usr_id
            WHERE a.token = ?
QUERY;
        $statement = $connection->executeQuery(
            $query,
            [$tokenInfo['sub']],
            [\PDO::PARAM_STR]
        );
        $results = $statement->fetchAll();

        if (count($results) !== 1 ||
            !array_key_exists('member_id', $results[0])) {
            return null;
        }

        $member = $this->findOneBy(['id' => $results[0]['member_id']]);

        if ($member instanceof MemberInterface) {
            return $member;
        }

        return null;
    }

    /**
     * @param SearchParams $searchParams
     * @return int
     * @throws NonUniqueResultException
     */
    public function countTotalPages(SearchParams $searchParams): int
    {
        return $this->howManyPages($searchParams, self::TABLE_ALIAS);
    }

    /**
     * @param SearchParams $searchParams
     * @return array
     * @throws DBALException
     */
    public function findMembers(SearchParams $searchParams): array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE_ALIAS);
        $aggregateProperties = $this->applyCriteria($queryBuilder, $searchParams);

        $queryBuilder->setFirstResult($searchParams->getFirstItemIndex());
        $queryBuilder->setMaxResults($searchParams->getPageSize());

        $results = $queryBuilder->getQuery()->getArrayResult();

        if (count($aggregateProperties) > 0) {
            return array_map(function ($result) use ($aggregateProperties) {
                return array_merge(
                    $result,
                    $aggregateProperties[strtolower($result['name'])]
                );
            }, $results);
        }

        return $results;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     * @return array
     * @throws DBALException
     */
    private function applyCriteria(QueryBuilder $queryBuilder, SearchParams $searchParams): array
    {
        $queryBuilder->select('m.twitter_username as name');
        $queryBuilder->addSelect('m.url');
        $queryBuilder->addSelect('m.description');
        $queryBuilder->addSelect('m.twitterID as twitterId');
        $queryBuilder->addSelect('m.notFound as isNotFound');
        $queryBuilder->addSelect('m.suspended as isSuspended');
        $queryBuilder->addSelect('m.protected as isProtected');
        $queryBuilder->addSelect('m.id as id');

        if ($searchParams->hasKeyword()) {
            $queryBuilder->andWhere('m.twitter_username like :keyword');
            $queryBuilder->setParameter(
                'keyword',
                sprintf(
                    '%%%s%%',
                    strtr(
                        $searchParams->getKeyword(),
                        [
                            '_' => '\_',
                            '%' => '%%',
                        ]
                    )
                )
            );
        }

        $params = $searchParams->getParams();
        if (array_key_exists('aggregateId', $params)) {
            $aggregates = $this->findRelatedAggregates($searchParams);
            $aggregateProperties = [];
            array_walk(
                $aggregates,
                function ($aggregate) use (&$aggregateProperties) {
                    $aggregate['id'] = (int) $aggregate['id'];
                    $aggregate['totalStatuses'] = (int) $aggregate['totalStatuses'];
                    $aggregate['locked'] = (bool)$aggregate['locked'];

                    if (array_key_exists('unlocked_at', $aggregate)) {
                        $aggregate['unlockedAt'] = $aggregate['unlocked_at'];
                    }

                    if (array_key_exists('unlocked_at', $aggregate) &&
                        !is_null($aggregate['unlocked_at'])) {
                        $aggregate['unlockedAt'] = (new \DateTime(
                            $aggregate['unlocked_at'],
                            new \DateTimeZone('UTC'))
                        )->getTimestamp();
                    }

                    $aggregateProperties[strtolower($aggregate['screenName'])] = $aggregate;
                }
            );

            $screenNames = array_map(
                function ($result) {
                    return $result['screenName'];
                },
                $aggregates
            );
            $queryBuilder->andWhere('m.twitter_username in (:screen_names)');
            $queryBuilder->setParameter('screen_names', $screenNames);

            return $aggregateProperties;
        }

        return [];
    }

    /**
     * @param SearchParams $params
     * @return array
     * @throws DBALException
     */
    private function findRelatedAggregates(SearchParams $searchParams): array
    {
        $params = $searchParams->getParams();
        $hasKeyword = $searchParams->hasKeyword();

        $keywordCondition = '';
        if ($hasKeyword) {
            $keywordCondition = 'AND aggregate.screen_name like ?';
        }

        $connection = $this->getEntityManager()->getConnection();
        $query = <<< QUERY
            SELECT 
            aggregate.id,
            aggregate.screen_name AS screenName, 
            aggregate.total_statuses AS totalStatuses,
            aggregate.locked, 
            aggregate.locked_at AS lockedAt,
            aggregate.unlocked_at AS unlockedAt
            FROM weaving_aggregate a
            INNER JOIN weaving_aggregate aggregate
            ON aggregate.screen_name = a.screen_name AND aggregate.screen_name IS NOT NULL
            WHERE a.name in (
                SELECT a.name
                FROM weaving_aggregate a
                WHERE id = ?
            )
            $keywordCondition
            GROUP BY aggregate.id
QUERY;

        $params = [$params['aggregateId']];
        if ($hasKeyword) {
            $keyword = sprintf(
                '%%%s%%',
                strtr(
                    $searchParams->getKeyword(),
                    [
                        '_' => '\_',
                        '%' => '%%',
                    ]
                )
            );
            $params[] = $keyword;
        }

        $paramsTypes = [
            \PDO::PARAM_INT,
        ];
        if ($hasKeyword) {
            $paramsTypes =  [
                \PDO::PARAM_INT,
                \PDO::PARAM_STR
            ];
        }

        $statement = $connection->executeQuery(
            $query,
            $params,
            $paramsTypes
        );

        $results = $statement->fetchAll();

        $results = array_map(
            function (array $aggregate) {
                if ((int) $aggregate['totalStatuses'] <= 0) {
                    $matchingAggregate = $this->aggregateRepository->findOneBy(
                        ['id' => (int) $aggregate['id']]
                    );

                    $this->aggregateRepository->updateTotalStatuses(
                        $aggregate,
                        $matchingAggregate
                    );
                    $aggregate['totalStatuses'] = $matchingAggregate->totalStatuses;
                }

                return $aggregate;
            },
            $results
        );

        try {
            $this->getEntityManager()->flush();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }

        return $results;
    }

    /**
     * @param MemberIdentity $memberIdentity
     * @param stdClass       $twitterUser
     * @param bool           $protected
     * @param bool           $suspended
     *
     * @return MemberInterface
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function saveMemberWithAdditionalProps(
        MemberIdentity $memberIdentity,
        stdClass $twitterUser,
        bool $protected = false,
        bool $suspended = false
    ): MemberInterface {
        $member = $this->make(
            $memberIdentity->id(),
            $twitterUser->screen_name,
            $protected,
            $suspended
        );

        $this->getEntityManager()->persist($member);
        $this->getEntityManager()->flush();

        return $member;
    }

    /**
     * @param MemberIdentity $memberIdentity
     *
     * @return MemberInterface
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveProtectedMember(MemberIdentity $memberIdentity): MemberInterface
    {
        return $this->saveMemberWithAdditionalProps(
            $memberIdentity,
            (object) ['screen_name' => $memberIdentity->screenName()],
            $protected = true
        );
    }

    /**
     * @param MemberIdentity $memberIdentity
     *
     * @return MemberInterface
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveSuspendedMember(MemberIdentity $memberIdentity): MemberInterface
    {
        return $this->saveMemberWithAdditionalProps(
            $memberIdentity,
            (object) ['screen_name' => $memberIdentity->screenName()],
            $protected = false,
            $suspended = true
        );
    }
}