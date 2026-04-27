<?php
declare(strict_types=1);

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
    public function __construct(private ComponentRegistrar $componentRegistrar)
    {
    }

    public function execute(Observer $event)
    {
        $config     = $event->getData('config');
        $extensions = $config->hasData('extensions') ? $config->getData('extensions') : [];

        $moduleName = implode('_', array_slice(explode('\\', __CLASS__), 0, 2));

        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);

        // Only use the path relative to the Magento base dir
        $extensions[] = ['src' => substr((string) $path, strlen(BP) + 1)];

        $config->setData('extensions', $extensions);
    }
}
