<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ErrorController extends AbstractController
{
  /**
   * @Route("/error/{code}", name="error", requirements={"code"="\d+"}, methods={"GET"})
   */
  public function error($code): JsonResponse
  {
    $message = "An error occurred.";
    if ($code == 404) {
      $message = "Resource not found.";
    } elseif ($code == 500) {
      $message = "Internal server error.";
    }

    return new JsonResponse([
      'success' => false,
      'message' => $message,
    ], $code);
  }
}
