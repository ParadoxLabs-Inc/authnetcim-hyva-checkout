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

use Exception;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magewirephp\Magewire\Component;
use ParadoxLabs\Authnetcim\Model\Wallet\WalletType;

/**
 * Place an Apple Pay / Google Pay order from the wallet sheet.
 *
 * The storefront calls hyvaCheckout.order.place() from inside the wallet sheet callback; the wallet validator puts
 * {wallet_type, wallet_token, card_display} into the place-order payment payload, and this assigns them to the quote
 * payment through the regular assign-data observer chain.
 *
 * Failures are reported through the order:place:authnetcim_wallet:error browser event, which the storefront waits
 * on to close the wallet sheet with a failure status, so this must never rethrow (see handleException()).
 */
class WalletPlaceOrderService extends AbstractPlaceOrderService
{
    /**
     * Wallet additional_data contract; anything else in the payload is dropped.
     */
    private const ALLOWED_KEYS = [
        'wallet_type' => null,
        'wallet_token' => null,
        'card_display' => null,
    ];

    /**
     * @var Exception|null
     */
    protected ?Exception $placeOrderException = null;

    /**
     * Assign the wallet payment data to the quote payment, then place the order.
     *
     * No re-staging is needed for the second importData() that QuoteManagement::placeOrder() runs against the same
     * payment instance: the wallet assign observer treats a re-import without a top-level wallet_token as a no-op,
     * so the token assigned here survives until the authorize command consumes (and clears) it.
     *
     * @param Quote $quote
     * @return int
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function placeOrder(Quote $quote): int
    {
        // The method code must come from the quote server-side, never from client input.
        $methodCode = (string)$quote->getPayment()->getMethod();

        if ($methodCode !== WalletType::METHOD_CODE) {
            throw new LocalizedException(__('Invalid payment method.'));
        }

        $walletData = $this->getWalletData();

        if (!isset(WalletType::DESCRIPTORS[$walletData['wallet_type']])) {
            throw new LocalizedException(__('Invalid wallet type.'));
        }

        if ($walletData['wallet_token'] === '') {
            throw new LocalizedException(
                __('Your wallet payment could not be completed. Please try again or choose another payment method.')
            );
        }

        $quote->getPayment()->importData([
            PaymentInterface::KEY_METHOD => $methodCode,
            PaymentInterface::KEY_ADDITIONAL_DATA => $walletData,
        ]);

        return parent::placeOrder($quote);
    }

    /**
     * Accept a place-order failure instead of rethrowing.
     *
     * The default rethrow can abort the Magewire request with a bare {message, code} response, which drops the
     * order:place:{method}:error browser event queued just before this call. The storefront needs that event to
     * complete the wallet sheet with a failure status and discard the spent wallet token.
     *
     * @param Exception $exception
     * @param Component $component
     * @param Quote $quote
     * @return void
     */
    public function handleException(Exception $exception, Component $component, Quote $quote): void
    {
        $this->placeOrderException = $exception;
    }

    /**
     * Report the place-order outcome: default success when an order was placed, else the (sanitized) failure reason.
     *
     * @param EvaluationResultFactory $resultFactory
     * @param int|null $orderId
     * @return EvaluationResultInterface
     */
    public function evaluateCompletion(
        EvaluationResultFactory $resultFactory,
        int|null $orderId = null,
    ): EvaluationResultInterface {
        if ($this->placeOrderException === null) {
            return parent::evaluateCompletion($resultFactory, $orderId);
        }

        $message = $this->placeOrderException instanceof LocalizedException
            ? $this->placeOrderException->getMessage()
            : (string)__('Something went wrong while processing your order. Please try again.');

        $errorMessage = $resultFactory->createErrorMessage();
        $errorMessage->withMessage($message);
        $errorMessage->withVisibilityDuration(7500);

        return $errorMessage;
    }

    /**
     * Block the processor's success-page redirect after a failed attempt, keeping the customer on checkout.
     *
     * @return bool
     */
    public function canRedirect(): bool
    {
        return $this->placeOrderException === null;
    }

    /**
     * Get the allowlisted wallet values from the place-order payload, as strings.
     *
     * @return array
     */
    private function getWalletData(): array
    {
        $paymentData = (array)$this->getData()->getPayment();
        $walletData  = [];

        foreach (array_keys(self::ALLOWED_KEYS) as $key) {
            $value = $paymentData[$key] ?? '';

            $walletData[$key] = is_scalar($value) ? trim((string)$value) : '';
        }

        return $walletData;
    }
}
