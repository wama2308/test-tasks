<?php
namespace App\Helpers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonHelper
{
  public function createApiResponse(bool $success, string | array $message, int $statusCode, array $data = []): JsonResponse
  {
    $response = [
      'success' => $success,
      'message' => $message,
    ];

    if (!empty($data)) {
      $response['data'] = $data;
    }

    return new JsonResponse($response, $statusCode);
  }

  public function getRequestData(Request $request): array|Response
  {
    $requestData = $request->getContent();
    $taskData = json_decode($requestData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return $this->createApiResponse(false, 'JSON inválido', Response::HTTP_BAD_REQUEST);
    }

    return $taskData;
  }
}
