<?php
declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use YaPro\DoctrineExt\ReloadDatabaseTrait;
use YaPro\SymfonyHttpClientExt\HttpClientJsonLdExtTrait;
use YaPro\SymfonyHttpTestExt\BaseTestCase;

class OneItemTest extends BaseTestCase
{
    use HttpClientJsonLdExtTrait;
    use ReloadDatabaseTrait;

    protected static EntityManagerInterface $entityManager;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateBook(): int
    {
        self::truncateAllTablesInSqLite();

        $json = '
        {
          "isbn": "string",
          "title": "string",
          "publicationDate": "2021-06-27T05:39:19.583Z",
          "nonExistentField": "данное поле будет проигнорировано т.к. не существует в сущности"
        }
        ';
        $crawler = $this->postLd('/api/books', $this->getJsonHelper()->jsonDecode($json, true));
        $this->assertJsonResponse('
        {
          "@context": "/api/contexts/Book",
          "@id": "/api/books/1",
          "@type": "Book",
          "id": 1,
          "isbn": "string",
          "title": "string",
          "publicationDate": "2021-06-27T05:39:19+00:00",
          "reviews": []
        }
        ');
        // https://github.com/api-platform/api-platform/blob/main/api/tests/Api/GreetingsTest.php
        return $this->assertResourceIsCreated();
    }

    /**
     * @depends testCreateBook
     *
     * @param int $bookId
     * @return int
     */
    public function testGetBook(int $bookId): int
    {
        $this->getLd('/api/books/' . $bookId);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonResponse('
            {
              "@context": "/api/contexts/Book",
              "@id": "/api/books/1",
              "@type": "Book",
              "id": 1,
              "isbn": "string",
              "title": "string",
              "publicationDate": "2021-06-27T05:39:19+00:00",
              "reviews": []
            }
        ');

        return $bookId;
    }

    /**
     * @depends testGetBook
     */
    public function testGetBooks(int $bookId): int
    {
        $this->getLd('/api/books');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonResponse('
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
            }
          ],
          "hydra:totalItems": 1
        }
        ');

        return $bookId;
    }

    /**
     * @depends testGetBook
     */
    public function testGetBooksWithPagination(): void
    {
        $this->getLd('/api/books?page=1&itemsPerPage=12');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonResponse('
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
            }
          ],
          "hydra:totalItems": 1,
          "hydra:view": {
            "@id": "/api/books?itemsPerPage=12",
            "@type": "hydra:PartialCollectionView"
          }
        }
        ');

        // То же самое, только передаем массивом параметров + не передается page + передается параметр pagination
        $this->getLd('/api/books', [
            'pagination' => true,
            'itemsPerPage' => 12,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonResponse('
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
            }
          ],
          "hydra:totalItems": 1,
          "hydra:view": {
            "@id": "/api/books?pagination=1&itemsPerPage=12",
            "@type": "hydra:PartialCollectionView"
          }
        }
        ');
    }

    /**
     * @depends testGetBooks
     */
    public function testGetBooksFilteredByIds(): void
    {
        $filteredIds = [1, 2, 3];
        $this->getLd('/api/books', [
            'id' => $filteredIds,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonResponse('
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
            }
          ],
          "hydra:totalItems": 1,
          "hydra:view": {
            "@id": "/api/books?id%5B%5D=1&id%5B%5D=2&id%5B%5D=3",
            "@type": "hydra:PartialCollectionView"
          }
        }
        ');
    }

    /**
     * @depends testGetBooks
     *
     * @param int $bookId
     * @return int
     */
    public function testUpdateBook(int $bookId): int
    {
        $json = '
            {
              "isbn": "new string",
              "title": "string",
              "publicationDate": "2022-06-27T05:39:19.583Z"
            }
            ';
        $this->putLd('/api/books/' . $bookId, $this->getJsonHelper()->jsonDecode($json, true));
        $this->assertJsonResponse('
            {
              "@context": "/api/contexts/Book",
              "@id": "/api/books/1",
              "@type": "Book",
              "id": 1,
              "isbn": "new string",
              "title": "string",
              "publicationDate": "2022-06-27T05:39:19+00:00",
              "reviews": []
            }
            ');
        return $this->assertResourceIsUpdated($bookId);
    }

    /**
     * @depends testUpdateBook
     *
     * @param int $bookId
     */
    public function testDeleteBook(int $bookId)
    {
        $this->deleteLd('/api/books/' . $bookId);
        $this->assertResourceIsDeleted();
    }

    public function testDeleteNonExistentBook()
    {
        $nonExistentBookId = 123;
        $this->deleteLd('/api/books/' . $nonExistentBookId);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        if ($_SERVER['APP_ENV'] === 'prod') {
            $this->assertJsonResponse('
            {
              "@context": "/api/contexts/Error",
              "@type": "hydra:Error",
              "hydra:title": "An error occurred",
              "hydra:description": "Not Found"
            }
            ');
        } else {
            // в респонсе будет стектрейс исключения
        }
        // независимо от значения APP_ENV, выбрасывается \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
        // и ловится в \Symfony\Component\HttpKernel\HttpKernel::handle а затем распечатывается логгером, потому что:
        // - \Symfony\Component\HttpKernel\HttpKernel::handleThrowable()
        // - \Symfony\Component\EventDispatcher\EventDispatcher::dispatch()
        // - \Symfony\Component\EventDispatcher\EventDispatcher::callListeners()
        // - \Symfony\Component\HttpKernel\EventListener\ErrorListener::logException()
        // - \Symfony\Component\HttpKernel\Log\Logger::log()
        // - \error_log()
        // это не мешает тестам, но печатает ошибку в stdout
        // Поправить это можно например:
        // 1. переопределив \Symfony\Component\HttpKernel\Log\Logger::log() и проверяя в $context NotFoundHttpException
        // 2. создав PR в symfony, решение простое: нужно сделать проверку на вид эксепшена в
        // \Symfony\Component\HttpKernel\EventListener\ErrorListener::logException() т.е. игнорируемые виды исключений
        // могут быть прописаны например в services.yaml и затем проброшены в ErrorListener
        // А пока что в терминале будет напечатано:
        // [error] Uncaught PHP Exception Symfony\Component\HttpKernel\Exception\NotFoundHttpException: "Not Found" at /app/vendor/api-platform/core/src/EventListener/ReadListener.php line 116
        // Кстати, если установить монолог, то NotFoundHttpException можно игнорировать: https://yapro.ru/article/5976
    }

    public function testCreateWithoutRequiredFieldValue(): void
    {
        // title не указан и выбрасывается \Doctrine\DBAL\Exception\NotNullConstraintViolationException
        $json = '
            {
              "isbn": "string"
            }
            ';
        $this->postLd('/api/books', $this->getJsonHelper()->jsonDecode($json, true));
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        if ($_SERVER['APP_ENV'] === 'prod') {
            $this->assertJsonResponse('
            {
              "@context": "/api/contexts/Error",
              "@type": "hydra:Error",
              "hydra:title": "An error occurred",
              "hydra:description": "Internal Server Error"
            }
            ');
        } else {
            // в респонсе будет стектрейс исключения
        }
    }

    public function testDefaultPagination()
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
        $this->assertJsonResponse('
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
        ');
    }

    /**
     * @depends testGetBooks
     */
    public function testGetBooksAndSpecifyPageAndItemsPerPage()
    {
        $this->getLd('/api/books?page=2&itemsPerPage=1');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonResponse('
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
        ');
    }
}
