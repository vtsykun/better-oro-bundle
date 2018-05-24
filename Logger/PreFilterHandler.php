<?php

namespace Okvpn\Bundle\BetterOroBundle\Logger;

use Oro\Bundle\MessageQueueBundle\Log\Handler\ConsoleHandler;

class PreFilterHandler extends ConsoleHandler
{
    protected $levelChannelMap = [
        'app' => 100,
        'doctrine' => 110,
        'security' => 110,
        'translation' => 350,
        'php' => 210,
        'event' => 110,
    ];

    /**
     * {@inheritdoc}
     */
    public function handle(array $record)
    {
        if (isset($this->levelChannelMap[$record['channel']])
            && $record['level'] < $this->levelChannelMap[$record['channel']]
        ) {
            return false;
        }

        return parent::handle($record);
    }

//    /**
//     * {@inheritdoc}
//     */
//    protected function getDefaultFormatter()
//    {
//        return new DmesgConsoleFormatter(
//            [
//                'extension' => ['extra', 'extension'],
//                'message'   => ['extra', 'message_body'],
//            ]
//        );
//    }
}
