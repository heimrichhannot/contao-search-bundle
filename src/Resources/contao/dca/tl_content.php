<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2019 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */

use HeimrichHannot\SearchBundle\Controller\ContentElement\RelatedSearchLinkElementController;

$dc = &$GLOBALS['TL_DCA']['tl_content'];

$dc['palettes'][RelatedSearchLinkElementController::TYPE] = $dc['palettes']['hyperlink'];