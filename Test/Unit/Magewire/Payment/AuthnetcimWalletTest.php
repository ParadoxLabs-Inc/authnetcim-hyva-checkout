<?php

declare(strict_types=1);

namespace ParadoxLabs\AuthnetcimHyvaCheckout\Test\Unit\Magewire\Payment;

use Hyva\Checkout\Model\Magewire\Component\Evaluation\ErrorMessage;
use Hyva\Checkout\Model\Magewire\Component\Evaluation\Validation;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use ParadoxLabs\AuthnetcimHyvaCheckout\Magewire\Payment\AuthnetcimWallet;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for the Apple Pay / Google Pay Magewire payment component
 */
class AuthnetcimWalletTest extends TestCase
{
    private AuthnetcimWallet $component;
    private CheckoutSession|MockObject $checkoutSession;

    protected function setUp(): void
    {
        $this->checkoutSession = $this->createMock(CheckoutSession::class);

        $this->component = new AuthnetcimWallet($this->checkoutSession);
    }

    public function testMountLoadsQuoteBaseGrandTotal(): void
    {
        $this->checkoutSession->method('getQuote')->willReturn($this->buildQuote('123.456'));

        $this->component->mount();

        $this->assertSame(123.46, $this->component->amount);
    }

    public function testRefreshAmountTracksTotalChanges(): void
    {
        $this->checkoutSession->method('getQuote')->willReturnOnConsecutiveCalls(
            $this->buildQuote('50.00'),
            $this->buildQuote('42.10')
        );

        $this->component->mount();
        $this->assertSame(50.0, $this->component->amount);

        $this->component->refreshAmount();
        $this->assertSame(42.1, $this->component->amount);
    }

    public function testRefreshAmountFallsBackToZeroWhenQuoteUnavailable(): void
    {
        $this->component->amount = 10.0;
        $this->checkoutSession->method('getQuote')->willThrowException(new RuntimeException('no session'));

        $this->component->refreshAmount();

        $this->assertSame(0.0, $this->component->amount);
    }

    public function testListensToEveryTotalsChangingEvent(): void
    {
        $listeners = $this->component->getListeners();

        foreach ([
            'shipping_method_selected',
            'coupon_code_applied',
            'coupon_code_revoked',
            'shipping_address_saved',
            'billing_address_saved',
        ] as $event) {
            $this->assertSame('refreshAmount', $listeners[$event] ?? null, $event);
        }
    }

    public function testEvaluateCompletionRegistersWalletValidator(): void
    {
        $errorMessage = $this->createMock(ErrorMessage::class);
        $errorMessage->expects($this->once())
            ->method('withMessage')
            ->with($this->stringContains('Apple Pay or Google Pay'));

        $validation = $this->createMock(Validation::class);
        $validation->expects($this->once())->method('withFailureResult')->with($errorMessage);

        $resultFactory = $this->createMock(EvaluationResultFactory::class);
        $resultFactory->method('createErrorMessage')->willReturn($errorMessage);
        $resultFactory->expects($this->once())
            ->method('createValidation')
            ->with('validateauthnetcim_wallet')
            ->willReturn($validation);

        $this->assertSame($validation, $this->component->evaluateCompletion($resultFactory));
    }

    /**
     * @param string $baseGrandTotal
     * @return Quote|MockObject
     */
    private function buildQuote(string $baseGrandTotal): Quote|MockObject
    {
        // getBaseGrandTotal is magic (no concrete declaration on Quote); stub getData() and let the real
        // DataObject::__call route it there.
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
        $quote->method('getData')->willReturnCallback(
            static fn ($key = '', $index = null) => $key === 'base_grand_total' ? $baseGrandTotal : null
        );

        return $quote;
    }
}
