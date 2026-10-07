<?php
/**
 * Softaware Social Login Pro
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLoginPro\Model\Provider;

use Softaware\SocialLogin\Model\AuthRequest;
use Softaware\SocialLogin\Model\Profile;
use Softaware\SocialLogin\Model\Provider\AbstractOidcProvider;

/**
 * "Sign In with LinkedIn using OpenID Connect" product.
 */
class LinkedIn extends AbstractOidcProvider
{
    public function getCode(): string
    {
        return 'linkedin';
    }

    public function getName(): string
    {
        return 'LinkedIn';
    }

    /**
     * LinkedIn does not document echoing the nonce, so it is checked only when present.
     */
    protected function isNonceRequired(): bool
    {
        return false;
    }

    protected function getAuthorizeEndpoint(?int $storeId): string
    {
        return 'https://www.linkedin.com/oauth/v2/authorization';
    }

    protected function getTokenEndpoint(?int $storeId): string
    {
        return 'https://www.linkedin.com/oauth/v2/accessToken';
    }

    protected function getJwksUri(?int $storeId): string
    {
        return 'https://www.linkedin.com/oauth/openid/jwks';
    }

    protected function getIssuerExpectation(?int $storeId): array
    {
        return ['issuer' => 'https://www.linkedin.com/oauth'];
    }

    protected function getScopes(?int $storeId): array
    {
        return ['openid', 'profile', 'email'];
    }

    protected function buildProfile(array $claims, array $tokenResponse, AuthRequest $request, array $callbackParams): Profile
    {
        [$first, $last] = $this->namesFromClaims($claims);
        $email = isset($claims['email']) && $claims['email'] !== '' ? (string)$claims['email'] : null;
        return new Profile(
            $this->getCode(),
            (string)$claims['sub'],
            $email,
            $email !== null && self::isTrue($claims['email_verified'] ?? false),
            $first,
            $last,
            (string)($claims['name'] ?? '')
        );
    }
}
