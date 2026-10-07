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
 * Microsoft identity platform (Entra ID v2.0 endpoint), "common" tenant by default, with PKCE.
 *
 * The "email" claim is only treated as verified for personal Microsoft accounts or when Entra
 * marks the domain as owner-verified (xms_edov), as Microsoft advises.
 */
class Microsoft extends AbstractOidcProvider
{
    private const CONSUMER_TENANT_ID = '9188040d-6c67-4c5b-b112-36a304b66dad';

    public function getCode(): string
    {
        return 'microsoft';
    }

    public function getName(): string
    {
        return 'Microsoft';
    }

    public function usesPkce(): bool
    {
        return true;
    }

    private function getTenant(?int $storeId): string
    {
        $tenant = $this->context->config->getProviderValue('microsoft', 'tenant', $storeId);
        return preg_match('/^[A-Za-z0-9.\-]{1,100}$/', $tenant) === 1 ? $tenant : 'common';
    }

    protected function getAuthorizeEndpoint(?int $storeId): string
    {
        return 'https://login.microsoftonline.com/' . rawurlencode($this->getTenant($storeId)) . '/oauth2/v2.0/authorize';
    }

    protected function getTokenEndpoint(?int $storeId): string
    {
        return 'https://login.microsoftonline.com/' . rawurlencode($this->getTenant($storeId)) . '/oauth2/v2.0/token';
    }

    protected function getJwksUri(?int $storeId): string
    {
        return 'https://login.microsoftonline.com/' . rawurlencode($this->getTenant($storeId)) . '/discovery/v2.0/keys';
    }

    protected function getIssuerExpectation(?int $storeId): array
    {
        return ['issuer_pattern' => '#^https://login\.microsoftonline\.com/[0-9a-f\-]{36}/v2\.0$#'];
    }

    protected function getScopes(?int $storeId): array
    {
        return ['openid', 'email', 'profile'];
    }

    protected function getExtraAuthorizationParams(AuthRequest $request): array
    {
        return ['prompt' => 'select_account', 'response_mode' => 'query'];
    }

    protected function buildProfile(array $claims, array $tokenResponse, AuthRequest $request, array $callbackParams): Profile
    {
        $tenantId = (string)($claims['tid'] ?? '');
        if ($claims['iss'] !== 'https://login.microsoftonline.com/' . $tenantId . '/v2.0') {
            throw $this->profileError('issuer does not match tenant');
        }
        $configured = strtolower($this->getTenant($request->storeId));
        if (preg_match('/^[0-9a-f\-]{36}$/', $configured) === 1 && $configured !== strtolower($tenantId)) {
            throw $this->profileError('token from a different tenant');
        }
        if ($configured === 'consumers' && $tenantId !== self::CONSUMER_TENANT_ID) {
            throw $this->profileError('work or school account used with the consumers tenant');
        }
        if ($configured === 'organizations' && $tenantId === self::CONSUMER_TENANT_ID) {
            throw $this->profileError('personal account used with the organizations tenant');
        }
        [$first, $last] = $this->namesFromClaims($claims);
        $email = isset($claims['email']) && $claims['email'] !== '' ? (string)$claims['email'] : null;
        $verified = $email !== null
            && ($tenantId === self::CONSUMER_TENANT_ID || self::isTrue($claims['xms_edov'] ?? false));
        return new Profile(
            $this->getCode(),
            (string)$claims['sub'],
            $email,
            $verified,
            $first,
            $last,
            (string)($claims['name'] ?? '')
        );
    }
}
