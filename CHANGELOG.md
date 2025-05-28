# ParadoxLabs_AuthnetcimHyvaCheckout Changelog

## 2.0.1 - May 28, 2025
- Fixed CC autocomplete selecting stored cards.
- Fixed PHPCS warnings (all false positives).

## 2.0.0 - May 15, 2025: Hyva Checkout/Alpine CSP support
**WARNING: Templates changed substantially. Any theme overrides must be updated.**
- Added support for Alpine CSP and Hyva Checkout CSP.
- Now requires Hyva Checkout `hyva-themes/magento2-hyva-checkout` >= 1.3, and `hyva-themes/magento2-theme-module` >= 1.3.11.
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
