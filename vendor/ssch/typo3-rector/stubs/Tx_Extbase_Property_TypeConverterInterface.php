<?php

namespace RectorPrefix20210708;

if (\interface_exists('Tx_Extbase_Property_TypeConverterInterface')) {
    return;
}
interface Tx_Extbase_Property_TypeConverterInterface
{
}
\class_alias('Tx_Extbase_Property_TypeConverterInterface', 'Tx_Extbase_Property_TypeConverterInterface', \false);