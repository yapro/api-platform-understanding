<?php

declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A review of a book.
 *
 * @ORM\Entity
 * @ApiResource
 */
class Review
{
    /**
     * The id of this review.
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public ?int $id = null;

    /**
     * The author of the review.
     *
     * @ORM\Column(type="text")
     */
    public string $author = '';

    /**
     * The rating of this review (between 0 and 5).
     *
     * @ORM\Column(type="smallint")
     */
    public int $rating = 0;

    /**
     * The date of publication of this review.
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    public ?\DateTimeInterface $publicationDate = null;

    /**
     * The book this review is about.
     *
     * @ORM\ManyToOne(targetEntity="Book", inversedBy="reviews")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    public ?Book $book = null;
}
