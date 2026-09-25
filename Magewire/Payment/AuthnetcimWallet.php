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

use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magewirephp\Magewire\Component;
use ParadoxLabs\Authnetcim\Model\Wallet\WalletType;
use Throwable;

/**
 * Apple Pay / Google Pay payment component.
 *
 * The wallet sheet needs the charge amount synchronously on the button click, so the quote base grand total is kept
 * current here (entangled into the Alpine component) and refreshed on every checkout event that can change totals.
 * Authorize.net charges base amounts, so the sheet shows the base total in the base currency, as on Luma.
 *
 * Wallet payment data (wallet_type, wallet_token, card_display) is not set here: it goes through the place-order
 * payload and is validated and assigned by {@see WalletPlaceOrderService}.
 */
class AuthnetcimWallet extends Component implements EvaluationInterface
{
    protected const METHOD_CODE = WalletType::METHOD_CODE;

    /**
     * Same totals-changing events the Hyva price summary listens to.
     *
     * @var string[]
     */
    protected $listeners = [
        'shipping_method_selected' => 'refreshAmount',
        'payment_method_selected' => 'refreshAmount',
        'coupon_code_applied' => 'refreshAmount',
        'coupon_code_revoked' => 'refreshAmount',
        'shipping_address_saved' => 'refreshAmount',
        'shipping_address_activated' => 'refreshAmount',
        'billing_address_saved' => 'refreshAmount',
        'billing_address_activated' => 'refreshAmount',
    ];

    /* Public component properties */
    public float $amount = 0.0;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(protected readonly CheckoutSession $checkoutSession)
    {
    }

    /**
     * Initialize component data on first render
     *
     * @return void
     */
    public function mount(): void
    {
        $this->refreshAmount();
    }

    /**
     * Load the quote's current base grand total for the wallet sheet.
     *
     * @return void
     */
    public function refreshAmount(): void
    {
        try {
            $this->amount = round((float)$this->checkoutSession->getQuote()->getBaseGrandTotal(), 2);
        } catch (Throwable) {
            $this->amount = 0.0;
        }
    }

    /**
     * Require the wallet payment data before order placement; it only exists after the wallet sheet was completed.
     *
     * @param EvaluationResultFactory $resultFactory
     * @return EvaluationResultInterface
     */
    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        $validationError = $resultFactory->createErrorMessage();
        $validationError->withMessage(
            (string)__('Please complete your payment with the Apple Pay or Google Pay button.')
        );
        $validationError->withVisibilityDuration(5000);

        $validation = $resultFactory->createValidation('validate' . static::METHOD_CODE);
        $validation->withFailureResult($validationError);

        return $validation;
    }
}
