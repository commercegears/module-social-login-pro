<?php
/**
 * CommerceGears Social Login Pro
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLoginPro\Model\Provider;

use CommerceGears\SocialLogin\Model\AuthRequest;
use CommerceGears\SocialLogin\Model\Profile;
use CommerceGears\SocialLogin\Model\Provider\AbstractProvider;

/**
 * Login with Amazon (authorisation code grant with PKCE).
 * Amazon does not state whether the address was verified, so it is treated as unverified:
 * an existing store account with the same email is only linked after the password is entered.
 */
class Amazon extends AbstractProvider
{
    public function getCode(): string
    {
        return 'amazon';
    }

    public function getName(): string
    {
        return 'Amazon';
    }

    public function usesPkce(): bool
    {
        return true;
    }

    protected function getAuthorizeEndpoint(?int $storeId): string
    {
        return 'https://www.amazon.com/ap/oa';
    }

    protected function getTokenEndpoint(?int $storeId): string
    {
        return 'https://api.amazon.com/auth/o2/token';
    }

    protected function getScopes(?int $storeId): array
    {
        return ['profile'];
    }

    public function fetchProfile(AuthRequest $request, array $callbackParams): Profile
    {
        $token = $this->exchangeCode($request, $callbackParams);
        $profile = $this->context->httpClient->getJson(
            'https://api.amazon.com/user/profile',
            ['Authorization' => 'Bearer ' . $token['access_token']]
        );
        if (empty($profile['user_id'])) {
            throw $this->profileError('profile response without user_id');
        }
        [$first, $last] = Profile::splitName((string)($profile['name'] ?? ''));
        $email = isset($profile['email']) && $profile['email'] !== '' ? (string)$profile['email'] : null;
        return new Profile(
            $this->getCode(),
            (string)$profile['user_id'],
            $email,
            false,
            $first,
            $last,
            (string)($profile['name'] ?? '')
        );
    }
}
