# Changelog

## 1.0.2 (2026-10-09)

### Changed
- Docs: no dashes as punctuation in headings and version ranges.

## 1.0.1 (2026-10-09)

### Changed

- Microsoft setup hint in the admin and docs uses the current Microsoft Entra admin centre names (Entra ID > App
  registrations, account type "Any Entra ID Tenant + Personal Microsoft accounts", Authentication, Certificates &
  secrets).
- Docs: product page and Composer key links point to softawarecommerce.com (the shop moved from
  shop.softawarecommerce.com); the product page tagline says Social Login is free and has no dashes.
- Doc screenshots retaken without the Magento logo of the Hyvä default theme.
- With Social Login 1.4.0, the "open this page in your browser" note for Instagram, Facebook and other in-app
  browsers also covers the Pro providers (no change needed in this module).

## 1.0.0 (2026-10-07)

First release. Add-on for Softaware Social Login 1.3+.

- **Sign in with Apple**: client secret created per request as an ES256 JWT from the .p8 key (valid for five
  minutes; keys pasted without line breaks are rebuilt), `response_mode=form_post` callback with the shopper's
  session restored, ID token validated against Apple's JWKS (signature, issuer, audience, expiry, nonce); the name
  from the first authorisation is used for the new account.
- **Microsoft (Entra ID, v2.0)**: `common` tenant by default (or `consumers`, `organizations`, a tenant ID), PKCE
  S256, ID token validated against the tenant's JWKS; the issuer must match the token's tenant and the configured
  tenant is enforced. Email addresses count as verified only for personal accounts or with `xms_edov`.
- **LinkedIn** (OpenID Connect): ID token validated against LinkedIn's JWKS; `email_verified` respected.
- **Amazon** (Login with Amazon): PKCE S256; addresses are treated as unverified (confirmation email for new
  accounts, password once for existing ones).
- **GitHub** (OAuth App): PKCE S256; only verified addresses from `/user/emails` are used.
- Settings in Social Login's configuration section (`softaware_sociallogin/<code>/...`): client ID, encrypted
  secrets, button label and sort order per provider, with the exact callback URL and setup hints shown in the admin.
- Button icons for Luma and Hyvä, Content Security Policy (`form-action`) entries for the providers' sign-in hosts.
- Self-test checks: authorisation requests of the five providers, Apple client secret.
- en_US and de_DE translations.
