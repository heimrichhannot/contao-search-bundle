<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2019 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */


namespace HeimrichHannot\SearchBundle;


use HeimrichHannot\SearchBundle\DependencyInjection\ContaoSearchExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotSearchBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new ContaoSearchExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}