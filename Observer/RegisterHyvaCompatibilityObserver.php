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

namespace ParadoxLabs\AuthnetcimHyvaCheckout\Observer;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Dispatcher for the `hyva_config_generate_before` event.
 */
class RegisterHyvaCompatibilityObserver implements ObserverInterface
{
    /**
     * RegisterHyvaCompatibilityObserver constructor.
     *
     * @param ComponentRegistrar $componentRegistrar
     */
    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar
    ) {
    }

    public function execute(Observer $event)
    {
        $config     = $event->getData('config');
        $extensions = $config->hasData('extensions') ? $config->getData('extensions') : [];

        if (!is_array($extensions)) {
            $extensions = [];
        }

        $moduleName = implode('_', array_slice(explode('\\', self::class), 0, 2));

        $path = (string) $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);

        // ComponentRegistrar resolves registration.php's __DIR__ to a real path. On a normal install
        // that path is under the Magento base dir, so we emit it relative to BP — the form the Hyvä
        // tailwind tooling (@hyva-themes/hyva-modules) expects, since it does path.join(basePath, src).
        //
        // In dev these modules are frequently symlinked into app/code from outside BP (e.g. /mnt/c/...),
        // so the real path is NOT under BP and a naive substr() yields a truncated, garbage src. Because
        // path.join() does not honor absolute paths, emitting the absolute real path would not help
        // either; instead we emit the app/code symlink location (which resolves back to the module) when
        // that symlink is present and points at the same target.
        if (str_starts_with($path, BP . DIRECTORY_SEPARATOR)) {
            $src = substr($path, strlen(BP) + 1);
        } else {
            $appCodePath = 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR
                . str_replace('_', DIRECTORY_SEPARATOR, $moduleName);
            $symlink     = BP . DIRECTORY_SEPARATOR . $appCodePath;

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (is_link($symlink) && realpath($symlink) === realpath($path)) {
                $src = $appCodePath;
            } else {
                // Last resort: keep the historical behavior.
                $src = substr($path, strlen(BP) + 1);
            }
        }

        $extensions[] = ['src' => $src];

        $config->setData('extensions', $extensions);
    }
}
