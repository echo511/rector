<?php

namespace RectorPrefix20210708;

if (\class_exists('SC_logout')) {
    return;
}
class SC_logout
{
}
\class_alias('SC_logout', 'SC_logout', \false);