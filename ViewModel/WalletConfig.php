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

namespace ParadoxLabs\AuthnetcimHyvaCheckout\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use ParadoxLabs\Authnetcim\Model\Wallet\CheckoutConfig;
use ParadoxLabs\Authnetcim\Model\Wallet\WalletType;
use Throwable;

/**
 * Client-side Apple Pay / Google Pay configuration for Hyva Checkout (same data as the Luma config provider).
 */
class WalletConfig implements ArgumentInterface
{
    /**
     * Wallet client library, from ParadoxLabs_Authnetcim view/base (exposes window.AuthnetcimWalletClient).
     */
    public const CLIENT_SCRIPT = 'ParadoxLabs_Authnetcim::js/wallet/client.js';

    /**
     * @var array|null
     */
    private ?array $config = null;

    /**
     * @param CheckoutConfig $checkoutConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly CheckoutConfig $checkoutConfig,
        private readonly StoreManagerInterface $storeManager,
    ) {
    }

    /**
     * Get the wallet client config for the current store. Empty when no wallet is enabled and fully configured.
     *
     * @return array
     */
    public function getConfig(): array
    {
        if ($this->config === null) {
            try {
                $this->config = $this->checkoutConfig->getConfig(
                    (int)$this->storeManager->getStore()->getId()
                );
            } catch (Throwable) {
                $this->config = [];
            }
        }

        return $this->config;
    }

    /**
     * Whether any wallet is enabled and configured, i.e. whether anything should render at all.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return !empty($this->getConfig());
    }

    /**
     * Get the wallet payment method code.
     *
     * @return string
     */
    public function getMethodCode(): string
    {
        return WalletType::METHOD_CODE;
    }
}
