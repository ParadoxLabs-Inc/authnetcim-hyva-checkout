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

class AuthnetcimAch extends Authnetcim
{
    protected const METHOD_CODE = 'authnetcim_ach';

    /**
     * Determine whether all required fields are present
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function isRequiredDataPresent(): bool
    {
        if (!empty($this->selectedCard) || !empty($this->transactionId)) {
            return true;
        }

        return false;
    }
}
