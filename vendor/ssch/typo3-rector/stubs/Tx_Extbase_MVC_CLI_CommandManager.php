<?php

namespace RectorPrefix20210708;

if (\class_exists('Tx_Extbase_MVC_CLI_CommandManager')) {
    return;
}
class Tx_Extbase_MVC_CLI_CommandManager
{
}
\class_alias('Tx_Extbase_MVC_CLI_CommandManager', 'Tx_Extbase_MVC_CLI_CommandManager', \false);