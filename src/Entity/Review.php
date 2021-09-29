<?php
declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * A review of a book.
 *
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
class Review
{
    /**
     * The id of this review.
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"apiRead", "apiWrite"})
     */
    public ?int $id = null;

    /**
     * The author of the review.
     *
     * @ORM\Column(type="text")
     * @Groups({"apiRead", "apiWrite"})
     */
	public string $author = '';

	/**
	 * The rating of this review (between 0 and 5).
	 *
	 * @ORM\Column(type="smallint")
     * @Groups({"apiRead", "apiWrite"})
	 */
	public int $rating = 0;

    /**
     * The date of publication of this review.
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @Groups({"apiRead", "apiWrite"})
     */
	public ?\DateTimeInterface $publicationDate = null;

    /**
     * The book this review is about.
     *
     * @ORM\ManyToOne(targetEntity="Book", inversedBy="reviews", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
	public ?Book $book = null;

    public function setBook(?Book $book, bool $updateRelation = true): void
    {
        $this->book = $book;
        if ($book && $updateRelation) {
            $book->addReview($this, false);
        }
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

}
