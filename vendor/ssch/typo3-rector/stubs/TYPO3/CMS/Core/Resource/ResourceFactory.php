<?php

namespace RectorPrefix20211027\TYPO3\CMS\Core\Resource;

if (\class_exists('TYPO3\\CMS\\Core\\Resource\\ResourceFactory')) {
    return;
}
class ResourceFactory
{
    /**
     * @return $this
     */
    public static function getInstance()
    {
        return new self();
    }
}
