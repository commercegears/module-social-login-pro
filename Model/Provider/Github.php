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
use Softaware\SocialLogin\Model\Provider\AbstractProvider;

/**
 * GitHub OAuth app. The email comes from /user/emails so that its "verified" flag can be checked.
 */
class Github extends AbstractProvider
{
    public function getCode(): string
    {
        return 'github';
    }

    public function getName(): string
    {
        return 'GitHub';
    }

    public function usesPkce(): bool
    {
        return true;
    }

    protected function getAuthorizeEndpoint(?int $storeId): string
    {
        return 'https://github.com/login/oauth/authorize';
    }

    protected function getTokenEndpoint(?int $storeId): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    protected function getScopes(?int $storeId): array
    {
        return ['read:user', 'user:email'];
    }

    protected function getExtraAuthorizationParams(AuthRequest $request): array
    {
        return ['allow_signup' => 'true'];
    }

    public function fetchProfile(AuthRequest $request, array $callbackParams): Profile
    {
        $token = $this->exchangeCode($request, $callbackParams);
        $headers = [
            'Authorization' => 'Bearer ' . $token['access_token'],
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
        $user = $this->context->httpClient->getJson('https://api.github.com/user', $headers);
        if (empty($user['id'])) {
            throw $this->profileError('user response without id');
        }
        $email = null;
        $verified = false;
        $emails = $this->context->httpClient->getJson('https://api.github.com/user/emails', $headers);
        foreach ($emails as $entry) {
            if (!is_array($entry) || empty($entry['email']) || empty($entry['verified'])) {
                continue;
            }
            if ($email === null || !empty($entry['primary'])) {
                $email = (string)$entry['email'];
                $verified = true;
            }
        }
        $name = trim((string)($user['name'] ?? ''));
        [$first, $last] = Profile::splitName($name !== '' ? $name : (string)($user['login'] ?? ''));
        return new Profile(
            $this->getCode(),
            (string)$user['id'],
            $email,
            $verified,
            $first,
            $last,
            $name !== '' ? $name : (string)($user['login'] ?? '')
        );
    }
}
