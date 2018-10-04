<?php

namespace Okvpn\Bundle\BetterOroBundle\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Oro\Component\MessageQueue\Client\Config;

class LegacyConsoleFormatter extends LineFormatter
{
    const SIMPLE_FORMAT = "%start_tag%%level_name%:%end_tag% %message% %data% %context%\n";

    /**
     * The variable map for "%data%" placeholder
     *
     * @var array [variable name => the path to the variable value, ...]
     */
    protected $dataMap;

    /**
     * @param array|null  $dataMap    The variable map for "%data%" placeholder
     *                                [variable name => the path to the variable value, ...]
     * @param string|null $format     The format of the message
     * @param string|null $dateFormat The format of the timestamp: one supported by DateTime::format
     */
    public function __construct(array $dataMap = null, $format = null, $dateFormat = null)
    {
        parent::__construct($format, $dateFormat, true);
        $this->dataMap = $dataMap;
        if (null === $this->dataMap) {
            $this->dataMap = [
                'processor' => ['extra', 'processor'],
                'extension' => ['extra', 'extension'],
                'topic'     => ['extra', 'message_properties', Config::PARAMETER_TOPIC_NAME],
                'message'   => ['extra', 'message_body'],
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $data = [];
        foreach ($this->dataMap as $key => $path) {
            $value = $this->getDataValue($record, $path);
            if (null !== $value) {
                $data[$key] = $value;
            }
        }
        $record['data'] = $data;

        if ($record['level'] >= Logger::ERROR) {
            $record['start_tag'] = '<error>';
            $record['end_tag'] = '</error>';
        } elseif ($record['level'] >= Logger::NOTICE) {
            $record['start_tag'] = '<comment>';
            $record['end_tag'] = '</comment>';
        } elseif ($record['level'] >= Logger::INFO) {
            $record['start_tag'] = '<info>';
            $record['end_tag'] = '</info>';
        } else {
            $record['start_tag'] = '';
            $record['end_tag'] = '';
        }

        return parent::format($record);
    }

    /**
     * Gets a value of the specified variable.
     *
     * @param array    $record The log record
     * @param string[] $path   The path to the variable
     *
     * @return mixed A value or NULL if the variable does not exist
     */
    protected function getDataValue(array $record, array $path)
    {
        $lastKey = array_pop($path);
        $current = $record;
        foreach ($path as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }
        if (!isset($current[$lastKey])) {
            return null;
        }

        return $current[$lastKey];
    }

    /**
     * {@inheritdoc}
     */
    protected function toJson($data, $ignoreErrors = false)
    {
        $result = parent::toJson($data, $ignoreErrors);
        if (is_array($data)) {
            $result = $this->formatJsonArrayString($result);
        }

        return $result;
    }

    /**
     * Makes JSON string more pretty for console output.
     *
     * @param string $value
     *
     * @return string
     */
    protected function formatJsonArrayString($value)
    {
        if ('[]' === $value) {
            // do not show empty array
            $value = '';
        } else {
            // replace {"key":"{\"anotherKey\":123}"}} with {"key":{"anotherKey":123}}
            $value = preg_replace_callback(
                '/"{(?P<val>.+?)}"/',
                function ($matches) {
                    return '{' . str_replace('\"', '"', $matches['val']) . '}';
                },
                $value
            );
            // remove extra back slashes
            $value = preg_replace('/\\\\{2,}/', '\\', $value);
        }

        return $value;
    }
}
