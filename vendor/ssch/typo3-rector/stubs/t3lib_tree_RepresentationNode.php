<?php

namespace RectorPrefix20210720;

if (\class_exists('t3lib_tree_RepresentationNode')) {
    return;
}
class t3lib_tree_RepresentationNode
{
}
\class_alias('t3lib_tree_RepresentationNode', 't3lib_tree_RepresentationNode', \false);