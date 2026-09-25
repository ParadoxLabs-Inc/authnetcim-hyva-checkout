<?php

declare(strict_types=1);

namespace ParadoxLabs\AuthnetcimHyvaCheckout\Test\Unit\ViewModel;

use Magento\Framework\View\LayoutInterface;
use Magento\Payment\Model\MethodInterface;
use ParadoxLabs\Authnetcim\Helper\Data;
use ParadoxLabs\AuthnetcimHyvaCheckout\ViewModel\PaymentForm;
use ParadoxLabs\TokenBase\Block\Form\Cc;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the shared Authnetcim payment form view model
 */
class PaymentFormTest extends TestCase
{
    public function testFormBlockIsCachedPerMethodCode(): void
    {
        $ccMethod = $this->createMock(MethodInterface::class);
        $achMethod = $this->createMock(MethodInterface::class);
        $ccBlock = $this->createMock(Cc::class);
        $achBlock = $this->createMock(Cc::class);
        $layout = $this->createMock(LayoutInterface::class);

        $helper = $this->createMock(Data::class);
        $helper->method('getMethodInstance')->willReturnMap([
            [
                'authnetcim',
                $ccMethod,
            ],
            [
                'authnetcim_ach',
                $achMethod,
            ],
        ]);
        $helper->expects($this->exactly(2))
            ->method('getMethodFormBlock')
            ->willReturnCallback(
                static fn (MethodInterface $method) => $method === $ccMethod ? $ccBlock : $achBlock
            );

        $viewModel = new PaymentForm($helper, $layout);

        // The view model is shared: whichever method renders first must not leak its block to the other.
        $this->assertSame($ccBlock, $viewModel->getFormBlock('authnetcim'));
        $this->assertSame($achBlock, $viewModel->getFormBlock('authnetcim_ach'));
        $this->assertSame($ccBlock, $viewModel->getFormBlock('authnetcim'));
        $this->assertSame($achBlock, $viewModel->getFormBlock('authnetcim_ach'));
    }
}
