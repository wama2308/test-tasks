<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Status;
use App\Entity\Priority;


use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * @Route("/api/tasks", name="task_api")
 */
class TaskController extends AbstractController
{
  private $entityManager;
  private $taskRepository;
  private $serializer;

  public function __construct(EntityManagerInterface $entityManager, TaskRepository $taskRepository, SerializerInterface $serializer)
  {
    $this->entityManager = $entityManager;
    $this->taskRepository = $taskRepository;
    $this->serializer = $serializer;
  }

  /**
   * @Route("/", name="index", methods={"GET"})
   */
  public function index(): Response
  {
    $tasks = $this->taskRepository->findAll();

    $jsonTasks = $this->serializer->serialize($tasks, 'json');

    $responseArray = [
      'data' => [
        'tasks' => json_decode($jsonTasks)
      ]
    ];

    $jsonResponse = $this->serializer->serialize($responseArray, 'json');

    return new Response($jsonResponse, Response::HTTP_OK, ['Content-Type' => 'application/json']);
  }

  /**
   * @Route("/", name="create", methods={"POST"})
   */
  public function create(Request $request): Response
  {
    $taskData = $this->getRequestData($request);
    if ($taskData instanceof Response) {
      return $taskData;
    }

    $validationErrors = $this->validateTaskData($taskData);
    if (!empty($validationErrors)) {
      return $this->createApiResponse(false, $validationErrors, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->entityManager->getConnection()->beginTransaction();

      $newOrder = $this->getNewTaskOrder();
      $task = new Task();
      $task->setTitle($taskData['title']);
      $task->setDescription($taskData['description']);
      $task->setNumOrder($newOrder);
      $task->setPriority(Priority::from($taskData['priority']));

      $this->entityManager->persist($task);
      $this->entityManager->flush();
      $this->entityManager->getConnection()->commit();

      $data = $this->serializer->serialize($task, 'json');
      return $this->createApiResponse(true, 'Tarea creada con éxito', Response::HTTP_CREATED, ['task' => json_decode($data)]);
    } catch (\Exception $e) {
      $this->entityManager->getConnection()->rollBack();
      return $this->createApiResponse(false, 'Error creando la tarea', Response::HTTP_INTERNAL_SERVER_ERROR, ['details' => $e->getMessage()]);
    }
  }

  /**
   * @Route("/{id}", name="update", methods={"PUT"})
   */
  public function update(Request $request, $id): Response
  {
    $task = $this->taskRepository->find($id);
    if (!$task) {
      return $this->createApiResponse(false, 'Tarea no encontrada', Response::HTTP_NOT_FOUND);
    }

    $taskData = $this->getRequestData($request);
    if ($taskData instanceof Response) {
      return $taskData;
    }

    $validationErrors = $this->validateTaskData($taskData);
    if (!empty($validationErrors)) {
      return $this->createApiResponse(false, $validationErrors, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->entityManager->getConnection()->beginTransaction();

      $task->setTitle($taskData['title']);
      $task->setDescription($taskData['description']);
      $task->setPriority(Priority::from($taskData['priority']));

      $this->entityManager->flush();
      $this->entityManager->getConnection()->commit();

      $data = $this->serializer->serialize($task, 'json');
      return $this->createApiResponse(true, 'Tarea modificada con éxito', Response::HTTP_OK, ['task' => json_decode($data)]);
    } catch (\Exception $e) {
      $this->entityManager->getConnection()->rollBack();
      return $this->createApiResponse(false, 'Error modificando la tarea', Response::HTTP_INTERNAL_SERVER_ERROR, ['details' => $e->getMessage()]);
    }
  }

  /**
   * @Route("/update-status/{id}", name="update_status", methods={"PUT"})
   */
  public function updateStatus($id): Response
  {
    $task = $this->taskRepository->find($id);
    if (!$task) {
      return $this->createApiResponse(false, 'Tarea no encontrada', Response::HTTP_NOT_FOUND);
    }

    try {
      $this->entityManager->getConnection()->beginTransaction();

      $status = Status::STATUS_COMPLETED;
      $task->setStatus($status);

      $this->entityManager->flush();
      $this->entityManager->getConnection()->commit();

      $data = $this->serializer->serialize($task, 'json');
      return $this->createApiResponse(true, 'Tarea completada con éxito', Response::HTTP_OK, ['task' => json_decode($data)]);
    } catch (\Exception $e) {
      $this->entityManager->getConnection()->rollBack();
      return $this->createApiResponse(false, 'Error modificando el estatus de la tarea', Response::HTTP_INTERNAL_SERVER_ERROR, ['details' => $e->getMessage()]);
    }
  }

  /**
   * @Route("/delete/{id}", name="delete_task", methods={"PUT"})
   */
  public function deleteTask($id): Response
  {
    $task = $this->taskRepository->find($id);
    if (!$task) {
      return $this->createApiResponse(false, 'Tarea no encontrada', Response::HTTP_NOT_FOUND);
    }

    try {
      $this->entityManager->getConnection()->beginTransaction();

      $task->setDeleteTask(true);

      $this->entityManager->flush();
      $this->entityManager->getConnection()->commit();

      $data = $this->serializer->serialize($task, 'json');
      return $this->createApiResponse(true, 'Tarea eliminada con éxito', Response::HTTP_OK, ['task' => json_decode($data)]);
    } catch (\Exception $e) {
      $this->entityManager->getConnection()->rollBack();
      return $this->createApiResponse(false, 'Error eliminando de la tarea', Response::HTTP_INTERNAL_SERVER_ERROR, ['details' => $e->getMessage()]);
    }
  }

  /**
   * @Route("/update/orders", name="update_orders", methods={"PUT"})
   */
  public function updateOrders(Request $request): Response
  {
    $data = $this->getRequestData($request);
    if ($data instanceof Response) {
      return $data;
    }

    // Verifica que la estructura del JSON sea válida
    if (!isset($data['order']) || !is_array($data['order'])) {
      return $this->createApiResponse(false, 'Datos inválidos', Response::HTTP_BAD_REQUEST);
    }

    $tasksData = $data['order'];

    try {
      $this->entityManager->getConnection()->beginTransaction();

      foreach ($tasksData as $item) {
        if (!isset($item['id']) || !isset($item['order'])) {
          // Datos inválidos en el array de tareas
          $this->entityManager->getConnection()->rollBack();
          return $this->createApiResponse(false, 'Datos de tareas inválidos', Response::HTTP_BAD_REQUEST);
        }

        $task = $this->taskRepository->find($item['id']);
        if (!$task) {
          // Si una tarea no se encuentra, puedes decidir si continuar o abortar
          $this->entityManager->getConnection()->rollBack();
          return $this->createApiResponse(false, 'Tarea con ID ' . $item['id'] . ' no encontrada', Response::HTTP_NOT_FOUND);
        }

        $task->setNumOrder($item['order']);
      }

      $this->entityManager->flush();
      $this->entityManager->getConnection()->commit();

      return $this->createApiResponse(true, 'Órdenes actualizadas con éxito', Response::HTTP_OK);
    } catch (\Exception $e) {
      $this->entityManager->getConnection()->rollBack();
      return $this->createApiResponse(false, 'Error actualizando órdenes', Response::HTTP_INTERNAL_SERVER_ERROR, ['details' => $e->getMessage()]);
    }
  }

  // FUNCIONES EXTRAS

  private function validateTaskData(array $taskData): array
  {
    $errors = [];

    if (empty($taskData['title'])) {
      $errors['title'] = 'El título es requerido';
    }
    if (empty($taskData['description'])) {
      $errors['description'] = 'La descripción es requerida';
    }
    if (empty($taskData['priority'])) {
      $errors['priority'] = 'La prioridad es requerida';
    }

    // Verifica la prioridad
    $priority = $taskData['priority'] ?? Priority::PRIORITY_HIGHT->value;
    $validPriorities = [
      Priority::PRIORITY_HIGHT->value,
      Priority::PRIORITY_MEDIUM->value,
      Priority::PRIORITY_LOW->value
    ];

    if (!in_array($priority, $validPriorities, true)) {
      $errors['priority'] = 'La prioridad no es válida. Los valores permitidos son alta, media o baja.';
    }
    return $errors;
  }

  private function getNewTaskOrder(): int
  {
    $maxOrder = $this->entityManager->createQueryBuilder()
      ->select('MAX(t.num_order)')
      ->from(Task::class, 't')
      ->getQuery()
      ->getSingleScalarResult();

    return $maxOrder !== null ? $maxOrder + 1 : 1;
  }

  private function createApiResponse(bool $success, $message, int $statusCode, array $data = []): JsonResponse
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

  private function getRequestData(Request $request): array|Response
  {
    $requestData = $request->getContent();
    $taskData = json_decode($requestData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return $this->createApiResponse(false, 'JSON inválido', Response::HTTP_BAD_REQUEST);
    }

    return $taskData;
  }
}
