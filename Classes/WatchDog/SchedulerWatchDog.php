<?php

namespace DMK\Mklog\WatchDog;

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

\tx_rnbase::load('Tx_Rnbase_Scheduler_Task');
\tx_rnbase::load('Tx_Rnbase_Domain_Model_Data');

/**
 * MK Log watchdog.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class SchedulerWatchDog extends \Tx_Rnbase_Scheduler_Task
{
    /**
     * Was used as the scheduler options before making the extension compatible with TYPO3 9. But as private
     * class variables can't be serialized anymore (@see __makeUp() method) this variable can't be used anymore.
     *
     * @var \Tx_Rnbase_Domain_Model_Data
     *
     * @deprecated can be removed including the __wakeup() method when support for TYPO3 8.7 and below is dropped.
     */
    private $options = null;

    /**
     * Internal options storage.
     *
     * @var \Tx_Rnbase_Domain_Model_Data
     */
    protected $schedulerOptions = null;

    /**
     * Was used as the scheduler options before making the extension compatible with TYPO3 9. But as private
     * class variables can't be serialized anymore (@see __makeUp() method) this variable can't be used anymore.
     *
     * @var \DMK\Mklog\WatchDog\Transport\InterfaceTransport
     *
     * @deprecated can be removed including the __wakeup() method when support for TYPO3 8.7 and below is dropped.
     */
    private $transport = null;

    /**
     * The current configured transport.
     *
     * @var \DMK\Mklog\WatchDog\Transport\InterfaceTransport
     */
    protected $messageTransport = null;

    /**
     * After the update to TYPO3 9 the private $options variable can't be serialized and therefore not saved in the
     * database anymore as our parent implemented the __sleep() method to return the class variables which should be
     * serialized/saved. So to keep the possibly saved $options we need to move them to $schedulerOptions if present.
     * Otherwise the $options will be lost after the scheduler is executed/saved. Same for $transport.
     */
    public function __wakeup()
    {
        if (method_exists(parent::class, '__wakeup')) {
            parent::__wakeup();
        }

        if ($this->options && !$this->schedulerOptions) {
            $this->schedulerOptions = $this->options;
        }

        if ($this->transport && !$this->messageTransport) {
            $this->messageTransport = $this->transport;
        }
    }

    /**
     * Returns a storage.
     *
     * @return \Tx_Rnbase_Domain_Model_Data
     */
    public function getOptions()
    {
        if (null === $this->schedulerOptions) {
            $this->schedulerOptions = \tx_rnbase::makeInstance(
                'Tx_Rnbase_Domain_Model_Data'
            );
        }

        return $this->schedulerOptions;
    }

    /**
     * Do the magic and publish all new messages thu the transport.
     *
     * @return bool Returns TRUE on successful execution, FALSE on error
     */
    public function execute()
    {
        $failures = $successes = array();

        $transport = $this->getTransport();

        // initialize the transport
        $transport->initialize($this->getOptions());

        /* @var $message \DMK\Mklog\Domain\Model\DevlogEntryModel */
        foreach ($this->findMessages() as $message) {
            try {
                $transport->publish($message);
                // mark entry as send for current transport
                $this->markAsTransported($message);
                $successes[$message->getUid()] = '';
            } catch (\Exception $e) {
                $failures[$message->getUid()] = $e->getMessage();
            }
        }

        // shutdown the transport
        $transport->shutdown();

        \tx_rnbase::load('tx_rnbase_util_Logger');
        $success = empty($failures);
        $msg = sprintf(
            'WatchDog %1$s has %2$d messages send and %3$d failures.',
            $this->getTransportId(),
            count($successes),
            count($failures)
        );
        $logMethod = $success ? 'debug' : 'warning';
        \Tx_Rnbase_Utility_Logger::$logMethod(
            'mklog',
            $msg,
            array(
                'transport' => $this->getTransportId(),
                'successes' => $successes,
                'failures' => $failures,
            )
        );

        // create a flash message for the beuser
        \tx_rnbase::load('tx_rnbase_util_TYPO3');
        if (\tx_rnbase_util_TYPO3::getBEUserUID()) {
            \tx_rnbase::load('tx_rnbase_util_Misc');
            \tx_rnbase_util_Misc::addFlashMessage(
                $msg,
                'MK LOGGER WatchDog',
                $success ? 0 : 2,
                false
            );
        }

        return $success;
    }

    /**
     * Returns all untransportet messages.
     *
     * @return \Tx_Rnbase_Domain_Collection_Base
     */
    protected function findMessages()
    {
        $repo = \DMK\Mklog\Factory::getDevlogEntryRepository();

        $fields = $options = array();

        $fields[SEARCH_FIELD_CUSTOM] = sprintf(
            'NOT FIND_IN_SET(\'%s\', `transport_ids`)',
            $this->getTransportId()
        );

        if ($this->getOptions()->getSeverity()) {
            $fields['DEVLOGENTRY.severity'][OP_LTEQ_INT] = $this->getOptions()->getSeverity();
        }

        $limit = $this->getOptions()->getMessageLimit();
        // fallback of 100, if no limit is configured
        if (null === $limit) {
            $limit = 100;
        }
        $limit = (int) $limit;
        if ($limit > 0) {
            $options['limit'] = $limit;
        }

        $options['orderby'] = ['DEVLOGENTRY.crdate' => 'ASC'];

        return $repo->search($fields, $options);
    }

    /**
     * Marks the message as transported.
     *
     * @param \DMK\Mklog\Domain\Model\DevlogEntryModel $message
     */
    protected function markAsTransported(
        \DMK\Mklog\Domain\Model\DevlogEntryModel $message
    ) {
        $repo = \DMK\Mklog\Factory::getDevlogEntryRepository();
        $repo->persist(
            $message->addTransportId(
                $this->getTransportId()
            )
        );
    }

    /**
     * Creates the transport.
     *
     * @return \DMK\Mklog\WatchDog\Transport\InterfaceTransport
     */
    protected function getTransport()
    {
        if (null === $this->messageTransport) {
            $this->messageTransport = \DMK\Mklog\Factory::getTransport(
                $this->getOptions()->getTransport()
            );
        }

        return $this->messageTransport;
    }

    /**
     * Creates the transport id.
     *
     * @return \DMK\Mklog\WatchDog\Transport\InterfaceTransport
     */
    protected function getTransportId()
    {
        return $this->getTransport()->getIdentifier().':'.$this->getTaskUid();
    }

    /**
     * This method returns the destination mail address as additional information.
     *
     * @return string Information to display
     */
    public function getAdditionalInformation()
    {
        if ($this->getOptions()->isEmpty()) {
            return '';
        }

        \tx_rnbase::load('Tx_Rnbase_Utility_Strings');

        $options = array();

        foreach ($this->getOptions() as $key => $value) {
            $key = \Tx_Rnbase_Utility_Strings::underscoredToLowerCamelCase($key);
            $options[] = ucfirst($key).': '.$value;
        }

        return 'Options: '.implode('; ', $options);
    }
}
