<?php
declare(strict_types=1);

namespace App\Member\Repository;

use App\Member\Authentication\Authenticator;
use App\Member\Entity\AuthenticationToken;
use Doctrine\ORM\EntityRepository;
use App\Membership\Entity\Member;
use App\Infrastructure\Repository\Membership\MemberRepository;
use const JSON_THROW_ON_ERROR;

class AuthenticationTokenRepository extends EntityRepository
{
    /**
     * @var MemberRepository
     */
    public MemberRepository $memberRepository;

    /**
     * @var Authenticator
     */
    public Authenticator $authenticator;

    /**
     * @param string $tokenId
     * @return array
     */
    public function findByTokenIdentifier(string $tokenId): array
    {
        try {
            $tokenInfo = $this->authenticator->authenticate($tokenId);
        } catch (\Exception $exception) {
            return [];
        }

        /** @var AuthenticationToken $token */
        $token = $this->findOneBy(['token' => $tokenInfo['sub']]);

        if (!($token instanceof AuthenticationToken)) {
            $defaultMember = new Member();
            $defaultMember->setScreenName('revue_2_presse');

            return [
                'member' => $defaultMember,
                'granted_routes' => json_encode(['bucket'], JSON_THROW_ON_ERROR),
            ];
        }

        return [
            'member' => $token->getMember(),
            'granted_routes' => $token->getGrantedRoutes()
        ];
    }
}
