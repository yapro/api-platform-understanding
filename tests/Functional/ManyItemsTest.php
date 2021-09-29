<?php

declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use YaPro\DoctrineExt\ReloadDatabaseTrait;
use YaPro\SymfonyHttpClientExt\HttpClientJsonLdExtTrait;
use YaPro\SymfonyHttpTestExt\BaseTestCase;

class ManyItemsTest extends BaseTestCase
{
    use HttpClientJsonLdExtTrait;
    use ReloadDatabaseTrait;

    protected static EntityManagerInterface $entityManager;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testGetBooks()
    {
        self::truncateAllTablesInSqLite();

        $json = '
        {
          "isbn": "string",
          "title": "string",
          "publicationDate": "2021-06-27T05:39:19.583Z"
        }
        ';
        // ApiPlatform not supporting multi insert
        $this->postLd('/api/books', $this->getJsonHelper()->jsonDecode($json, true));
        $this->postLd('/api/books', $this->getJsonHelper()->jsonDecode($json, true));
        $this->postLd('/api/books', $this->getJsonHelper()->jsonDecode($json, true));
        $this->assertResourceIsCreated();

        $this->getLd('/api/books');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonResponse(
            '
        {
          "@context": "/api/contexts/Book",
          "@id": "/api/books",
          "@type": "hydra:Collection",
          "hydra:member": [
            {
              "@id": "/api/books/1",
              "@type": "Book",
              "id": 1,
              "isbn": "string",
              "title": "string",
              "publicationDate": "2021-06-27T05:39:19+00:00",
              "reviews": []
            },
            {
              "@id": "/api/books/2",
              "@type": "Book",
              "id": 2,
              "isbn": "string",
              "title": "string",
              "publicationDate": "2021-06-27T05:39:19+00:00",
              "reviews": []
            }
          ],
          "hydra:totalItems": 3,
          "hydra:view": {
              "@id":         "/api/books?page=1",
              "@type":       "hydra:PartialCollectionView",
              "hydra:first": "/api/books?page=1",
              "hydra:last":  "/api/books?page=2",
              "hydra:next":  "/api/books?page=2"
          }
        }
        '
        );
    }

    /**
     * @depends testGetBooks
     */
    public function testGetBooksAndSpecifyPageAndItemsPerPage()
    {
        $this->getLd('/api/books?page=2&itemsPerPage=1');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonResponse(
            '
        {
          "@context": "/api/contexts/Book",
          "@id": "/api/books",
          "@type": "hydra:Collection",
          "hydra:member": [
            {
              "@id": "/api/books/2",
              "@type": "Book",
              "id": 2,
              "isbn": "string",
              "title": "string",
              "publicationDate": "2021-06-27T05:39:19+00:00",
              "reviews": []
            }
          ],
          "hydra:totalItems": 3,
          "hydra:view": {
              "@id":             "/api/books?itemsPerPage=1&page=2",
              "@type":           "hydra:PartialCollectionView",
              "hydra:first":     "/api/books?itemsPerPage=1&page=1",
              "hydra:last":      "/api/books?itemsPerPage=1&page=3",
              "hydra:previous":  "/api/books?itemsPerPage=1&page=1",
              "hydra:next":      "/api/books?itemsPerPage=1&page=3"
          }
        }
        '
        );
    }

    public function testCreateBookAndTwoReviews(): int
    {
        self::truncateAllTablesInSqLite();

        $json = '
        {
          "isbn": "isbn",
          "title": "title",
          "publicationDate": "2021-06-27T05:39:19.583Z",
          "reviews": [
            {"author": "author1"},
            {"author": "author2"}
          ]
        }
        ';
        $this->postLd('/api/books', $this->getJsonHelper()->jsonDecode($json, true));
        $this->assertResourceIsCreated();

        // НЕОЖИДАННО: при отсутствии метода Book::removeReview() возвращается пустой список "reviews": []
        $this->assertJsonResponse('
        {
          "@context": "/api/contexts/Book",
          "@id": "/api/books/1",
          "@type": "Book",
          "id": 1,
          "isbn": "isbn",
          "title": "title",
          "publicationDate": "2021-06-27T05:39:19+00:00",
            "reviews": [
                {
                  "@id": "/api/reviews/1",
                  "@type": "Review",
                  "id": 1,
                  "author": "author1",
                  "rating": 0
                },
                {
                  "@id": "/api/reviews/2",
                  "@type": "Review",
                  "id": 2,
                  "author": "author2",
                  "rating": 0
                }
            ]
        }
        ');
        return $this->assertResourceIsCreated();
    }
}
