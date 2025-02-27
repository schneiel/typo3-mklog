<?php

namespace DMK\Mklog\WatchDog\Transport\Gelf;

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

\tx_rnbase::load('DMK\\Mklog\\WatchDog\\Transport\\AbstractTransport');
\tx_rnbase::load('Tx_Rnbase_Interface_Singleton');

/**
 * MK Log watchdog gelf transporter.
 *
 * All chunks MUST arrive within 5 seconds
 * or the server will discard all already arrived and still arriving chunks.
 * A message MUST NOT consist of more than 128 chunks.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class HttpGelf extends \DMK\Mklog\WatchDog\Transport\Gelf\AbstractGelf
{
    /**
     * Creates the Transport.
     *
     * @return \Gelf\Transport\AbstractTransport
     */
    protected function getTransport()
    {
        $transport = \Gelf\Transport\HttpTransport::fromUrl(
            $this->getOptions()->getCredentials()
        );

        return $transport;
    }
}
