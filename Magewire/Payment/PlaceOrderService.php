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
    private const ALLOWED_KEYS = [
        'method' => null,
        'card_id' => null,
        'save' => null,
        'cc_number' => null,
        'cc_type' => null,
        'cc_exp_month' => null,
        'cc_exp_year' => null,
        'cc_cid' => null,
        'cc_last4' => null,
        'cc_bin' => null,
        'transaction_id' => null,
        'acceptjs_key' => null,
        'acceptjs_value' => null,
    ];

    /**
     * @throws CouldNotSaveException
     */
    public function placeOrder(\Magento\Quote\Model\Quote $quote): int
    {
        $paymentData = $this->getData()->getPayment();

        // Only pass through known allowed values, to prevent parameter injection
        $knownPaymentData = array_intersect_key(
            $paymentData,
            self::ALLOWED_KEYS
        );

        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $quote->getPayment();
        $payment->addData($knownPaymentData);

        return parent::placeOrder($quote);
    }
}
