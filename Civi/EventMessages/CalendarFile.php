<?php
/*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
| Copyright (C) 2024 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/


namespace Civi\EventMessages;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CalendarFile
 *
 * @package Civi\EventMessages
 *
 * This event allows you to modify/replace the ICS calendar file attached to a event communication
 *
 * Caution: the content is cached, and can currently not be individualised
 */
class CalendarFile extends Event
{
    /** @var string holds the initial ical data */
    protected $original_ical_data;

    /** @var string holds the current ical data */
    protected $ical_data;

    /** @var int event ID */
    protected $event_id;

  /**
   * @param string $ical_data
   * @param integer $event_id
   */
    public function __construct($ical_data, $event_id)
    {
        $this->original_ical_data = $ical_data;
        $this->ical_data = $ical_data;
        $this->event_id = $event_id;
    }

  /**
   * Replace the ical data with our own version
   *
   * @param string $new_ical_data
   *   the new ical data to be used as an .ics attachment
   *
   * @return void
   */
    public function setIcalData(string $new_ical_data)
    {
      $this->ical_data = $new_ical_data;
    }

  /**
   * Get the current ical data to be used as an .ics attachment
   *
   * @return string
   */
  public function getIcalData() : string
  {
    return $this->ical_data;
  }

  /**
   * Get the original ical data from when this event was created
   *
   * @return string
   */
  public function getOriginalIcalData() : string
  {
    return $this->original_ical_data;
  }
}
