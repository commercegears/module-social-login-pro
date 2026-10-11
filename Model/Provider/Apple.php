<?php
/**
 * CommerceGears Social Login Pro
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLoginPro\Model\Provider;

use CommerceGears\SocialLogin\Exception\SocialLoginException;
use CommerceGears\SocialLogin\Model\AuthRequest;
use CommerceGears\SocialLogin\Model\Jwt\JwtException;
use CommerceGears\SocialLogin\Model\Profile;
use CommerceGears\SocialLogin\Model\Provider\AbstractOidcProvider;
use CommerceGears\SocialLogin\Model\Provider\ProviderContext;
use CommerceGears\SocialLoginPro\Model\Jwt\Es256Signer;

/**
 * Sign in with Apple. The client secret is an ES256 JWT signed with the .p8 key; Apple posts the
 * callback (response_mode=form_post) and sends the name only on the very first authorisation.
 */
class Apple extends AbstractOidcProvider
{
    private const ISSUER = 'https://appleid.apple.com';

    public function __construct(
        ProviderContext $context,
        private readonly Es256Signer $es256Signer
    ) {
        parent::__construct($context);
    }

    public function getCode(): string
    {
        return 'apple';
    }

    public function getName(): string
    {
        return 'Apple';
    }

    public function usesFormPost(): bool
    {
        return true;
    }

    protected function hasClientSecret(?int $storeId): bool
    {
        $config = $this->context->config;
        return $config->getProviderValue('apple', 'team_id', $storeId) !== ''
            && $config->getProviderValue('apple', 'key_id', $storeId) !== ''
            && $config->getProviderSecret('apple', 'private_key', $storeId) !== '';
    }

    /**
     * Client secret JWT, valid for five minutes (Apple allows up to six months).
     */
    protected function getClientSecret(?int $storeId): string
    {
        $config = $this->context->config;
        $now = time();
        try {
            return $this->es256Signer->sign(
                [
                    'iss' => $config->getProviderValue('apple', 'team_id', $storeId),
                    'iat' => $now,
                    'exp' => $now + 300,
                    'aud' => self::ISSUER,
                    'sub' => $this->getClientId($storeId),
                ],
                $config->getProviderSecret('apple', 'private_key', $storeId),
                ['kid' => $config->getProviderValue('apple', 'key_id', $storeId)]
            );
        } catch (JwtException $e) {
            throw SocialLoginException::withDetail(
                'Sign in with Apple is not configured correctly.',
                'apple: client secret: ' . $e->getMessage()
            );
        }
    }

    protected function getAuthorizeEndpoint(?int $storeId): string
    {
        return 'https://appleid.apple.com/auth/authorize';
    }

    protected function getTokenEndpoint(?int $storeId): string
    {
        return 'https://appleid.apple.com/auth/token';
    }

    protected function getJwksUri(?int $storeId): string
    {
        return 'https://appleid.apple.com/auth/keys';
    }

    protected function getIssuerExpectation(?int $storeId): array
    {
        return ['issuer' => self::ISSUER];
    }

    protected function getScopes(?int $storeId): array
    {
        return ['name', 'email'];
    }

    protected function getExtraAuthorizationParams(AuthRequest $request): array
    {
        return ['response_mode' => 'form_post'];
    }

    protected function buildProfile(array $claims, array $tokenResponse, AuthRequest $request, array $callbackParams): Profile
    {
        $first = $last = '';
        // "user" is only posted on the first authorisation and is not signed: use it for the name only.
        $user = (string)($callbackParams['user'] ?? '');
        if ($user !== '') {
            try {
                $decoded = json_decode($user, true, 8, JSON_THROW_ON_ERROR);
                $first = trim((string)($decoded['name']['firstName'] ?? ''));
                $last = trim((string)($decoded['name']['lastName'] ?? ''));
            } catch (\JsonException $e) {
                $first = $last = '';
            }
        }
        $email = isset($claims['email']) && $claims['email'] !== '' ? (string)$claims['email'] : null;
        return new Profile(
            $this->getCode(),
            (string)$claims['sub'],
            $email,
            $email !== null && self::isTrue($claims['email_verified'] ?? false),
            mb_substr($first, 0, 255),
            mb_substr($last, 0, 255),
            trim($first . ' ' . $last)
        );
    }
}
