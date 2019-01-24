<?php

namespace Ampersand\CurrencyConverter\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getTimeout()
    {
        return (int)$this->scopeConfig->getValue('currency/freecurrencyconverter/timeout');
    }
}
