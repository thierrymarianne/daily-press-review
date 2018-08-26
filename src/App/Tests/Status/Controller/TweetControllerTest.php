<?php

namespace App\Tests\Controller;

use Prophecy\Argument;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use WeavingTheWeb\Bundle\UserBundle\Entity\Role;
use WTW\CodeGeneration\QualityAssuranceBundle\Test\WebTestCase;

/**
 * @group tweet
 * @group controller_latest_tweets
 */
class TweetControllerTest extends WebTestCase
{
    /**
     * @var \Prophecy\Prophet $prophet
     */
    protected $prophet;

    public function setUp()
    {
        $this->client = $this->getAuthenticatedClient(['follow_redirects' => true]);
    }

    /**
     * @test
     * @group it_should_respond_with_the_latest_statuses
     */
    public function it_should_respond_with_the_latest_statuses()
    {
        $this->assertLatestStatusesAreAvailable();

        $this->assertValidOptionsResponse();

        $this->assertConnectionErrorToDatabaseIsReported();
    }

    /**
     * @param $response
     * @return mixed
     */
    protected function decodeJsonResponseContent(Response $response)
    {
        $decodedJson = json_decode($response->getContent(), true);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), 'The JSON response is not valid');

        return $decodedJson;
    }

    /**
     * @param string $message
     */
    protected function assertValidStatusContent($message)
    {
        $expectedHttpStatusCode = 200;
        $response = $this->assertResponseStatusCodeEquals($expectedHttpStatusCode, $message);

        $decodedJson = $this->decodeJsonResponseContent($response);
        $this->assertArrayHasKey(0, $decodedJson, 'The decoded json array should contain one status');
        $this->assertEquals('194987972', $decodedJson[0]['status_id']);
    }

    /**
     *
     */
    protected function assertValidOptionsResponse()
    {
        $this->setUp();

        $this->mockAuthentication();

        $router = $this->get('router');
        $latestTweetUrl = $router->generate('weaving_the_web_twitter_tweet_latest');
        $this->client->request('OPTIONS', $latestTweetUrl);

        $response = $this->assertResponseStatusCodeEquals(200);
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));

        $this->tearDown();
    }

    /**
     * @return object
     */
    protected function makeUserStreamRepositoryMock()
    {
        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository $mock
         */
        $prophecy = $this->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository');
        $prophecy->findLatest(
            Argument::any(),
            Argument::cetera()
        )->willThrow(new \PDOException('No socket available.'));
        $prophecy->setOauthTokens(Argument::any())->willReturn(null);

        return $prophecy->reveal();
    }

    public function testBookmarksAction()
    {
        /** @var \Symfony\Component\Routing\Router $router */
        $router = $this->get('router');
        $latestTweetUrl = $router->generate('weaving_the_web_twitter_tweet_sync_bookmarks');

        $this->client->request('POST', $latestTweetUrl, ['statusIds' => [194987972], 'username' => 'user']);

        $this->assertValidStatusContent('When syncing bookmarks, the response should contain statuses');

        $this->assertValidOptionsResponse($latestTweetUrl);
    }

    /**
     * @dataProvider getStarringExpectations
     */
    public function testToggleStarringStatusAction($routeName, $expectedStarringStatus)
    {
        /** @var \Symfony\Component\Routing\Router $router */
        $router = $this->get('router');

        $statusId = 194987972;
        $actionUrl = $router->generate($routeName, ['statusId' => $statusId]);
        $this->client->request('OPTIONS', $actionUrl);
        $response = $this->assertResponseStatusCodeEquals(200);
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));

        $this->client->request('POST', $actionUrl);
        $response = $this->assertResponseStatusCodeEquals(200);

        $decodedContent = $this->decodeJsonResponseContent($response);
        $this->assertArrayHasKey('status', $decodedContent);
        $this->assertEquals(
            $statusId,
            $decodedContent['status'],
            'It should return a json response containing the status id of a tweet which has been updated'
        );

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository $userStatusRepository */
        $userStatusRepository = $this->get('weaving_the_web_twitter.repository.read.status');
        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Status $userStatus */
        $userStatus = $userStatusRepository->findOneBy(['starred' => $expectedStarringStatus]);

        $this->assertNotNull($userStatus);
        $this->assertEquals($expectedStarringStatus, $userStatus->isStarred());
    }

    public function getStarringExpectations()
    {
        return [
            [
                'route_name' => 'weaving_the_web_twitter_tweet_star',
                'expected_starring_status' => true,
            ], [
                'route_name' => 'weaving_the_web_twitter_tweet_unstar',
                'expected_starring_status' => false,
            ]
        ];
    }

    private function mockAuthentication(): void
    {
        // User inserting by fixtures bundle from WeavingTheWeb\Bundle\UserBundle\DataFixtures\ORM\UserData
        $member = $this->get('user_manager')->findOneBy(['email' => 'user@weaving-the-web.org']);

        $token = $this->prophesize(TokenInterface::class);
        $token->getRoles()->willReturn([(new Role())->setRole('ROLE_USER')]);
        $token->__toString()->willReturn('my tok');
        $token->isAuthenticated()->willReturn(true);
        $token->getUser()->willReturn($member);
        $token->serialize()->willReturn('serialized_token');

        $authenticationProviderManagerProphecy = $this->prophesize(AuthenticationProviderManager::class);
        $authenticationProviderManagerProphecy->authenticate(Argument::any())->willReturn($token->reveal());

        $this->get('service_container')->set(
            'test.security.authentication.manager',
            $authenticationProviderManagerProphecy->reveal()
        );
    }

    /**
     * @return array
     */
    private function assertLatestStatusesAreAvailable()
    {
        $this->mockAuthentication();

        /** @var \Symfony\Component\Routing\Router $router */
        $router = $this->get('router');
        $latestTweetUrl = $router->generate('weaving_the_web_twitter_tweet_latest');

        $requestParameters = ['username' => 'user'];
        $this->client->request('GET', $latestTweetUrl, $requestParameters);

        $this->assertValidStatusContent($message = 'It should respond with the latest tweets ');

        $this->assertTrue(
            $this->client->getResponse()->isCacheable(),
            'It should be possible to cache the latest statuses.'
        );

        $this->tearDown();
    }

    private function assertConnectionErrorToDatabaseIsReported(): void
    {
        $this->setUp();

        $this->mockAuthentication();

        $userRepositoryMock = $this->makeUserStreamRepositoryMock();
        self::$kernel->getContainer()->set('weaving_the_web_twitter.repository.read.status', $userRepositoryMock);

        $router = $this->get('router');
        $latestTweetUrl = $router->generate('weaving_the_web_twitter_tweet_latest');

        $requestParameters = ['username' => 'user'];
        $this->client->request('GET', $latestTweetUrl, $requestParameters);

        $serverErrorMessage = 'It should respond with a server error status';
        $response = $this->assertResponseStatusCodeEquals(500, $serverErrorMessage);

        $decodeJson = $this->decodeJsonResponseContent($response);
        $this->assertArrayHasKey('error', $decodeJson);

        /** @var \Symfony\Component\Translation\Translator $translator */
        $translator = $this->get('translator');
        $databaseConnectionError = $translator->trans('twitter.error.database_connection', [], 'messages');
        $this->assertEquals(
            $decodeJson['error'],
            $databaseConnectionError,
            'It should inform the client about a database connection error.'
        );
    }
}
