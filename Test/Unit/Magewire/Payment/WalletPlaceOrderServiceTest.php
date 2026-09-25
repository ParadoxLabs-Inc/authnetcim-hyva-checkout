<?php

declare(strict_types=1);

namespace ParadoxLabs\AuthnetcimHyvaCheckout\Test\Unit\Magewire\Payment;

use Exception;
use Hyva\Checkout\Model\Magewire\Component\Evaluation\ErrorMessage;
use Hyva\Checkout\Model\Magewire\Component\Evaluation\Success;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Payment\DefaultOrderData;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magewirephp\Magewire\Component;
use ParadoxLabs\AuthnetcimHyvaCheckout\Magewire\Payment\WalletPlaceOrderService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for the Hyva Checkout wallet (Apple Pay / Google Pay) place order service
 */
class WalletPlaceOrderServiceTest extends TestCase
{
    private WalletPlaceOrderService $service;
    private CartManagementInterface|MockObject $cartManagement;
    private Quote|MockObject $quote;
    private QuotePayment|MockObject $payment;

    protected function setUp(): void
    {
        $this->cartManagement = $this->createMock(CartManagementInterface::class);

        $this->payment = $this->createMock(QuotePayment::class);
        $this->payment->method('getMethod')->willReturn('authnetcim_wallet');

        $this->quote = $this->createMock(Quote::class);
        $this->quote->method('getPayment')->willReturn($this->payment);
        $this->quote->method('getId')->willReturn(7);

        $this->service = new WalletPlaceOrderService(
            $this->cartManagement,
            new DefaultOrderData(),
        );
    }

    public function testPlaceOrderImportsWalletDataAndStripsUnknownKeys(): void
    {
        $this->service->getData()->setData([
            'payment' => [
                'method' => 'checkmo',
                'wallet_type' => 'applepay',
                'wallet_token' => 'eyJ0b2tlbiI6MX0=',
                'card_display' => 'Visa 1234',
                'tokenbase_id' => '999',
                'acceptjs_value' => 'evil',
            ],
        ]);

        $this->payment->expects($this->once())
            ->method('importData')
            ->with([
                PaymentInterface::KEY_METHOD => 'authnetcim_wallet',
                PaymentInterface::KEY_ADDITIONAL_DATA => [
                    'wallet_type' => 'applepay',
                    'wallet_token' => 'eyJ0b2tlbiI6MX0=',
                    'card_display' => 'Visa 1234',
                ],
            ]);

        $this->cartManagement->expects($this->once())
            ->method('placeOrder')
            ->with(7, $this->payment)
            ->willReturn('42');

        $this->assertSame(42, $this->service->placeOrder($this->quote));
    }

    public function testPlaceOrderDefaultsMissingCardDisplayAndDropsNonScalars(): void
    {
        $this->service->getData()->setData([
            'payment' => [
                'wallet_type' => 'googlepay',
                'wallet_token' => 'dG9rZW4=',
                'card_display' => [
                    'Visa',
                ],
            ],
        ]);

        $this->payment->expects($this->once())
            ->method('importData')
            ->with([
                PaymentInterface::KEY_METHOD => 'authnetcim_wallet',
                PaymentInterface::KEY_ADDITIONAL_DATA => [
                    'wallet_type' => 'googlepay',
                    'wallet_token' => 'dG9rZW4=',
                    'card_display' => '',
                ],
            ]);

        $this->cartManagement->method('placeOrder')->willReturn('5');

        $this->assertSame(5, $this->service->placeOrder($this->quote));
    }

    public function testPlaceOrderRejectsInvalidWalletType(): void
    {
        $this->service->getData()->setData([
            'payment' => [
                'wallet_type' => 'paypal',
                'wallet_token' => 'dG9rZW4=',
            ],
        ]);

        $this->payment->expects($this->never())->method('importData');
        $this->cartManagement->expects($this->never())->method('placeOrder');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid wallet type.');

        $this->service->placeOrder($this->quote);
    }

    public function testPlaceOrderRejectsMissingToken(): void
    {
        $this->service->getData()->setData([
            'payment' => [
                'wallet_type' => 'googlepay',
                'wallet_token' => '',
            ],
        ]);

        $this->payment->expects($this->never())->method('importData');
        $this->cartManagement->expects($this->never())->method('placeOrder');

        $this->expectException(LocalizedException::class);

        $this->service->placeOrder($this->quote);
    }

    public function testPlaceOrderRejectsOtherQuotePaymentMethod(): void
    {
        $payment = $this->createMock(QuotePayment::class);
        $payment->method('getMethod')->willReturn('authnetcim');
        $payment->expects($this->never())->method('importData');

        $quote = $this->createMock(Quote::class);
        $quote->method('getPayment')->willReturn($payment);

        $this->service->getData()->setData([
            'payment' => [
                'wallet_type' => 'googlepay',
                'wallet_token' => 'dG9rZW4=',
            ],
        ]);

        $this->cartManagement->expects($this->never())->method('placeOrder');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid payment method.');

        $this->service->placeOrder($quote);
    }

    /**
     * handleException must NOT rethrow: the storefront needs the order:place:authnetcim_wallet:error event to close
     * the wallet sheet with a failure status.
     */
    public function testHandleExceptionDoesNotRethrowAndBlocksRedirect(): void
    {
        $this->assertTrue($this->service->canRedirect());

        $this->service->handleException(
            new Exception('gateway boom'),
            $this->createMock(Component::class),
            $this->quote
        );

        $this->assertFalse($this->service->canRedirect());
    }

    public function testEvaluateCompletionAfterLocalizedFailureShowsRealMessage(): void
    {
        $this->service->handleException(
            new LocalizedException(__('Card declined.')),
            $this->createMock(Component::class),
            $this->quote
        );

        $errorMessage = $this->createMock(ErrorMessage::class);
        $errorMessage->expects($this->once())->method('withMessage')->with('Card declined.');

        $resultFactory = $this->createMock(EvaluationResultFactory::class);
        $resultFactory->method('createErrorMessage')->willReturn($errorMessage);
        $resultFactory->expects($this->never())->method('createSuccess');

        $this->assertSame($errorMessage, $this->service->evaluateCompletion($resultFactory));
    }

    public function testEvaluateCompletionAfterGenericFailureHidesInternalMessage(): void
    {
        $this->service->handleException(
            new RuntimeException('PDO: connection refused'),
            $this->createMock(Component::class),
            $this->quote
        );

        $errorMessage = $this->createMock(ErrorMessage::class);
        $errorMessage->expects($this->once())
            ->method('withMessage')
            ->with($this->logicalAnd(
                $this->stringContains('Something went wrong'),
                $this->logicalNot($this->stringContains('PDO'))
            ));

        $resultFactory = $this->createMock(EvaluationResultFactory::class);
        $resultFactory->method('createErrorMessage')->willReturn($errorMessage);

        $this->assertSame($errorMessage, $this->service->evaluateCompletion($resultFactory));
    }

    public function testEvaluateCompletionWithoutFailureUsesDefaultSuccess(): void
    {
        $success = $this->createMock(Success::class);

        $resultFactory = $this->createMock(EvaluationResultFactory::class);
        $resultFactory->expects($this->once())->method('createSuccess')->willReturn($success);
        $resultFactory->expects($this->never())->method('createErrorMessage');

        $this->assertSame($success, $this->service->evaluateCompletion($resultFactory, 42));
        $this->assertTrue($this->service->canRedirect());
    }
}
