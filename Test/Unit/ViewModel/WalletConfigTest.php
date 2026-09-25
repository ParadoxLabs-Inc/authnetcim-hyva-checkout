<?php

declare(strict_types=1);

namespace ParadoxLabs\AuthnetcimHyvaCheckout\Test\Unit\ViewModel;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use ParadoxLabs\Authnetcim\Model\Wallet\CheckoutConfig;
use ParadoxLabs\AuthnetcimHyvaCheckout\ViewModel\WalletConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for the Hyva wallet client config view model
 */
class WalletConfigTest extends TestCase
{
    private CheckoutConfig|MockObject $checkoutConfig;
    private StoreManagerInterface|MockObject $storeManager;
    private WalletConfig $viewModel;

    protected function setUp(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn('3');

        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->checkoutConfig = $this->createMock(CheckoutConfig::class);

        $this->viewModel = new WalletConfig(
            $this->checkoutConfig,
            $this->storeManager,
        );
    }

    public function testGetConfigUsesSharedCheckoutConfigForCurrentStore(): void
    {
        $config = [
            'wallets' => [
                'googlepay',
            ],
            'currencyCode' => 'USD',
        ];

        $this->checkoutConfig->expects($this->once())
            ->method('getConfig')
            ->with(3)
            ->willReturn($config);

        $this->assertSame($config, $this->viewModel->getConfig());
        // Memoized: the template calls it more than once per render.
        $this->assertSame($config, $this->viewModel->getConfig());
        $this->assertTrue($this->viewModel->isEnabled());
    }

    public function testDisabledWhenNoWalletConfigured(): void
    {
        $this->checkoutConfig->method('getConfig')->willReturn([]);

        $this->assertSame([], $this->viewModel->getConfig());
        $this->assertFalse($this->viewModel->isEnabled());
    }

    public function testDisabledWhenConfigThrows(): void
    {
        $this->checkoutConfig->method('getConfig')->willThrowException(new RuntimeException('store not found'));

        $this->assertFalse($this->viewModel->isEnabled());
    }

    public function testMethodCodeAndClientScript(): void
    {
        $this->assertSame('authnetcim_wallet', $this->viewModel->getMethodCode());
        $this->assertSame('ParadoxLabs_Authnetcim::js/wallet/client.js', WalletConfig::CLIENT_SCRIPT);
    }
}
