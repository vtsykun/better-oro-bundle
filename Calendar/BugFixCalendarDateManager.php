<?php

namespace Okvpn\Bundle\BetterOroBundle\Calendar;

use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\ReportBundle\Entity\Manager\CalendarDateManager;

/**
 * Fix appear of duplicates
 */
class BugFixCalendarDateManager extends CalendarDateManager
{
    /**
     * {@inheritdoc}
     */
    public function handleCalendarDates($append = false)
    {
        $period = $this->getDatesFromInterval($append);
        $manager = $this->doctrineHelper->getEntityManager(CalendarDate::class);
        $calendarRepository = $manager->getRepository('OroReportBundle:CalendarDate');

        foreach ($period as $day) {
            // start bug fix
            if ($calendarRepository->getDate($day) !== null) {
                continue;
            }
            // end bug fix

            $calendarDate = new CalendarDate();
            $calendarDate->setDate($day);
            $manager->persist($calendarDate);
        }

        $manager->flush();
    }
}
