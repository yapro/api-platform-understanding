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
 * A book.
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
class Book
{
    /**
     * The id of this book.
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"apiRead", "apiWrite"})
     */
    public ?int $id = null;

    /**
     * The ISBN of this book (or null if doesn't have one).
     *
     * @ORM\Column(nullable=true)
     * @Groups({"apiRead", "apiWrite"})
     */
	public ?string $isbn = null;

    /**
     * The title of this book.
     *
     * @ORM\Column
     * @Groups({"apiRead", "apiWrite"})
     */
	public string $title = '';

    /**
     * The publication date of this book.
     *
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"apiRead", "apiWrite"})
     */
	public ?\DateTimeInterface $publicationDate = null;

    /**
     * @var Review[]|Collection Available reviews for this book.
     *
     * @ORM\OneToMany(targetEntity="Review", mappedBy="book", cascade={"persist", "remove"})
     * @ApiSubresource
     * @Groups({"apiRead", "apiWrite"})
     */
	public iterable $reviews;

	public function __construct()
	{
		$this->reviews = new ArrayCollection();
	}

    /**
     * @return Collection|Review[]
     */
    public function getReviews(): iterable
    {
        return $this->reviews;
    }

    public function addReview(Review $review, bool $updateRelation = true): void
    {
        if ($this->reviews->contains($review)) {
            return;
        }
        $this->reviews->add($review);
        if ($updateRelation) {
            $review->setBook($this, false);
        }
    }

    public function removeReview(Review $review, bool $updateRelation = true): void
    {
        $this->reviews->removeElement($review);
        if ($updateRelation) {
            $review->setBook(null, false);
        }
    }
}
