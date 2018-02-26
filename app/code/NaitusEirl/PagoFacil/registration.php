<?php

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'NaitusEirl_PagoFacil',
    __DIR__
);

$vendorAutoload = BP . "/lib/internal/ctala/transaccion-default/vendor/autoload.php";
/** @var \Composer\Autoload\ClassLoader $composerAutoloader */
$composerAutoloader = include $vendorAutoload;
$composerAutoloader->addPsr4('ctala\\transaccion\\','/lib/internal/ctala/transaccion-default');