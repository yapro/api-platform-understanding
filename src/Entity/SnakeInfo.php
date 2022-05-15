<?php

declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Создан с целью проверить работу OneToOne отношения
 *
 * @ORM\Entity
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {"apiRead"},
 *         "skip_null_values": false
 *     },
 *     denormalizationContext={
 *         "groups": {"apiWrite"}
 *     }
 * )
 */
class SnakeInfo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"apiRead", "apiWrite"})
     */
    public ?int $id = null;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"apiRead", "apiWrite"})
     */
    public int $averageLength = 0;

    /**
     * @ORM\OneToOne(targetEntity="Snake", inversedBy="snakeInfo")
     */
    private ?Snake $snake;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSnake(): ?Snake
    {
        return $this->snake;
    }

    public function setSnake(?Snake $snake, bool $updateRelation = true): void
    {
        $this->snake = $snake;
        if ($snake && $updateRelation) {
            $snake->setSnakeInfo($this, false);
        }
    }

    public function getAverageLength(): int
    {
        return $this->averageLength;
    }

    public function setAverageLength(int $averageLength): self
    {
        $this->averageLength = $averageLength;

        return $this;
    }
}
