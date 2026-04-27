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
use ParadoxLabs\Authnetcim\Model\ConfigProvider;
use ParadoxLabs\AuthnetcimHyvaCheckout\ViewModel\PaymentForm;
use ParadoxLabs\TokenBase\Gateway\Validator\CreditCard\Types;

class CheckoutTemplate extends Template
{
    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \ParadoxLabs\AuthnetcimHyvaCheckout\ViewModel\PaymentForm $paymentForm
     * @param \ParadoxLabs\Authnetcim\Model\ConfigProvider $configProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected PaymentForm $paymentForm,
        protected ConfigProvider $configProvider,
        protected Types $ccTypes,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
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
        $config = $this->configProvider->getConfig();

        return $config['payment'][ $this->getMethodCode() ] ?? [];
    }

    /**
     * Get credit card types, by code
     *
     * @return array
     */
    public function getCcTypes(): array
    {
        $types       = $this->ccTypes->getTypes();
        $typesByCode = [];
        foreach ($types as $type) {
            $typesByCode[ $type['type'] ] = $type;
        }

        return $typesByCode;
    }
}
