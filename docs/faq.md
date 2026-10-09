# Social Login Pro for Magento 2: FAQ

**What does Social Login Pro add?**
Sign in and registration with Apple, Microsoft (personal, work and school accounts), LinkedIn, Amazon and GitHub,
in addition to Google, Facebook and X from Softaware Social Login.

**Do I need Softaware Social Login as well?**
Yes. Social Login Pro is an add-on: it requires `softaware/module-social-login` 1.3 or later, which Composer installs
automatically. All settings outside the provider groups (where the buttons appear, popup or redirect, accounts,
Connected Accounts, admin tools) come from Social Login and apply to the Pro providers too.

**Does it work with Hyvä?**
Yes. The buttons are rendered by Social Login's Luma and Hyvä templates (Hyvä 1.3+, tested with 1.5).

**Does it install third-party SDKs?**
No. Like Social Login, it uses Magento's HTTP client and PHP's OpenSSL extension only. The Apple client secret is
signed locally with your .p8 key.

**Which of these providers link to an existing account automatically?**
Apple, LinkedIn and GitHub when they report the address as verified, and Microsoft for personal accounts or work and
school accounts whose domain is verified (`xms_edov`), provided Social Login's **Existing Account with the Same
Email** setting is "Link automatically". Amazon gives no verification flag, so the customer enters the account
password once.

**Why does an Apple customer have to enter an email address or name?**
Apple sends the name only on the very first authorisation and lets the customer hide the email address. If either
is missing, the customer completes a short form once.

**Can I restrict Microsoft sign-in to my organisation?**
Yes. Set **Tenant** to your tenant ID (or `organizations` for any work or school account, `consumers` for personal
accounts only). Tokens from other tenants are refused.

**Can I change the button order?**
Yes, with each provider's **Sort Order**. By default the order is Google, Apple, Facebook, Microsoft, GitHub,
LinkedIn, Amazon, X.

**What happens if I uninstall Social Login Pro?**
Customers keep their accounts and existing links stay stored. Social Login shows those links with their provider
code; admins and customers can remove them. The settings stay in the database, so reinstalling restores them.

**Which Magento and PHP versions are supported?**
Magento Open Source and Adobe Commerce 2.4.7 to 2.4.9, PHP 8.2 to 8.5.
