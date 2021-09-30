<?php

declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use YaPro\DoctrineExt\ReloadDatabaseTrait;
use YaPro\SymfonyHttpClientExt\HttpClientJsonLdExtTrait;
use YaPro\SymfonyHttpTestExt\BaseTestCase;

/**
 * НЕОЖИДАННО: если в классе для ApiResource объявляется normalizationContext, группы должны быть обязательно объявлены
 * НЕОЖИДАННО: если в классе для ApiResource объявлен normalizationContext + группы, поля перестают быть доступными, а
 *   чтобы они снова стали доступными, нужно объявлять принадлежность поля к группе
 * НЕОЖИДАННО: то же самое касается и ApiResource атрибута denormalizationContext
 * НЕОЖИДАННО: если в ApiResource указан атрибут denormalizationContext, то, чтобы сохранить данные в приватные поля
 *   сущности, нужно создавать сетеры для этих полей
 * НЕОЖИДАННО: если в ApiResource указан атрибут denormalizationContext, то, чтобы сохранить сущность со связанными
 *   сущностями, нужно чтобы в основной сущности был создан метод removeKid()
 * НЕОЖИДАННО: если в ApiResource у связанной сущности поле id приватное, то, чтобы прочитать сущность со связанными
 *   сущностями, нужно чтобы в связанной сущности был создан метод getId()
 */
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

    public function testCreateSnakeAndTwoSnakeColor(): int
    {
        self::truncateAllTablesInSqLite();

        /*
         * НЕОЖИДАННО: чтобы создавать Snake + несколько SnakeColor, нужно:
         * 1. в аннотации к классам Snake и SnakeColor прописать:
         * @ApiResource(
         *     denormalizationContext={
         *         "groups": {"apiWrite"}
         *     }
         * )
         *
         * 2. всем заполняемым полям в Snake и SnakeColor прописать:
         * @Groups({"apiWrite"})
         */
        $json = '
        {
          "title": "cobra",
          "snakeColors": [
            {"color": "white"},
            {"color": "black"}
          ]
        }
        ';
        $this->postLd('/api/snakes', $this->getJsonHelper()->jsonDecode($json, true));
        $this->assertResourceIsCreated();

        /*
         * НЕОЖИДАННО: чтобы получить Snake + несколько SnakeColor, нужно:
         * 1. в аннотации к классам Snake и SnakeColor прописать:
         * @ApiResource(
         *     normalizationContext={
         *         "groups": {"apiRead"}
         *     }
         * )
         *
         * 2. всем заполняемым полям в Snake и SnakeColor прописать:
         * @Groups({"apiRead"})
         *
         * 3. в классе Snake должен быть объявлен метод removeSnakeColor(), иначе возвратится пустой список "snakeColors": []
         */
        $this->assertJsonResponse('
        {
          "@context": "/api/contexts/Snake",
          "@id": "/api/snakes/1",
          "@type": "Snake",
          "id": 1,
          "title": "cobra",
          "snakeColors": [
              {
                "@id": "/api/snake_colors/1",
                "@type": "SnakeColor",
                "id": 1,
                "color": "white"
              },
              {
                "@id": "/api/snake_colors/2",
                "@type": "SnakeColor",
                "id": 2,
                "color": "black"
              }
          ]
        }
        ');
        return $this->assertResourceIsCreated();
    }
}
