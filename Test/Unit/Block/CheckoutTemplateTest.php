<?php

declare(strict_types=1);

namespace ParadoxLabs\AuthnetcimHyvaCheckout\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use ParadoxLabs\Authnetcim\Model\Ach\ConfigProvider as AchConfigProvider;
use ParadoxLabs\Authnetcim\Model\ConfigProvider;
use ParadoxLabs\AuthnetcimHyvaCheckout\Block\CheckoutTemplate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Authnetcim Hyva checkout template block
 */
class CheckoutTemplateTest extends TestCase
{
    private ConfigProvider|MockObject $configProvider;
    private AchConfigProvider|MockObject $achConfigProvider;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->configProvider->method('getConfig')->willReturn([
            'payment' => [
                'authnetcim' => [
                    'selectedCard' => 'cc-hash',
                ],
            ],
        ]);

        $this->achConfigProvider = $this->createMock(AchConfigProvider::class);
        $this->achConfigProvider->method('getConfig')->willReturn([
            'payment' => [
                'authnetcim_ach' => [
                    'selectedCard' => 'ach-hash',
                ],
            ],
        ]);
    }

    public function testGetConfigUsesCcProviderForCc(): void
    {
        $this->assertSame(
            [
                'selectedCard' => 'cc-hash',
            ],
            $this->createBlock('authnetcim')->getConfig()
        );
    }

    public function testGetConfigUsesAchProviderForAch(): void
    {
        $this->assertSame(
            [
                'selectedCard' => 'ach-hash',
            ],
            $this->createBlock('authnetcim_ach')->getConfig()
        );
    }

    private function createBlock(string $methodCode): CheckoutTemplate
    {
        return (new ObjectManager($this))->getObject(
            CheckoutTemplate::class,
            [
                'configProvider' => $this->configProvider,
                'achConfigProvider' => $this->achConfigProvider,
                'data' => [
                    'method_code' => $methodCode,
                ],
            ]
        );
    }
}
