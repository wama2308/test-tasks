<?php


namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response; // Importar la clase Response


class ExceptionListener
{
  private $errorController;

  public function __construct($errorController)
  {
    $this->errorController = $errorController;
  }

  public function onKernelException(ExceptionEvent $event)
  {
    $exception = $event->getThrowable();
    $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

    // Delegate the handling to the error controller
    $response = $this->errorController->error($statusCode);
    $event->setResponse($response);
  }
}
