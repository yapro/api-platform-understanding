<?php
declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {"apiRead"}
 *     },
 *     denormalizationContext={
 *         "groups": {"apiWrite"}
 *     }
 * )
 */
class SnakeType
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"apiRead", "apiWrite"})
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="text")
     * @Groups({"apiRead", "apiWrite"})
     */
    private string $typeName = '';

    /**
     * @ORM\ManyToOne(targetEntity="Snake", inversedBy="snakeTypes")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    private ?Snake $snake = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeName(): string
    {
        return $this->typeName;
    }

    public function setSnake(?Snake $snake, bool $updateRelation = true): void
    {
        $this->snake = $snake;
        if ($snake && $updateRelation) {
            $snake->addSnakeType($this, false);
        }
    }

    public function setTypeName(string $typeName): SnakeType
    {
        $this->typeName = $typeName;
        return $this;
    }
}
