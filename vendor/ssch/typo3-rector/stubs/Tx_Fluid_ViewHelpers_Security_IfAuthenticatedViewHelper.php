<?php

namespace RectorPrefix20210708;

if (\class_exists('Tx_Fluid_ViewHelpers_Security_IfAuthenticatedViewHelper')) {
    return;
}
class Tx_Fluid_ViewHelpers_Security_IfAuthenticatedViewHelper
{
}
\class_alias('Tx_Fluid_ViewHelpers_Security_IfAuthenticatedViewHelper', 'Tx_Fluid_ViewHelpers_Security_IfAuthenticatedViewHelper', \false);