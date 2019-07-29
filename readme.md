## Overview

Laravel documentation recommends to use supervisor for queue workers and *IX cron for scheduled tasks. However, when deploying your application to AWS Elastic Beanstalk, neither option is available.

This package helps you run your Laravel (or Lumen) jobs in AWS worker environments.

## Dependencies

* PHP >= 5.5

## Scheduled tasks

You remember how Laravel documentation advised you to invoke the task scheduler? Right, by running ```php artisan schedule:run``` on regular basis, and to do that we had to add an entry to our cron file:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

AWS doesn't allow you to run *IX commands or to add cron tasks directly. Instead, you have to make regular HTTP (POST, to be precise) requests to your worker endpoint.

Add cron.yaml to the root folder of your application (this can be a part of your repo or you could add this file right before deploying to EB - the important thing is that this file is present at the time of deployment):

```yaml
version: 1
cron:
 - name: "schedule"
   url: "/worker/schedule"
   schedule: "* * * * *"
```

From now on, AWS will do POST /worker/schedule to your endpoint every minute - kind of the same effect we achieved when editing a UNIX cron file. The important difference here is that the worker environment still has to run a web process in order to execute scheduled tasks.

Your scheduled tasks should be defined in ```App\Console\Kernel::class``` - just where they normally live in Laravel, eg.:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('inspire')
              ->everyMinute();
}
```

## Queued jobs: SQS

Normally Laravel has to poll SQS for new messages, but in case of AWS Elastic Beanstalk messages will come to us – inside of POST requests from the AWS daemon. 

Therefore, we will create jobs manually based on SQS payload that arrived, and pass that job to the framework's default worker. From this point, the job will be processed the way it's normally processed in Laravel. If it's processed successfully,
our controller will return a 200 HTTP status and AWS daemon will delete the job from the queue. Again, we don't need to poll for jobs and we don't need to delete jobs - that's done by AWS in this case.

If you dispatch jobs from another instance of Laravel or if you are following Laravel's payload format ```{"job":"","data":""}``` you should be okay to go.

## Configuring the queue

You have to tell Laravel about this queue. First set your queue driver to SQS in ```.env``` file:

```
QUEUE_DRIVER=sqs
```

Then go to ```config/queue.php``` and copy/paste details from AWS console:

```php
        ...
        'sqs' => [
            'driver' => 'sqs',
            'key' => 'your-public-key',
            'secret' => 'your-secret-key',
            'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
            'queue' => 'your-queue-name',
            'region' => 'us-east-1',
        ],
        ...
```

To generate key and secret go to Identity and Access Management in the AWS console. It's better to create a separate user that ONLY has access to SQS.

## Installation via Composer

To install simply run:

```
composer require vijayd28/laravel_sqs
```

Or add it to `composer.json` manually:

```json
{
    "require": {
        "vijayd28/laravel_sqs": "~1.0"
    }
}
```

### Usage in Laravel 5


Environment variable ```REGISTER_WORKER_ROUTES``` is used to trigger binding of the two routes above. If you run the same application in both web and worker environments,
don't forget to set ```REGISTER_WORKER_ROUTES``` to ```false``` in your web environment. You don't want your regular users to be able to invoke scheduler or queue worker.

This variable is set to ```true``` by default at this moment.

So that's it - if you (or AWS) hits ```/worker/queue```, Laravel will process one queue item (supplied in the POST). And if you hit ```/worker/schedule```, we will run the scheduler (it's the same as to run ```php artisan schedule:run``` in shell).
