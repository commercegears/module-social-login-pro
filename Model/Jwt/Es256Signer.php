<?php
/**
 * CommerceGears Social Login Pro
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLoginPro\Model\Jwt;

use CommerceGears\SocialLogin\Model\Jwt\Base64Url;
use CommerceGears\SocialLogin\Model\Jwt\Der;
use CommerceGears\SocialLogin\Model\Jwt\JwtException;

/**
 * Creates ES256-signed JWTs, e.g. the client secret for Sign in with Apple (from the .p8 key).
 */
class Es256Signer
{
    public function sign(array $claims, string $privateKeyPem, array $extraHeader = []): string
    {
        $key = openssl_pkey_get_private($this->normalisePem($privateKeyPem));
        if ($key === false) {
            throw new JwtException('The private key cannot be read. Paste the full contents of the .p8 file.');
        }
        $details = openssl_pkey_get_details($key);
        if (($details['type'] ?? null) !== OPENSSL_KEYTYPE_EC || ($details['ec']['curve_name'] ?? '') !== 'prime256v1') {
            throw new JwtException('The private key is not an EC P-256 key.');
        }
        $header = ['alg' => 'ES256', 'typ' => 'JWT'] + $extraHeader;
        $input = Base64Url::encode(json_encode($header, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES))
            . '.' . Base64Url::encode(json_encode($claims, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $derSignature = '';
        if (!openssl_sign($input, $derSignature, $key, OPENSSL_ALGO_SHA256)) {
            throw new JwtException('Signing failed.');
        }
        return $input . '.' . Base64Url::encode(Der::derToRawSignature($derSignature));
    }

    /**
     * Accepts a key pasted with or without line breaks (password fields drop them) and rebuilds the PEM.
     */
    public function normalisePem(string $key): string
    {
        $key = trim($key);
        if (preg_match('/-----BEGIN ([A-Z ]+)-----(.+?)-----END \1-----/s', $key, $matches) === 1) {
            $label = $matches[1];
            $body = preg_replace('/\s+/', '', $matches[2]);
        } else {
            $label = 'PRIVATE KEY';
            $body = preg_replace('/\s+/', '', $key);
        }
        return '-----BEGIN ' . $label . "-----\n" . chunk_split((string)$body, 64, "\n") . '-----END ' . $label . "-----\n";
    }
}
