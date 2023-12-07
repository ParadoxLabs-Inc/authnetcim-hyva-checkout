[![Latest Stable Version](https://poser.pugx.org/paradoxlabs/authnetcim-hyva-checkout/v/stable)](https://packagist.org/packages/paradoxlabs/authnetcim-hyva-checkout)
[![License](https://poser.pugx.org/paradoxlabs/authnetcim-hyva-checkout/license)](https://packagist.org/packages/paradoxlabs/authnetcim-hyva-checkout)
[![Total Downloads](https://poser.pugx.org/paradoxlabs/authnetcim-hyva-checkout/downloads)](https://packagist.org/packages/paradoxlabs/authnetcim-hyva-checkout)

<p align="center">
    <a href="https://www.paradoxlabs.com"><img alt="ParadoxLabs" src="https://paradoxlabs.com/wp-content/uploads/2020/02/pl-logo-canva-2.png" width="250"></a>
</p>

This module adds support for Hyva Checkout to our [Authorize.net CIM payment method for Magento 2](https://github.com/ParadoxLabs-Inc/authnetcim).

Requirements
============

* Magento 2.4 (or equivalent version of Adobe Commerce, Adobe Commerce Cloud, or Mage-OS)
* Hyva Checkout (separate product and license)

Features
========

* Place orders via Hyva Checkout, with Authorize.net CIM payment
  ![2023-12-01_152853](https://github.com/ParadoxLabs-Inc/authnetcim-hyva-checkout/assets/13335952/c55eeb44-a24a-4a35-b946-b783d4a77033)
* Supports payment by credit card, ACH, and stored cards (via CIM tokenization). See Authorize.net extension for full details.

Limitations
===========

* Requires the 'Accept Hosted' iframe payment form at this time (no Accept.js or inline form support yet)
* Does not yet implement the 'My Payment Options' customer account interface for stored card management

Installation and Usage
======================

In SSH at your Magento base directory, run:

    composer require paradoxlabs/authnetcim-hyva-checkout
    php bin/magento module:enable ParadoxLabs_AuthnetcimHyvaCheckout
    php bin/magento setup:upgrade

## Applying Updates

In SSH at your Magento base directory, run:

    composer update paradoxlabs/authnetcim-hyva-checkout
    php bin/magento setup:upgrade

These commands will download and apply any available updates to the module.

If you have any integrations or custom functionality based on this extension, we strongly recommend testing to ensure they are not affected.

Changelog
=========

Please see [CHANGELOG.md](https://github.com/ParadoxLabs-Inc/authnetcim-hyva-checkout/blob/master/CHANGELOG.md).

Support
=======

This module is provided free and without support of any kind. You may report issues you've found in the module, and we will address them as we are able, but **no support will be provided here.**

**DO NOT include any API keys, credentials, or customer-identifying in issues, pull requests, or comments. Any personally identifying information will be deleted on sight.**

If you need personal support services, please [buy an extension support plan from ParadoxLabs](https://store.paradoxlabs.com/support-renewal.html), then open a ticket at [support.paradoxlabs.com](https://support.paradoxlabs.com).

Contributing
============

Please feel free to submit pull requests with any contributions. We welcome and appreciate your support, and will acknowledge contributors.

This module is maintained by ParadoxLabs, a Magento solutions provider. We make no guarantee of accepting contributions, especially any that introduce architectural changes.

License
=======

This module is licensed under [APACHE LICENSE, VERSION 2.0](https://github.com/ParadoxLabs-Inc/authnetcim/blob/master/LICENSE).
