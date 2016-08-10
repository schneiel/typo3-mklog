<?php
namespace DMK\Mklog\WatchDog\Transport;

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
 * MK Log watchdog gelf transporter
 *
 * All chunks MUST arrive within 5 seconds
 * or the server will discard all already arrived and still arriving chunks.
 * A message MUST NOT consist of more than 128 chunks.
 *
 * @package TYPO3
 * @subpackage DMK\Mklog
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class GelfTransport
	extends AbstractTransport implements \Tx_Rnbase_Interface_Singleton
{
	/**
	 * The gelf publisher
	 *
	 * @var \Gelf\PublisherInterface
	 */
	private $publisher = null;

	/**
	 * An unique identifier for the transport
	 *
	 * @return string
	 */
	public function getIdentifier()
	{
		return 'mkloggelf';
	}

	/**
	 * Initializes the Transport
	 *
	 * @param \Tx_Rnbase_Domain_Model_Data $options
	 *
	 * @return void
	 */
	public function initialize(
		\Tx_Rnbase_Domain_Model_Data $options
	) {
		parent::initialize($options);

		\DMK\Mklog\Utility\ComposerUtility::autoload();
	}

	/**
	 * Publishes a message by the provider
	 *
	 * @param \DMK\Mklog\WatchDog\Message\InterfaceMessage $message
	 *
	 * @return void
	 */
	public function publish(
		\DMK\Mklog\WatchDog\Message\InterfaceMessage $message
	) {
		$gelfMsg = new \Gelf\Message();
		($gelfMsg
			->setTimestamp($message->getTimestamp())
			->setLevel($message->getLevel())
			->setShortMessage($message->getShortMessage())
			->setFullMessage($message->getFullMessage())
			->setAdditional('additional_data', $message->getAdditionalData())
			->setFacility($message->getFacility())
		);

		$this->getPublisher()->publish($gelfMsg);
	}

	/**
	 * Creates the Transport
	 *
	 * @return \Gelf\Transport\AbstractTransport
	 */
	protected function getTransport()
	{
		list($host, $port) = explode(
			',',
			$this->getOptions()->getCredentials()
		);

		return new \Gelf\Transport\UdpTransport(
			trim($host),
			trim($port),
			\Gelf\Transport\UdpTransport::CHUNK_SIZE_LAN
		);

	}

	/**
	 * Creates the Publisher
	 *
	 * @return \Gelf\PublisherInterface
	 */
	protected function getPublisher()
	{
		if ($this->publisher === null) {
			$this->publisher = new \Gelf\Publisher(
				$this->getTransport()
			);
		}

		return $this->publisher;
	}
}
