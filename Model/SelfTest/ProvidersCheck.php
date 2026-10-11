<?php
/**
 * CommerceGears Social Login Pro
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLoginPro\Model\SelfTest;

use Magento\Store\Model\StoreManagerInterface;
use CommerceGears\SocialLogin\Model\AuthRequest;
use CommerceGears\SocialLogin\Model\Provider\AbstractProvider;
use CommerceGears\SocialLogin\Model\Provider\ProviderPool;
use CommerceGears\SocialLogin\Model\SelfTest\CheckInterface;
use CommerceGears\SocialLogin\Model\SelfTest\Reporter;
use CommerceGears\SocialLogin\Model\StateStore;

/**
 * The Pro providers are registered and build correct authorisation requests (endpoint, state, PKCE, nonce,
 * form_post). Builds URLs only; nothing is sent to the providers.
 */
class ProvidersCheck implements CheckInterface
{
    /**
     * Code => [authorisation host, PKCE, nonce, form_post]
     */
    private const EXPECTED = [
        'apple' => ['appleid.apple.com', false, true, true],
        'microsoft' => ['login.microsoftonline.com', true, true, false],
        'github' => ['github.com', true, false, false],
        'linkedin' => ['www.linkedin.com', false, true, false],
        'amazon' => ['www.amazon.com', true, false, false],
    ];

    public function __construct(
        private readonly ProviderPool $providerPool,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function run(Reporter $reporter): void
    {
        $reporter->section('Social Login Pro providers (authorisation requests, no provider calls)');
        $storeId = (int)$this->storeManager->getDefaultStoreView()->getId();
        foreach (self::EXPECTED as $code => [$host, $pkce, $nonce, $formPost]) {
            $provider = $this->providerPool->get($code);
            if ($provider === null) {
                $reporter->check($code . ': registered', false);
                continue;
            }
            $state = StateStore::randomToken();
            $verifier = StateStore::randomToken(48);
            $request = new AuthRequest($code, $state, 'nonce-' . $code, $verifier, 'https://shop.example/cb', $storeId);
            $url = $provider->getAuthorizationUrl($request);
            parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
            $ok = parse_url($url, PHP_URL_SCHEME) === 'https'
                && parse_url($url, PHP_URL_HOST) === $host
                && ($query['state'] ?? '') === $state
                && ($query['redirect_uri'] ?? '') === 'https://shop.example/cb'
                && ($query['response_type'] ?? '') === 'code'
                && $provider->usesPkce() === $pkce
                && $pkce === (($query['code_challenge'] ?? '') === AbstractProvider::codeChallenge($verifier)
                    && ($query['code_challenge_method'] ?? '') === 'S256')
                && $nonce === (($query['nonce'] ?? '') === 'nonce-' . $code)
                && $provider->usesFormPost() === $formPost
                && $formPost === (($query['response_mode'] ?? '') === 'form_post');
            $reporter->check(sprintf(
                '%s: https://%s, state%s%s%s',
                $provider->getName(),
                $host,
                $pkce ? ', PKCE S256' : '',
                $nonce ? ', nonce' : '',
                $formPost ? ', form_post' : ''
            ), $ok);
        }
    }
}
