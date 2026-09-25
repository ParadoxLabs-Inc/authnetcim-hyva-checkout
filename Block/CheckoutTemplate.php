<?php declare(strict_types=1);
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\AuthnetcimHyvaCheckout\Block;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Override;
use ParadoxLabs\Authnetcim\Model\Ach\ConfigProvider as AchConfigProvider;
use ParadoxLabs\Authnetcim\Model\ConfigProvider;
use ParadoxLabs\AuthnetcimHyvaCheckout\ViewModel\PaymentForm;
use ParadoxLabs\TokenBase\Gateway\Validator\CreditCard\Types;

class CheckoutTemplate extends Template
{
    /**
     * Constructor
     *
     * @param Context $context
     * @param PaymentForm $paymentForm
     * @param ConfigProvider $configProvider
     * @param Types $ccTypes
     * @param AchConfigProvider $achConfigProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected readonly PaymentForm $paymentForm,
        protected readonly ConfigProvider $configProvider,
        protected readonly Types $ccTypes,
        protected readonly AchConfigProvider $achConfigProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    #[Override]
    public function getTemplate()
    {
        $method    = $this->paymentForm->getMethod($this->getMethodCode());
        $formType  = (string)($method->getConfigData('form_type') ?: ConfigProvider::FORM_HOSTED);
        $templates = (array)$this->getData('form_template');

        if (isset($templates[ $formType ])) {
            $this->_template = $templates[ $formType ];
        } else {
            throw new LocalizedException(
                __('No compatible template found for the %1 form.', $formType)
            );
        }

        return parent::getTemplate();
    }

    /**
     * Get payment method code for the active/relevant method
     *
     * @return string
     */
    public function getMethodCode(): string
    {
        return $this->getData('method_code') ?? ConfigProvider::CODE;
    }

    /**
     * Get payment form config object
     *
     * @return array
     */
    public function getConfig(): array
    {
        // Each method's config comes from its own provider; the CC provider only emits the CC key, which
        // left the eCheck form without selectedCard/defaultSaveCard.
        $configProvider = $this->getMethodCode() === AchConfigProvider::CODE
            ? $this->achConfigProvider
            : $this->configProvider;

        $config = $configProvider->getConfig();

        return $config['payment'][ $this->getMethodCode() ] ?? [];
    }

    /**
     * Get credit card types, by code
     *
     * @return array
     */
    public function getCcTypes(): array
    {
        return array_column($this->ccTypes->getTypes(), null, 'type');
    }
}
