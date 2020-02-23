<?php
declare(strict_types=1);

namespace App\Tests\Builder;

use App\Twitter\Api\ApiAccessorInterface;
use App\Domain\Resource\OwnershipCollection;
use App\Twitter\Exception\UnavailableResourceException;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use stdClass;

class ApiAccessorBuilder
{
    public const LIST_ID   = 1;
    public const LIST_NAME = 'science';

    public const MEMBER_ID          = '1';
    public const MEMBER_NAME        = 'Marie Curie';
    public const MEMBER_SCREEN_NAME = 'mariec';

    public const SCREEN_NAME = 'BobEponge';

    public static function newApiAccessorBuilder()
    {
        return new self();
    }

    private ObjectProphecy $prophecy;

    public function __construct()
    {
        $prophet = new Prophet();

        $this->prophecy = $prophet->prophesize(ApiAccessorInterface::class);
    }

    public function build(): ApiAccessorInterface
    {
        return $this->prophecy->reveal();
    }

    /**
     * @return object
     */
    public function makeMemberList(): stdClass
    {
        return (object) [
            'users' => [
                (object) [
                    'name'        => self::MEMBER_NAME,
                    'id'          => self::MEMBER_ID,
                    'screen_name' => self::MEMBER_SCREEN_NAME
                ]
            ]
        ];
    }

    /**
     * @return OwnershipCollection
     */
    public function makeOwnershipCollection(): OwnershipCollection
    {
        return OwnershipCollection::fromArray(
            [
                self::LIST_NAME => (object) [
                    'name'   => self::LIST_NAME,
                    'id'     => self::LIST_ID,
                    'id_str' => (string) self::LIST_ID,
                ]
            ]
        );
    }

    public function willGetMembersInList(
        int $listId,
        \stdClass $members
    ): self {
        $this->prophecy
            ->getListMembers($listId)
            ->willReturn($members);

        return $this;
    }

    public function willGetOwnershipCollectionForMember(
        OwnershipCollection $ownershipCollection,
        string $screenName
    ): self {
        $this->prophecy
            ->getMemberOwnerships($screenName)
            ->willReturn($ownershipCollection);

        return $this;
    }

    public function willThrowWhenGettingOwnershipCollectionForMember(
        string $screenName
    ): self {
        $this->prophecy
            ->getMemberOwnerships($screenName)
            ->willThrow(new UnavailableResourceException());

        return $this;
    }

    public function willGetOwnershipCollectionAfterThrowingForMember(
        OwnershipCollection $ownershipCollection,
        string $screenName
    ): self {
        static $calls = 0;

        $this->prophecy
            ->getMemberOwnerships($screenName)
            ->will(function () use (&$calls, $ownershipCollection) {
                if ($calls === 0) {
                    $calls++;

                    throw new UnavailableResourceException();
                }

                return $ownershipCollection;
            });

        return $this;
    }

    public function willGetProfileForMemberHavingScreenName(
        stdClass $profile,
        string $screenName
    ): self {
        $this->prophecy
            ->getMemberProfile($screenName)
            ->willReturn($profile);

        return $this;
    }
}