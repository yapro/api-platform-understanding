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
 * Больше информации:
 * - https://symfonycasts.com/screencast/api-platform/collections-create
 * - https://symfonycasts.com/screencast/api-platform/embedded-write
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
     *
     *
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
    public function testCreateSnakeAndTwoSnakeColor(): int
    {
        self::truncateAllTablesInSqLite();

        $this->postLd('/api/snakes', '
        {
          "title": "cobra",
          "snakeColors": [
            {"color": "white"},
            {"color": "black"}
          ]
        }
        ');
        $this->assertJsonResponse('
        {
          "@context": "/api/contexts/Snake",
          "@id": "/api/snakes/1",
          "@type": "Snake",
          "id": 1,
          "title": "cobra",
          "length": null,
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

    /**
     * ОЖИДАЕМО: мы не только создали еще одну Snake, но и отвязали от Snake=1 запись SnakeColor=2, привязав к новой Snake
     *
     * @depends testCreateSnakeAndTwoSnakeColor
     * @return int
     */
    public function testCreateSnakeWithExistingSnakeColor(): int
    {
        $this->postLd('/api/snakes', '
        {
          "title": "cobra2",
          "snakeColors": [
            "/api/snake_colors/2"
          ]
        }
        ');
        $this->assertJsonResponse('
        {
          "@context": "/api/contexts/Snake",
          "@id": "/api/snakes/2",
          "@type": "Snake",
          "id": 2,
          "title": "cobra2",
          "length": null,
          "snakeColors": [
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

    /**
     * ОЖИДАЕМО: изменяем Snake.title, отвязываем SnakeColor=1, привязываем SnakeColor=2
     *
     * @depends testCreateSnakeAndTwoSnakeColor
     * @return int
     */
    public function testUpdateSnake1AndRemoveSnakeColor1AndAddSnakeColor2(): int
    {
        $this->putLd('/api/snakes/1', '
        {
          "title": "cobra1",
          "snakeColors": [
            "/api/snake_colors/2"
          ]
        }
        ');
        $this->assertJsonResponse('
        {
          "@context": "/api/contexts/Snake",
          "@id": "/api/snakes/1",
          "@type": "Snake",
          "id": 1,
          "title": "cobra1",
          "length": null,
          "snakeColors": [
              {
                "@id": "/api/snake_colors/2",
                "@type": "SnakeColor",
                "id": 2,
                "color": "black"
              }
          ]
        }
        ');
        return $this->assertResourceIsUpdated();
    }

    /**
     * ОЖИДАЕМО: изменяем Snake.title, отвязываем все SnakeColor`s
     *
     * @depends testUpdateSnake1AndRemoveSnakeColor1AndAddSnakeColor2
     * @return int
     */
    public function testUpdateSnake1AndRemoveSnakeColors(): int
    {
        $this->putLd('/api/snakes/1', '
        {
          "length": 123,
          "snakeColors": []
        }
        ');
        $this->assertJsonResponse('
        {
          "@context": "/api/contexts/Snake",
          "@id": "/api/snakes/1",
          "@type": "Snake",
          "id": 1,
          "title": "cobra1",
          "length": 123,
          "snakeColors": []
        }
        ');
        return $this->assertResourceIsUpdated();
    }
}
