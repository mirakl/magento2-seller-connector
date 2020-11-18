<?php
define('MIRAKL_SELLER_BP', dirname(__DIR__));

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'MiraklSeller_Api',
    __DIR__
);
