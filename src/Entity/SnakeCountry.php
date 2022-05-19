<?php
declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Создан с целью проверить работу обязательного OneToMany отношения
 *
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
class SnakeCountry
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
    private string $countryName = '';

    /**
     * @ORM\ManyToOne(targetEntity="Snake", inversedBy="snakeCountries")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    private Snake $snake;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSnake(): Snake
    {
        return $this->snake;
    }

    public function setSnake(Snake $snake, bool $updateRelation = true): void
    {
        $this->snake = $snake;
        if ($updateRelation) {
            $snake->addSnakeCountry($this, false);
        }
    }

    public function getCountryName(): string
    {
        return $this->countryName;
    }

    public function setCountryName(string $countryName): self
    {
        $this->countryName = $countryName;
        return $this;
    }
}
