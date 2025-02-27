<?php

namespace DMK\Mklog\Backend;

/***************************************************************
 * Copyright notice
 *
 * (c) 2016 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

// error_reporting(E_ALL & ~E_NOTICE);
// ini_set("display_errors", 1);

\tx_rnbase::load('tx_rnbase_mod_BaseModule');

/**
 * MK Log backend module.
 *
 * @author Michael Wagner
 */
class ModuleBackend extends \tx_rnbase_mod_BaseModule
{
    /**
     * Initializes the backend module by setting internal variables, initializing the menu.
     */
    public function init()
    {
        $GLOBALS['LANG']->includeLLFile('EXT:mklog/Resources/Private/Language/Backend.xlf');
        parent::init();
    }

    /**
     * Method to get the extension key.
     *
     * @return string Extension key
     */
    public function getExtensionKey()
    {
        return 'mklog';
    }
}
