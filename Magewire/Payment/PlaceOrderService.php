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

use Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use ParadoxLabs\Authnetcim\Model\Ach\ConfigProvider as AchConfigProvider;
use ParadoxLabs\Authnetcim\Model\ConfigProvider;

class PlaceOrderService extends AbstractPlaceOrderService
{
    /**
     * Payment method codes this place order service is allowed to submit for
     */
    private const ALLOWED_METHODS = [
        ConfigProvider::CODE,
        AchConfigProvider::CODE,
    ];

    private const ALLOWED_KEYS = [
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
     * Assign the client payment data to the quote payment, then place the order.
     *
     * importData() (not addData()) so the payment_method_assign_data observer chain is guaranteed
     * to run before order placement — that is what resolves card_id into tokenbase_id, validates
     * and imports the Accept Hosted transaction_id, and copies acceptjs_key/acceptjs_value into
     * additional_information.
     *
     * The addData() that follows importData() is load-bearing, not redundant: Hyva's
     * AbstractPlaceOrderService passes the quote payment into CartManagement::placeOrder(), and
     * QuoteManagement::placeOrderRun re-runs importData($payment->getData()). On that second
     * import the Accept.js keys are no longer top-level, so processAcceptJs is skipped and
     * TokenBase's assignStandardData hits its `elseif (tokenbase_id === null)` branch and nulls
     * cc_last_4 — the just-created card and order lose their last4 ("Visa XXXX-"). Re-staging the
     * allowlisted raw keys as top-level payment data makes the second import re-run the observer
     * chain with the same inputs. processAcceptJs is idempotent: it only re-stashes the same
     * acceptjs_key/acceptjs_value/cc_last4 into additional_information and re-nulls tokenbase_id;
     * it does not consume the Accept.js nonce (that happens later at gateway auth, after the assign
     * chain), so running it twice with identical inputs yields identical state. processAcceptHosted
     * is likewise idempotent via its transaction_id-equality early return.
     *
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function placeOrder(Quote $quote): int
    {
        // The method code must come from the quote server-side, never from client input.
        $methodCode = (string)$quote->getPayment()->getMethod();

        if (!in_array($methodCode, self::ALLOWED_METHODS, true)) {
            throw new LocalizedException(__('Invalid payment method.'));
        }

        $paymentData = (array)$this->getData()->getPayment();

        // Only pass through known allowed values, to prevent parameter injection
        $knownPaymentData = array_intersect_key(
            $paymentData,
            self::ALLOWED_KEYS
        );

        $payment = $quote->getPayment();
        $payment->importData([
            PaymentInterface::KEY_METHOD => $methodCode,
            PaymentInterface::KEY_ADDITIONAL_DATA => $knownPaymentData,
        ]);

        // Re-stage the allowed keys as raw payment data so the re-import re-asserts them
        // through the observer chain instead of wiping cc_last_4 (see docblock).
        $payment->addData($knownPaymentData);

        return parent::placeOrder($quote);
    }
}
