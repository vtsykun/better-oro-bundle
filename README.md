##  BetterOroBundle

Table of Contents
-----------------
 - [Jobs logger](#jobs-logger)
 - [Change message priorities](#change-message-priorities)
 - [Message send events](#message-send-events)
 - [Handle jobs exception](#handle-jobs-exception)
 - [Improve cron cleanup](#improve-cron-cleanup)
 - [Better log format](#better-log-format)
 - [Disable container reset extension](#disable-container-reset-extension)


### Jobs logger
The job logger provides the ability to display logs in the UI. Usage: inject logger `okvpn.jobs.logger` into your service

Example:

```php
class SomeProcessor implements MessageProcessorInterface
{

    /** @var LoggerInterface */
    private $logger;

    /** @var JobRunner */
    private $jobRunner;

    public function __construct(LoggerInterface $logger, JobRunner $jobRunner)
    {
        $this->logger = $logger;
        $this->jobRunner = $jobRunner;
    }
    
    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        $result = $this->jobRunner->runDelayed(
            $body['jobId'],
            function () use ($body) {
                try {
                    $this->logger->info('This logs will display in UI on the given root jobs page.')
                } catch (\Throwable $e) {
                    $this->logger->critical(
                        'An error occupies during job execute'
                        ['e' => $e,] // the full stack trace & exception message will display on the job page.
                    );

                    return false;
                }
            }
        );
        
        $this->logger->info('This log will not display, because there isn\'t active job');
        
        return $result ? self::ACK : self::REJECT;
    }
}

```

#### Execute jobs in active transaction

The log persistence happen in a separate transaction from the process that executes it, so information available to end user 
thru UI immediately when its created.


### Change message priorities

You can change the predefined message priority. Example: 

```yml
okvpn_oro:
    default_priorities:
        oro.importexport.cli_import: 3 # topic name OR cron command name OR process definition name(worklfow bundle)
        oro.importexport.pre_cli_import: 3
        oro.importexport.pre_http_import: 3
        oro.importexport.http_import: 3
        oro.importexport.pre_export: 3
        oro.importexport.export: 3
        oro.importexport.post_export: 3
        oro.importexport.send_import_notification: 3
```

| Priority | Map |
|-----|:------:|
| 0 | VERY LOW  |
| 1 | LOW |
| 2 | MEDIUM |
| 3 | HIGH |
| 4 | VERY HIGH |

### Better log format

[![Logs](./Resources/docs/logs.png)](./Resources/docs/logs.png)
