<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 255)]
  #[Assert\NotBlank(message: 'El título es requerido')]
  private ?string $title = null;

  #[ORM\Column(type: Types::TEXT)]
  #[Assert\NotBlank(message: 'La descripción es requerido')]
  private ?string $description = null;

  #[ORM\Column(length: 255, options: ['default' => Status::STATUS_PENDING->value])]
  private string $status = Status::STATUS_PENDING->value;

  #[ORM\Column]
  private ?int $num_order = null;

  #[ORM\Column(length: 255)]
  #[Assert\NotBlank(message: 'La prioridad es requerida')]
  #[Assert\Choice(
    callback: [Priority::class, 'values'],
    message: 'La prioridad no es válida. Los valores permitidos son alta, media o baja.'
  )]
  private ?string $priority = null;

  #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
  private bool $delete_task = false;

  #[ORM\Column(type: Types::DATETIME_MUTABLE)]
  private ?\DateTimeInterface $created_at = null;

  #[ORM\Column(type: Types::DATETIME_MUTABLE)]
  private ?\DateTimeInterface $updated_at = null;

  public function __construct()
  {
    $this->created_at = new \DateTime();
    $this->updated_at = new \DateTime();
  }

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getTitle(): ?string
  {
    return $this->title;
  }

  public function setTitle(string $title): static
  {
    $this->title = $title;

    return $this;
  }

  public function getDescription(): ?string
  {
    return $this->description;
  }

  public function setDescription(string $description): static
  {
    $this->description = $description;

    return $this;
  }

  public function getStatus(): Status
  {
    return Status::from($this->status);
  }

  public function setStatus(Status $status): static
  {
    $this->status = $status->value;
    return $this;
  }

  public function getNumOrder(): ?int
  {
    return $this->num_order;
  }

  public function setNumOrder(int $num_order): static
  {
    $this->num_order = $num_order;

    return $this;
  }

  public function getPriority(): ?Priority
  {
    return Priority::from($this->priority);
  }

  public function setPriority(?string $priority): static
  {
    $this->priority = $priority;

    return $this;
  }

  public function isDeleteTask(): bool
  {
    return $this->delete_task;
  }

  public function setDeleteTask(bool $delete_task): static
  {
    $this->delete_task = $delete_task;
    return $this;
  }

  public function getCreatedAt(): ?\DateTimeInterface
  {
    return $this->created_at;
  }

  public function setCreatedAt(\DateTimeInterface $created_at): static
  {
    $this->created_at = $created_at;

    return $this;
  }

  public function getUpdatedAt(): ?\DateTimeInterface
  {
    return $this->updated_at;
  }

  public function setUpdatedAt(\DateTimeInterface $updated_at): static
  {
    $this->updated_at = $updated_at;

    return $this;
  }
}
