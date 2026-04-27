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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Payment\Model\MethodInterface;
use ParadoxLabs\Authnetcim\Helper\Data;
use ParadoxLabs\TokenBase\Block\Form\Cc;

class PaymentForm implements ArgumentInterface
{
    /**
     * @var Cc
     */
    protected $formBlock;

    /**
     * PaymentForm constructor.
     *
     * @param Data $helper
     * @param LayoutInterface $layout
     */
    public function __construct(
        protected readonly Data $helper,
        protected readonly LayoutInterface $layout,
    ) {
    }

    /**
     * Get the active payment method instance
     *
     * @param string $code
     * @return MethodInterface
     * @throws LocalizedException
     */
    public function getMethod(string $code): MethodInterface
    {
        return $this->helper->getMethodInstance($code);
    }

    /**
     * Get the active payment method form block
     *
     * @param string $code
     * @return Cc
     * @throws LocalizedException
     */
    public function getFormBlock(string $code): Cc
    {
        if (!isset($this->formBlock)) {
            $this->formBlock = $this->helper->getMethodFormBlock(
                $this->getMethod($code),
                $this->layout
            );
        }

        return $this->formBlock;
    }
}
