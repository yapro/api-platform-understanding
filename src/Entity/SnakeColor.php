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
 *     },
 *     attributes={"pagination_maximum_items_per_page"=1000}
 * )
 */
class SnakeColor
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
    private string $color = '';

    /**
     * @ORM\ManyToOne(targetEntity="Snake", inversedBy="snakeColors", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    private ?Snake $snake = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setSnake(?Snake $snake, bool $updateRelation = true): void
    {
        $this->snake = $snake;
        if ($snake && $updateRelation) {
            $snake->addSnakeColor($this, false);
        }
    }

    public function setColor(string $color): SnakeColor
    {
        $this->color = $color;
        return $this;
    }
}
