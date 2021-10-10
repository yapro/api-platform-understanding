<?php
declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * A book.
 *
 * @ORM\Entity
 * @ApiResource
 * @UniqueEntity(fields={"isbn"})
 */
class Book
{
    /**
     * The id of this book.
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public ?int $id = null;

    /**
     * The ISBN of this book (or null if doesn't have one).
     *
     * @ORM\Column(nullable=true)
     */
	public ?string $isbn = null;

    /**
     * The title of this book.
     *
     * @ORM\Column(type="string", unique=true)
     */
	public string $title = '';

    /**
     * The publication date of this book.
     *
     * @ORM\Column(type="datetime_immutable")
     */
	public ?\DateTimeInterface $publicationDate = null;

    /**
     * @var Review[]|Collection Available reviews for this book.
     *
     * @ORM\OneToMany(targetEntity="Review", mappedBy="book", cascade={"persist"})
     */
	public iterable $reviews;

	public function __construct()
	{
		$this->reviews = new ArrayCollection();
	}
}
