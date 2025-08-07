<?php

namespace Soranoiseki\Core\Traits;

use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponse
{
    public function parseGivenData($data = [], $statusCode = 200, $headers = []): array
    {
        $responseStructure = [
            'success' => $data['success'],
            'message' => $data['message'] ?? null,
            'result'  => $data['result']  ?? null,
        ];

        if (isset($data['errors'])) {
            $responseStructure['errors'] = $data['errors'];
        }

        if (isset($data['status'])) {
            $statusCode = $data['status'];
        }

        if (isset($data['exception']) && ($data['exception'] instanceof Error || $data['exception'] instanceof Exception)) {
            if (config('app.env') !== 'production') {
                $responseStructure['exception'] = [
                    'message' => $data['exception']->getMessage(),
                    'file'    => $data['exception']->getFile(),
                    'line'    => $data['exception']->getLine(),
                    'code'    => $data['exception']->getCode(),
                    'trace'   => $data['exception']->getTrace(),
                ];
            }

            if ($statusCode === 200) {
                $statusCode = 500;
            }
        }

        if ($data['success'] === false) {
            if (isset($data['error_code'])) {
                $responseStructure['error_code'] = $data['error_code'];
            } else {
                $responseStructure['error_code'] = 1;
            }
        }

        return ['content' => $responseStructure, 'statusCode' => $statusCode, 'headers' => $headers];
    }

    protected function apiResponseTest($data = [], $statusCode = 200, $headers = [])
    {
        $result = $this->parseGivenData($data, $statusCode, $headers);

        return response()->json(
            $result['content'],
            $result['statusCode'],
            $result['headers']
        );
    }

    protected function respondWithResource(JsonResource $resource, $message = null, $statusCode = 200, $headers = []): \Illuminate\Http\JsonResponse
    {
        return $this->apiResponseTest(
            [
                'success' => true,
                'result'  => [
                    'data' => $resource
                ],
                'message' => $message,
            ],
            $statusCode,
            $headers
        );
    }

    protected function respondWithResourceCollection(ResourceCollection $resourceCollection, $message = null, $statusCode = 200, $headers = [])
    {
        return $this->apiResponseTest(
            [
                'success' => true,
                'result'  => $resourceCollection->response()->getData(),
            ],
            $statusCode,
            $headers
        );
    }

    protected function respondEmpty($message = null, $statusCode = 200, $headers = [])
    {
        return $this->apiResponseTest(
            [
                'success' => true,
                'result'  => [
                    'data' => []
                ],
                'message' => $message,
            ],
            $statusCode,
            $headers
        );
    }

    protected function respondWithData($data = [], $message = null, $statusCode = 200, $headers = [])
    {
        return $this->apiResponseTest(
            [
                'success' => true,
                'result'  => [
                    'data' => $data
                ],
                'message' => $message,
            ],
            $statusCode,
            $headers
        );
    }
}
