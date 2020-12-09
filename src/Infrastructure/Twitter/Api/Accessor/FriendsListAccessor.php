<?php
declare (strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Infrastructure\Twitter\Api\Resource\FriendsList;
use App\Twitter\Api\ApiAccessorInterface;
use Closure;
use Psr\Log\LoggerInterface;
use Throwable;

class FriendsListAccessor implements ListAccessorInterface
{
    private ApiAccessorInterface $accessor;
    private LoggerInterface $logger;

    public function __construct(
        ApiAccessorInterface $accessor,
        LoggerInterface $logger
    ) {
        $this->accessor = $accessor;
        $this->logger = $logger;
    }

    /**
     * @param string $screenName
     * @param Closure|null $onFinishCollection
     * @return FriendsList
     * @throws Throwable
     */
    public function getListAtDefaultCursor(string $screenName, Closure $onFinishCollection = null): FriendsList {
        return $this->getListAtCursor($screenName, '-1', $onFinishCollection);
    }

    /**
     * @param string $screenName
     * @param string $cursor
     * @param Closure|null $onFinishCollection
     * @return FriendsList
     * @throws Throwable
     */
    public function getListAtCursor(string $screenName, string $cursor, Closure $onFinishCollection = null): FriendsList {
        try {
            $friendsListEndpoint = $this->getFriendsListEndpoint();

            $endpoint = strtr(
                $friendsListEndpoint,
                [
                    '{{ screen_name }}' => $screenName,
                    '{{ cursor }}' => $cursor,
                ]
            );

            $friendsList = (array) $this->accessor->contactEndpoint($endpoint);

            if (is_callable($onFinishCollection)) {
                $onFinishCollection($friendsList);
            }

            return FriendsList::fromResponse($friendsList);
        } catch (Throwable $exception) {
            $this->logger->error(
                $exception->getMessage(),
                ['screen_name' => $screenName]
            );

            throw $exception;
        }
    }

    public function getMemberFriendsList(string $screenName): FriendsList
    {
        $friendsList = $this->getListAtDefaultCursor($screenName);
        $nextFriendsList = $friendsList;

        while ($nextFriendsList->count() === 200 && $nextFriendsList->nextCursor() !== -1) {
            $nextFriendsList = $this->getListAtCursor($screenName, $friendsList->nextCursor());
            $friendsList = FriendsList::fromResponse(array_merge(
                ['users' => array_merge($friendsList->getList(), $nextFriendsList->getList())],
                ['next_cursor_str' => $nextFriendsList->nextCursor()]
            ));
        }

        return $friendsList;
    }

    private function getFriendsListEndpoint(): string {
        return implode([
            $this->accessor->getApiBaseUrl(),
            '/friends/list.json?',
            'count=200',
            '&skip_status=false',
            '&cursor={{ cursor }}',
            '&screen_name={{ screen_name }}'
        ]);
    }
}