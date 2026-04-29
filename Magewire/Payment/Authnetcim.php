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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use ParadoxLabs\TokenBase\Model\Card;
use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magewirephp\Magewire\Component\Form;
use ParadoxLabs\Authnetcim\Model\Service\AcceptCustomer\FrontendRequest as AcceptCustomerService;
use ParadoxLabs\Authnetcim\Model\Service\AcceptHosted\FrontendRequest as AcceptHostedService;
use ParadoxLabs\AuthnetcimHyvaCheckout\ViewModel\PaymentForm;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;
use ParadoxLabs\TokenBase\Block\Form\Cc;
use ParadoxLabs\TokenBase\Helper\Data;
use Rakit\Validation\Validator;

class Authnetcim extends Form implements EvaluationInterface
{
    protected const METHOD_CODE = 'authnetcim';

    /**
     * @var bool
     */
    protected $loader = [
        'billing_address_activated' => 'Loading payment form',
        'billing_address_saved' => 'Loading payment form',
        'billing_address_submitted' => 'Loading payment form',
        'getNewCard' => 'Updating payment data',
        // 'initHostedForm' => 'Loading payment form',
    ];

    /**
     * @var string[]
     */
    protected $listeners = [
        'billing_address_activated' => 'initHostedForm',
        'billing_address_saved' => 'initHostedForm',
        'billing_address_submitted' => 'initHostedForm',
    ];

    /* Public component properties */
    public array $storedCards = [];

    /* Protected property validation rule map */
    protected $rules = [
        'storedCards' => 'array',
        'selectedCard' => 'alpha_num',
    ];

    /**
     * @param \Rakit\Validation\Validator $validator
     * @param CheckoutSession $checkoutSession
     * @param AcceptCustomerService $acceptCustomerService
     * @param AcceptHostedService $acceptHostedService
     * @param CardRepositoryInterface $cardRepository
     * @param Data $helper
     * @param PaymentForm $formViewModel
     */
    public function __construct(
        Validator $validator,
        protected readonly CheckoutSession $checkoutSession,
        protected readonly AcceptCustomerService $acceptCustomerService,
        protected readonly AcceptHostedService $acceptHostedService,
        protected readonly CardRepositoryInterface $cardRepository,
        protected readonly Data $helper,
        protected readonly PaymentForm $formViewModel
    ) {
        parent::__construct($validator);
    }

    /**
     * Initialize component data on update
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadSelectedCard();
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function mount(): void
    {
        $this->loadStoredCards();
    }

    /**
     * Update component selected card based on the quote's assigned stored card
     *
     * @return void
     * @throws LocalizedException
     */
    protected function loadSelectedCard(): void
    {
        $payment = $this->getQuote()->getPayment();

        if ($payment->getData('tokenbase_id') !== null) {
            $card = $this->cardRepository->getById($payment->getData('tokenbase_id'));

            if ($card->getMethod() === static::METHOD_CODE
                && (int)$card->getCustomerId() === (int)$this->getQuote()->getCustomerId()) {
                $this->addStoredCardToList($card);
            }
        }
    }

    /**
     * Generate Accept Hosted form token
     *
     * @return void
     * @see \ParadoxLabs\Authnetcim\Model\Service\AcceptHosted\FrontendRequest
     */
    public function initHostedForm(): void
    {
        if ($this->getMethod()->getConfigData('payment_action') === 'order') {
            $formService = $this->acceptCustomerService;
        } else {
            $formService = $this->acceptHostedService;
        }

        $formService->setMethodCode(static::METHOD_CODE);
        $params = $formService->getParams();

        $this->dispatchBrowserEvent(
            static::METHOD_CODE . 'InitHostedForm',
            $params
        );
    }

    /**
     * Get the current user's active quote
     *
     * @return CartInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getQuote(): CartInterface
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Determine whether checkout completion is allowed
     *
     * @param \Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory $resultFactory
     * @return \Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface
     */
    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        $validationError = $resultFactory->createErrorMessage();
        $validationError->withMessage('There\'s an issue with your payment details. Please check the payment form.');
        $validationError->withVisibilityDuration(5000);

        $validation = $resultFactory->createValidation('validate' . static::METHOD_CODE);
        $validation->withFailureResult($validationError);

        return $validation;
    }

    /**
     * Get the active payment method instance
     *
     * @return MethodInterface
     * @throws LocalizedException
     */
    protected function getMethod(): MethodInterface
    {
        return $this->formViewModel->getMethod(static::METHOD_CODE);
    }

    /**
     * Get the active payment method form block
     *
     * @return Cc
     * @throws LocalizedException
     */
    protected function getFormBlock(): Cc
    {
        return $this->formViewModel->getFormBlock(static::METHOD_CODE);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    protected function loadStoredCards(): void
    {
        /** @var Card $card */
        foreach ($this->getFormBlock()->getStoredCards() as $card) {
            $this->addStoredCardToList($card);
        }
    }

    /**
     * @return void
     */
    public function notifyCommunicatorFailure(): void
    {
        $this->helper->log(static::METHOD_CODE, 'ERROR: User failed to load hosted form communicator');

        $this->dispatchErrorMessage(
            __(
                'Payment gateway failed to connect. Please reload and try again. '
                . 'If the problem continues, please seek support.'
            )
        );
    }

    /**
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    public function getNewCard($data = []): void
    {
        // Import new card
        $newCard = $this->acceptCustomerService->getCard();

        $this->addStoredCardToList($newCard);
    }

    /**
     * @param CardInterface $card
     * @return void
     */
    protected function addStoredCardToList(CardInterface $card): void
    {
        $card = $card->getTypeInstance();
        $hash = (string)$card->getHash();

        $this->storedCards[$hash] = [
            'hash' => $hash,
            'label' => $card->getLabel(),
            'type' => $card->getType(),
        ];
    }
}
