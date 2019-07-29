<?php

namespace Vijayd28\LaravelSQS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Vijayd28\LaravelSQS\Jobs\AwsJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory;
use Vijayd28\LaravelSQS\Exceptions\MalformedRequestException;


class QueueController
{

    /**
     * $response variable
     *
     * @var ResponseFactory
     */
    private $response;

    /**
     * Constructor function
     *
     * @param ResponseFactory $response
     */
    public function __construct(ResponseFactory $response)
    {
        $this->response = $response;
    }

    /**
     * This method is nearly identical to ScheduleRunCommand shipped with Laravel, but since we are not interested
     * in console output we couldn't reuse it
     *
     * @return array
     */
    public function schedule()
    {
        return $this->jsonResponse(['command_status' => Artisan::call('schedule:run')]);
    }

    /**
     * @param Request $request
     * @param Worker $worker
     * @param Container $container
     * @return Response
     */
    public function queue(Request $request, Worker $worker, Container $container)
    {

        try {
            $body = $this->validateBody($request, $container);

            $job = new AwsJob($container, $request->header('X-Aws-Sqsd-Queue'), [
                'Body' => $body,
                'MessageId' => $request->header('X-Aws-Sqsd-Msgid'),
                'ReceiptHandle' => false,
                'Attributes' => [
                    'ApproximateReceiveCount' => $request->header('X-Aws-Sqsd-Receive-Count')
                ]
            ]);

            $bodyData = json_decode($body);

            $workerOptions = new WorkerOptions(
                data_get($bodyData, 'data.delay', 0),
                128,
                0,
                3,
                data_get($bodyData, 'data.maxTries', 0),
                false,
                false
            );

            $worker->process($request->header('X-Aws-Sqsd-Queue'), $job, $workerOptions);

            return $this->jsonResponse(['Processed ' . $job->getJobId()]);
        } catch (\Exception $e) {
            throw new MalformedRequestException('Something wrong with job data: ' . $e->getMessage());
        }
    }

    /**
     * @param Request $request
     * @throws MalformedRequestException
     */
    private function validateHeaders(Request $request)
    {
        foreach (config('sqs.allowed_aws_headers', []) as $header) {
            if (! $this->hasHeader($request, $header)) {
                throw new MalformedRequestException('Missing AWS header: ' . $header);
            }
        }
    }

    /**
     * @param Request $request
     * @param $header
     * @return bool
     */
    private function hasHeader(Request $request, $header)
    {
        if (method_exists($request, 'hasHeader')) {
            return $request->hasHeader($header);
        }
        return $request->header($header, false);
    }

    /**
     * @param Request $request
     * @param Container $laravel
     * @return string
     * @throws MalformedRequestException
     */
    private function validateBody(Request $request, Container $laravel)
    {
        if (empty($request->getContent())) {
            throw new MalformedRequestException('Empty request body');
        }
        $job = json_decode($request->getContent(), true);
        
        if ($job === null) {
            throw new MalformedRequestException('Unable to decode request JSON');
        } 

        if (isset($job['job']) && isset($job['data'])) {
            return $request->getContent();
        }

        throw new MalformedRequestException('Unable to decode request JSON');
    }
    
    /**
     * @param array $data
     * @param int $code
     * @return Response
     */
    private function jsonResponse($data = [], $code = 200)
    {
        return $this->response->json($data, $code);
    }
}