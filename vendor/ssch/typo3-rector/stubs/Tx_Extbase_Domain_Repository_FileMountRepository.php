<?php

namespace RectorPrefix20210708;

if (\class_exists('Tx_Extbase_Domain_Repository_FileMountRepository')) {
    return;
}
class Tx_Extbase_Domain_Repository_FileMountRepository
{
}
\class_alias('Tx_Extbase_Domain_Repository_FileMountRepository', 'Tx_Extbase_Domain_Repository_FileMountRepository', \false);