<?php
/**
 * CommerceGears Social Login Pro
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLoginPro\Model\SelfTest;

use CommerceGears\SocialLogin\Model\Jwt\Base64Url;
use CommerceGears\SocialLogin\Model\Jwt\JwtException;
use CommerceGears\SocialLogin\Model\Jwt\JwtValidator;
use CommerceGears\SocialLogin\Model\SelfTest\CheckInterface;
use CommerceGears\SocialLogin\Model\SelfTest\Reporter;
use CommerceGears\SocialLoginPro\Model\Jwt\Es256Signer;

/**
 * Sign in with Apple client secret: an ES256 JWT signed with a freshly generated P-256 key (no Apple calls).
 */
class AppleClientSecretCheck implements CheckInterface
{
    public function __construct(
        private readonly Es256Signer $es256Signer,
        private readonly JwtValidator $jwtValidator
    ) {
    }

    public function run(Reporter $reporter): void
    {
        $reporter->section('Sign in with Apple client secret (ES256)');
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        openssl_pkey_export($key, $pem);
        $details = openssl_pkey_get_details($key);
        $jwks = ['keys' => [[
            'kty' => 'EC', 'crv' => 'P-256', 'kid' => 'ABC123DEFG',
            'x' => Base64Url::encode($details['ec']['x']), 'y' => Base64Url::encode($details['ec']['y']),
        ]]];
        // A .p8 key pasted into a password field loses its line breaks: rebuild and sign.
        $flattened = str_replace("\n", ' ', $pem);
        $now = time();
        try {
            $secret = $this->es256Signer->sign(
                ['iss' => 'TEAMID1234', 'iat' => $now, 'exp' => $now + 300, 'aud' => 'https://appleid.apple.com', 'sub' => 'com.example.signin'],
                $flattened,
                ['kid' => 'ABC123DEFG']
            );
            [$header] = $this->jwtValidator->decodeUnverified($secret);
            $reporter->check('client secret header has alg ES256 and kid', $header['alg'] === 'ES256' && $header['kid'] === 'ABC123DEFG');
            $reporter->check('raw R||S signature is 64 bytes', strlen(Base64Url::decode(explode('.', $secret)[2])) === 64);
            $claims = $this->jwtValidator->validate($secret, $jwks, [
                'issuer' => 'TEAMID1234', 'audience' => 'https://appleid.apple.com', 'algorithms' => ['ES256'],
            ]);
            $reporter->check('client secret verifies with the public key (key pasted without line breaks)', $claims['sub'] === 'com.example.signin');
        } catch (\Throwable $e) {
            $reporter->check('client secret: ' . $e->getMessage(), false);
        }
        try {
            $this->es256Signer->sign(['a' => 1], 'not a key');
            $reporter->check('invalid .p8 key rejected', false);
        } catch (JwtException $e) {
            $reporter->check('invalid .p8 key rejected', true);
        }
        $rsa = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        openssl_pkey_export($rsa, $rsaPem);
        try {
            $this->es256Signer->sign(['a' => 1], $rsaPem);
            $reporter->check('key that is not EC P-256 rejected', false);
        } catch (JwtException $e) {
            $reporter->check('key that is not EC P-256 rejected', true);
        }
    }
}
