<?php
declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {"apiRead"},
 *         "skip_null_values" = false
 *     },
 *     denormalizationContext={
 *         "groups": {"apiWrite"}
 *     }
 * )
 */
class Snake
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"apiRead", "apiWrite"})
     */
    public ?int $id = null;

    /**
     * @ORM\Column
     * @Groups({"apiRead", "apiWrite"})
     */
    private string $title = '';

    /**
     * @ORM\Column(nullable=true)
     * @Groups({"apiRead", "apiWrite"})
     */
    public ?int $length = null;

    /**
     * @var SnakeColor[]|Collection
     *
     * @ORM\OneToMany(targetEntity="SnakeColor", mappedBy="snake", cascade={"persist", "remove"})
     * @ApiSubresource
     * @Groups({"apiRead", "apiWrite"})
     */
	private iterable $snakeColors;

	public function __construct()
	{
		$this->snakeColors = new ArrayCollection();
	}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    /**
     * @return Collection|Review[]
     */
    public function getSnakeColors(): iterable
    {
        return $this->snakeColors;
    }

    public function addSnakeColor(SnakeColor $snakeColor, bool $updateRelation = true): void
    {
        if ($this->snakeColors->contains($snakeColor)) {
            return;
        }
        $this->snakeColors->add($snakeColor);
        if ($updateRelation) {
            $snakeColor->setSnake($this, false);
        }
    }

    public function removeSnakeColor(SnakeColor $snakeColor, bool $updateRelation = true): void
    {
        $this->snakeColors->removeElement($snakeColor);
        if ($updateRelation) {
            $snakeColor->setSnake(null, false);
        }
    }
}
