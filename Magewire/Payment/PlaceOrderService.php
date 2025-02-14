<?php declare(strict_types=1);
/**
 * Copyright © 2023-present ParadoxLabs, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Need help? Try our knowledgebase and support system:
 *
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\AuthnetcimHyvaCheckout\Magewire\Payment;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartManagementInterface;

class PlaceOrderService extends \Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * PlaceOrderService constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        CartManagementInterface $cartManagement,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        parent::__construct($cartManagement);

        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @throws CouldNotSaveException
     */
    public function placeOrder(\Magento\Quote\Model\Quote $quote): int
    {
        // Load CVV in from session if present
        // TODO: Handle this via $this->getData() instead of checkoutSession
        // $ccCid = $this->checkoutSession->getStepData('payment', 'cc_cid');
        // if (!empty($ccCid) && is_numeric((string)$ccCid)) {
        //     $quote->getPayment()->setData('cc_cid', $ccCid);
        // }

        $paymentData = $this->getData()->getPayment();

        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $quote->getPayment();
        $payment->setData($paymentData);

        return parent::placeOrder($quote);
    }
}
