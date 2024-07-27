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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Helpers\JsonHelper;

/**
 * @Route("/api/tasks", name="task_api")
 */
class TaskController extends AbstractController
{
  private $entityManager;
  private $taskRepository;
  private $serializer;
  private $validator;
  private $jsonHelper;

  public function __construct(EntityManagerInterface $entityManager, TaskRepository $taskRepository, SerializerInterface $serializer, ValidatorInterface $validator, JsonHelper $jsonHelper )
  {
    $this->entityManager = $entityManager;
    $this->taskRepository = $taskRepository;
    $this->serializer = $serializer;
    $this->validator = $validator;
    $this->jsonHelper = $jsonHelper;
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
    $taskData = $this->jsonHelper->getRequestData($request);
    if ($taskData instanceof Response) {
      return $taskData;
    }

    $task = new Task();
    $task->setTitle($taskData['title']);
    $task->setDescription($taskData['description']);
    $task->setPriority($taskData['priority']);
    $task->setNumOrder($this->taskRepository->getNewTaskOrder());

    $errors = $this->validator->validate($task);

    if (count($errors) > 0) {
      $errorsArray = [];
      foreach ($errors as $error) {

        $field = $error->getPropertyPath();
        if (!array_key_exists($field, $errorsArray)) {
          $errorsArray[$field] = $error->getMessage();
        }
      }
      return $this->jsonHelper->createApiResponse(false, $errorsArray, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->entityManager->getConnection()->beginTransaction();

      $this->entityManager->persist($task);
      $this->entityManager->flush();
      $this->entityManager->getConnection()->commit();

      $data = $this->serializer->serialize($task, 'json');
      return $this->jsonHelper->createApiResponse(true, 'Tarea creada con éxito', Response::HTTP_CREATED, ['task' => json_decode($data)]);
    } catch (\Exception $e) {
      $this->entityManager->getConnection()->rollBack();
      return $this->jsonHelper->createApiResponse(false, 'Error creando la tarea', Response::HTTP_INTERNAL_SERVER_ERROR, ['details' => $e->getMessage()]);
    }
  }

  /**
   * @Route("/{id}", name="update", methods={"PUT"})
   */
  public function update(Request $request, $id): Response
  {
    $taskData = $this->jsonHelper->getRequestData($request);
    if ($taskData instanceof Response) {
      return $taskData;
    }

    $task = new Task();
    $task->setTitle($taskData['title']);
    $task->setDescription($taskData['description']);
    $task->setPriority($taskData['priority']);

    $errors = $this->validator->validate($task);

    if (count($errors) > 0) {
      $errorsArray = [];
      foreach ($errors as $error) {

        $field = $error->getPropertyPath();
        if (!array_key_exists($field, $errorsArray)) {
          $errorsArray[$field] = $error->getMessage();
        }
      }
      return $this->jsonHelper->createApiResponse(false, $errorsArray, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->entityManager->getConnection()->beginTransaction();

      $this->entityManager->flush();
      $this->entityManager->getConnection()->commit();

      $data = $this->serializer->serialize($task, 'json');
      return $this->jsonHelper->createApiResponse(true, 'Tarea modificada con éxito', Response::HTTP_OK, ['task' => json_decode($data)]);
    } catch (\Exception $e) {
      $this->entityManager->getConnection()->rollBack();
      return $this->jsonHelper->createApiResponse(false, 'Error modificando la tarea', Response::HTTP_INTERNAL_SERVER_ERROR, ['details' => $e->getMessage()]);
    }
  }

  /**
   * @Route("/update-status/{id}", name="update_status", methods={"PUT"})
   */
  public function updateStatus($id): Response
  {
    $task = $this->taskRepository->find($id);
    if (!$task) {
      return $this->jsonHelper->createApiResponse(false, 'Tarea no encontrada', Response::HTTP_NOT_FOUND);
    }

    try {
      $this->entityManager->getConnection()->beginTransaction();

      $status = Status::STATUS_COMPLETED;
      $task->setStatus($status);

      $this->entityManager->flush();
      $this->entityManager->getConnection()->commit();

      $data = $this->serializer->serialize($task, 'json');
      return $this->jsonHelper->createApiResponse(true, 'Tarea completada con éxito', Response::HTTP_OK, ['task' => json_decode($data)]);
    } catch (\Exception $e) {
      $this->entityManager->getConnection()->rollBack();
      return $this->jsonHelper->createApiResponse(false, 'Error modificando el estatus de la tarea', Response::HTTP_INTERNAL_SERVER_ERROR, ['details' => $e->getMessage()]);
    }
  }

  /**
   * @Route("/delete/{id}", name="delete_task", methods={"PUT"})
   */
  public function deleteTask($id): Response
  {
    $task = $this->taskRepository->find($id);
    if (!$task) {
      return $this->jsonHelper->createApiResponse(false, 'Tarea no encontrada', Response::HTTP_NOT_FOUND);
    }

    try {
      $this->entityManager->getConnection()->beginTransaction();

      $task->setDeleteTask(true);

      $this->entityManager->flush();
      $this->entityManager->getConnection()->commit();

      $data = $this->serializer->serialize($task, 'json');
      return $this->jsonHelper->createApiResponse(true, 'Tarea eliminada con éxito', Response::HTTP_OK, ['task' => json_decode($data)]);
    } catch (\Exception $e) {
      $this->entityManager->getConnection()->rollBack();
      return $this->jsonHelper->createApiResponse(false, 'Error eliminando de la tarea', Response::HTTP_INTERNAL_SERVER_ERROR, ['details' => $e->getMessage()]);
    }
  }

  /**
   * @Route("/update/orders", name="update_orders", methods={"PUT"})
   */
  public function updateOrders(Request $request): Response
  {
    $data = $this->jsonHelper->getRequestData($request);
    if ($data instanceof Response) {
      return $data;
    }

    // Verifica que la estructura del JSON sea válida
    if (!isset($data['order']) || !is_array($data['order'])) {
      return $this->jsonHelper->createApiResponse(false, 'Datos inválidos', Response::HTTP_BAD_REQUEST);
    }

    $tasksData = $data['order'];

    try {
      $this->entityManager->getConnection()->beginTransaction();

      foreach ($tasksData as $item) {
        if (!isset($item['id']) || !isset($item['order'])) {
          // Datos inválidos en el array de tareas
          $this->entityManager->getConnection()->rollBack();
          return $this->jsonHelper->createApiResponse(false, 'Datos de tareas inválidos', Response::HTTP_BAD_REQUEST);
        }

        $task = $this->taskRepository->find($item['id']);
        if (!$task) {
          // Si una tarea no se encuentra, puedes decidir si continuar o abortar
          $this->entityManager->getConnection()->rollBack();
          return $this->jsonHelper->createApiResponse(false, 'Tarea con ID ' . $item['id'] . ' no encontrada', Response::HTTP_NOT_FOUND);
        }

        $task->setNumOrder($item['order']);
      }

      $this->entityManager->flush();
      $this->entityManager->getConnection()->commit();

      return $this->jsonHelper->createApiResponse(true, 'Órdenes actualizadas con éxito', Response::HTTP_OK);
    } catch (\Exception $e) {
      $this->entityManager->getConnection()->rollBack();
      return $this->jsonHelper->createApiResponse(false, 'Error actualizando órdenes', Response::HTTP_INTERNAL_SERVER_ERROR, ['details' => $e->getMessage()]);
    }
  }

  // FUNCIONES EXTRAS

  

  
}
