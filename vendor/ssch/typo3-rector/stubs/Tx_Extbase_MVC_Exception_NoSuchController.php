<?php

namespace RectorPrefix20210720;

if (\class_exists('Tx_Extbase_MVC_Exception_NoSuchController')) {
    return;
}
class Tx_Extbase_MVC_Exception_NoSuchController
{
}
\class_alias('Tx_Extbase_MVC_Exception_NoSuchController', 'Tx_Extbase_MVC_Exception_NoSuchController', \false);