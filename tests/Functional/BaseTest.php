<?php
declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use YaPro\DoctrineExt\ReloadDatabaseTrait;
use YaPro\SymfonyHttpClientExt\HttpClientJsonLdExtTrait;
use YaPro\SymfonyHttpTestExt\BaseTestCase;

class BaseTest extends BaseTestCase
{
	use HttpClientJsonLdExtTrait;
    use ReloadDatabaseTrait;

    protected static EntityManagerInterface $entityManager;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::truncateAllTablesInSqLite();
    }

    public function testCreateBook(): int
	{
		$json = '
		{
		  "isbn": "string",
		  "title": "string",
		  "publicationDate": "2021-06-27T05:39:19.583Z"
		}
		';
		$crawler = $this->postLd('/api/books', $this->getJsonHelper()->jsonDecode($json, true));
		$this->assertJsonResponse('
		{
		  "@context": "\/api\/contexts\/Book",
		  "@id": "\/api\/books\/1",
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


}
