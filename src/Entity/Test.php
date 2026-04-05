<?php

namespace App\Entity;

use App\Repository\TestRepository;
use BcMath\Number;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestRepository::class)]
class Test
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::NUMBER)]
    private ?Number $intger = null;

    #[ORM\Column(type: Types::NUMBER)]
    private ?Number $age = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getIntger(): ?Number
    {
        return $this->intger;
    }

    public function setIntger(Number $intger): static
    {
        $this->intger = $intger;

        return $this;
    }

    public function getAge(): ?Number
    {
        return $this->age;
    }

    public function setAge(Number $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }
}
