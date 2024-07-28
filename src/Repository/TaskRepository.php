<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
  private $entityManager;
  public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
  {
    parent::__construct($registry, Task::class);
    $this->entityManager = $entityManager;
  }

  /**
   * @param bool|null $isDeleted
   * @param string|null $priority
   * @return Task[] Returns an array of Task objects
   */
  public function findByCriteria(?bool $isDeleted, ?string $priority)
  {
    $qb = $this->createQueryBuilder('t');

    if ($isDeleted !== null) {
      if ($isDeleted) {
        $qb->andWhere('t.delete_at IS NOT NULL');
      } else {
        $qb->andWhere('t.delete_at IS NULL');
      }
    }

    if ($priority !== null) {
      $qb->andWhere('t.priority = :priority')
        ->setParameter('priority', $priority);
    }

    return $qb->getQuery()->getResult();
  }

  /**
   * @return integer Returns an array of Task objects
   */
  public function getNewTaskOrder(): int
  {
    $maxOrder = $this->entityManager->createQueryBuilder()
      ->select('MAX(t.num_order)')
      ->from(Task::class, 't')
      ->getQuery()
      ->getSingleScalarResult();

    return $maxOrder !== null ? $maxOrder + 1 : 1;
  }
}
