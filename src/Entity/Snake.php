<?php

declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
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
     * @ORM\OneToOne(targetEntity="SnakeInfo", mappedBy="snake", cascade={"persist"})
     * @Groups({"apiRead", "apiWrite"})
     */
    private ?SnakeInfo $snakeInfo = null;

    /**
     * @var SnakeColor[]|Collection
     *
     * @ORM\OneToMany(targetEntity="SnakeColor", mappedBy="snake", cascade={"persist"})
     * @ApiSubresource
     * @Groups({"apiRead", "apiWrite"})
     */
    private iterable $snakeColors;

    /**
     * @var SnakeType[]|Collection
     *
     * @ORM\OneToMany(targetEntity="SnakeType", mappedBy="snake", cascade={"persist"})
     * @ApiSubresource
     * @Groups({"apiRead", "apiWrite"})
     */
    private iterable $snakeTypes;

    /**
     * @var SnakeCountry[]|Collection
     *
     * @ORM\OneToMany(targetEntity="SnakeCountry", mappedBy="snake", cascade={"persist"})
     * @Groups({"apiRead", "apiWrite"})
     */
    private iterable $snakeCountries;

    public function __construct()
    {
        $this->snakeColors = new ArrayCollection();
        $this->snakeTypes = new ArrayCollection();
        $this->snakeCountries = new ArrayCollection();
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

    // без гетера не ApiPlatform не отдает property snakeInfo
    public function getSnakeInfo(): ?SnakeInfo
    {
        return $this->snakeInfo;
    }

    public function setSnakeInfo(?SnakeInfo $snakeInfo, bool $updateRelation = true): void
    {
        $this->snakeInfo = $snakeInfo;
        if ($snakeInfo && $updateRelation) {
            $snakeInfo->setSnake($this, false);
        }
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

    /**
     * @return Collection|SnakeType[]
     */
    public function getSnakeTypes()
    {
        return $this->snakeTypes;
    }

    public function addSnakeType(SnakeType $snakeType, bool $updateRelation = true): void
    {
        if ($this->snakeTypes->contains($snakeType)) {
            return;
        }
        $this->snakeTypes->add($snakeType);
        if ($updateRelation) {
            $snakeType->setSnake($this, false);
        }
    }

    /**
     * @return Collection|SnakeCountry[]
     */
    public function getSnakeCountries()
    {
        return $this->snakeCountries;
    }

    public function removeSnakeType(SnakeType $snakeType, bool $updateRelation = true): void
    {
        $this->snakeTypes->removeElement($snakeType);
        if ($updateRelation) {
            $snakeType->setSnake(null, false);
        }
    }

    public function addSnakeCountry(SnakeCountry $snakeCountry, bool $updateRelation = true)
    {
        if ($this->snakeCountries->contains($snakeCountry)) {
            return;
        }
        $this->snakeCountries->add($snakeCountry);
        if ($updateRelation) {
            $snakeCountry->setSnake($this, false);
        }
    }

    public function removeSnakeCountry(SnakeCountry $snakeCountry): void
    {
        $this->snakeCountries->removeElement($snakeCountry);
    }
}
