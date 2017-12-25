<?php

namespace Okvpn\Bundle\BetterOroBundle\Logger;

use Oro\Bundle\MessageQueueBundle\Log\Formatter\ConsoleFormatter;
use Oro\Component\MessageQueue\Client\Config;

class DmesgConsoleFormatter extends ConsoleFormatter
{
    const SIMPLE_FORMAT = "%start_tag%%level_name%:%end_tag%%empty%" .
    "<comment>[<bg=default;options=bold>%execute_time%</>%channel%|%processor%]</comment> "
    . "%message% %data% %context%\n";

    protected $terminalDimensions;

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {

        list($colums) = $this->getTerminalDimensions();

        if (isset($record['extra']['message_properties'][Config::PARAMETER_PROCESSOR_NAME])) {
            $record['processor'] = $record['extra']['message_properties'][Config::PARAMETER_PROCESSOR_NAME];
        } else {
            $record['processor'] = 'unknown';
        }

        if (isset($record['context']['time'])) {
            $record['execute_time'] = $record['context']['time'] . 'ms';
            $length = 5 - strlen($record['execute_time']);
            $record['execute_time'] .= $length > 0 ? str_repeat(' ', $length) : '';
        } else {
            $record['execute_time'] = 'na   ';
        }

        $length = 8 - strlen($record['level_name']);
        $record['empty'] = $length > 0 ? str_repeat(' ', $length) : '';

        $output = parent::format($record);
        $clear = preg_replace('/<[\s\w\/;=]+>/i', '', $output);

        $len = strlen($clear);
        $origLen = strlen($output);
        if ($colums !== null && $len > $colums && in_array($record['level_name'], ['DEBUG', 'INFO', 'NOTICE'])) {
            $output = substr($output, 0, ($origLen - $len) + $colums -1) . PHP_EOL;
        }

        return $output;

    }

    private function getTerminalDimensions()
    {
        if ($this->terminalDimensions) {
            return $this->terminalDimensions;
        }

        $this->terminalDimensions = $this->getSttyColumns();

        return $this->terminalDimensions;
    }


    private function getSttyColumns()
    {
        if (!function_exists('proc_open')) {
            return [null, null];
        }
        
        $sttyString = '';
        $descriptorspec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open('stty -a | grep columns', $descriptorspec, $pipes, null, null, ['suppress_errors' => true]);
        if (is_resource($process)) {
            $sttyString = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }

        // extract [w, h] from "rows h; columns w;"
        if (preg_match('/rows.(\d+);.columns.(\d+);/i', $sttyString, $matches)) {
            return  [(int) $matches[2], (int) $matches[1]];
        }
        // extract [w, h] from "; h rows; w columns"
        if (preg_match('/;.(\d+).rows;.(\d+).columns/i', $sttyString, $matches)) {
            return [(int) $matches[2], (int) $matches[1]];
        }

        return [null, null];
    }
}
