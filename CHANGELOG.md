# ParadoxLabs_AuthnetcimHyvaCheckout Changelog

## 3.1.1 - Unreleased

- Fixed the Accept.js "Save for next time" checkbox saving the card even when unchecked.
- Fixed the eCheck method showing the "only supports Accept Hosted" notice, and listing credit cards, when the
  credit card method uses Accept.js: payment forms shared one cached form block across methods.
- Fixed the eCheck payment form reading the credit card method's config.
- Fixed the misconfigured-form notice rendering an inert payment form behind it (Alpine CSP console warnings, and
  place order hanging on validation).
- Fixed the Accept Hosted form re-registering a Magewire hook on every re-init, and detached forms (after switching
  payment methods) still reacting to terms updates and calling their removed Magewire component.

## 3.1.0 - Jul 17, 2026: Customer payment options on Hyva

**Now requires ParadoxLabs_TokenBaseHyvaCheckout (`paradoxlabs/tokenbase-hyva-checkout`).**

- Added customer account payment options (My Payment Options) support on Hyva themes, for both the
  credit card (Accept Hosted) and eCheck methods.
- Restored the My Payment Options account navigation link on Hyva (previously removed as unsupported).

## 3.0.0 - Jun 17, 2026: PHP 8.1–8.5 compatibility

**WARNING: PHP 8.1 is now the minimum. Now requires ParadoxLabs_Authnetcim 6.0.**

- Added support up to PHP 8.5; PHP 8.1+ is now required.
- Refactored for PHP 8.1+: constructor property promotion, readonly properties, strict types, and import cleanup (Magewire payment component, view models, blocks).

## 2.0.1 - May 28, 2025

- Fixed CC autocomplete selecting stored cards.
- Fixed PHPCS warnings (all false positives).

## 2.0.0 - May 15, 2025: Hyva Checkout/Alpine CSP support

**WARNING: Templates changed substantially. Any theme overrides must be updated.**

- Added support for Alpine CSP and Hyva Checkout CSP.
- Now requires Hyva Checkout `hyva-themes/magento2-hyva-checkout` >= 1.3, and `hyva-themes/magento2-theme-module` >=
  1.3.11.
- Updated payment form styling to fit latest Hyva Checkout UI.

## 1.3.4 - Apr 23, 2025

- Improved Accept.js payment form styling.

## 1.3.3 - Feb 20, 2025

- Fixed incorrect parsing of two-digit expiration months.
- Fixed payment validation messages never going away.

## 1.3.2 - Feb 19, 2025

- Fixed layout shift and no-stored-card conditions.

## 1.3.1 - Feb 19, 2025

- Fixed `hyva-themes/magento2-payment-icons` version constraint >= 2.0.

## 1.3.0 - Feb 17, 2025: Accept.js support

- Added Accept.js payment form support.
- Improved performance of the Accept Hosted payment form, by reducing Magewire data saves.

## 1.2.0 - Jan 13, 2025: T&C support

- Added support for terms and conditions.
- Fixed Magewire 'multiple roots' error.

## 1.1.0 - Jun 28, 2024: Magento CSP support

- Added CSP/SRI secure mode support for 2.4.0+ (2.4.7 checkout compatibility).
- Fixed "Loading payment form" spinner not resolving on checkout payment form reload.

## 1.0.0 - December 7, 2023

- Initial release: ParadoxLabs Authorize.net CIM support for Hosted Forms on Hyva Checkout
