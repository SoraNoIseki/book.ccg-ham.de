<?php

namespace Soranoiseki\Core\Traits;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

/**
 * Trait ApiResponser
 *
 * @package App\Traits
 */
trait ApiResponser
{
    /**
     * @param JsonResource $resource
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respondSuccessWithResource(JsonResource $resource, array $data = [], int $statusCode = 200, array $headers = []): JsonResponse
    {
        $responseData = [
            'success' => true,
            'data' => $resource->response()->getData()->data,
            ...$data
        ];

        return $this->apiResponse($responseData, $statusCode, $headers);
    }

    /**
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respondSuccess(array $data = [], int $statusCode = 200, array $headers = []): JsonResponse
    {
        $responseData = [
            'success' => true,
            'data' => $data
        ];
        return $this->apiResponse($responseData, $statusCode, $headers);
    }

    /**
     * @param ResourceCollection $resourceCollection
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respondSuccessWithResourceCollection(ResourceCollection $resourceCollection, int $statusCode = 200, array $headers = []): JsonResponse
    {
        $responseData = [
            'success' => true,
            'data' => $resourceCollection->response()->getData()->data
        ];

        return $this->apiResponse($responseData, $statusCode, $headers);
    }

    /**
     * @param Collection $resourceCollection
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respondSuccessWithCollection(Collection $resourceCollection, int $statusCode = 200, array $headers = []): JsonResponse
    {
        $responseData = [
            'success' => true,
            'data' => $resourceCollection
        ];

        return $this->apiResponse($responseData, $statusCode, $headers);
    }

    /**
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respondFail(array $data = [], int $statusCode = 400, array $headers = []): JsonResponse
    {
        $responseData = [
            'success' => false,
            'data' => $data
        ];
        return $this->apiResponse($responseData, $statusCode, $headers);
    }

    /**
     * @param MessageBag $messageBag
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respondFailWithMessageBag(MessageBag $messageBag, int $statusCode = 400, array $headers = []): JsonResponse
    {
        $responseData = [
            'success' => false,
            'data' => $messageBag->toArray()
        ];

        return $this->apiResponse($responseData, $statusCode, $headers);
    }

    /**
     * @param string $message
     * @param int|null $code
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respondError(string $message, $code = null, array $data = [], int $statusCode = 422, array $headers = []): JsonResponse
    {
        $responseData = [
            'success' => false,
            'status' => 'error',
            'message' => $message ?? 'There was an internal error, Pls try again later.',
            'code' => $code ?? 9999,
            'data' => $data
        ];

        return $this->apiResponse($responseData, $statusCode, $headers);
    }

    /**
     * @param \Exception $exception
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respondErrorWithException(\Exception $exception, $data = [], int $statusCode = 422, array $headers = []): JsonResponse
    {
        $responseData = [
            'exception' => get_class($exception),
            'file' => sprintf('%s@%s', $exception->getFile(), $exception->getLine()),
            'trace' => $exception->getTrace()
        ];

        if (count($data) >= 1) {
            $responseData = array_merge($responseData, $data);
        }
        return $this->respondError($exception->getMessage(), $exception->getCode(), $responseData, $statusCode, $headers);
    }

    /**
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    private function apiResponse(array $data = [], int $statusCode = 200, array $headers = []): JsonResponse
    {
        return response()->json(
            $data,
            $statusCode,
            $headers
        );
    }
}
