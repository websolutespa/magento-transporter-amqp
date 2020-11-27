<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const IMPORT_AMQP_IS_ENABLED_CONFIG_PATH = 'transporter/amqp/enabled';
    const IMPORT_AMQP_IS_ENABLED_FOR_DOWNLOADERS_CONFIG_PATH = 'transporter/amqp/enabled_for_downloaders';
    const IMPORT_AMQP_IS_ENABLED_FOR_MANIPULATORS_CONFIG_PATH = 'transporter/amqp/enabled_for_manipulators';
    const IMPORT_AMQP_IS_ENABLED_FOR_UPLOADERS_CONFIG_PATH = 'transporter/amqp/enabled_for_uploaders';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Websolute\TransporterImporter\Model\Config
     */
    private $baseConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param \Websolute\TransporterBase\Model\Config $baseConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Websolute\TransporterBase\Model\Config $baseConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->baseConfig = $baseConfig;
    }

    /**
     * @return bool
     */
    public function isEnabledForDownloaders(): bool
    {
        return $this->isEnabled() && (bool)$this->scopeConfig->getValue(
            self::IMPORT_AMQP_IS_ENABLED_FOR_DOWNLOADERS_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->baseConfig->isEnabled() && (bool)$this->scopeConfig->getValue(
            self::IMPORT_AMQP_IS_ENABLED_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isEnabledForTranformers(): bool
    {
        return $this->isEnabled() && (bool)$this->scopeConfig->getValue(
            self::IMPORT_AMQP_IS_ENABLED_FOR_MANIPULATORS_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isEnabledForUploaders(): bool
    {
        return $this->isEnabled() && (bool)$this->scopeConfig->getValue(
            self::IMPORT_AMQP_IS_ENABLED_FOR_UPLOADERS_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }
}
