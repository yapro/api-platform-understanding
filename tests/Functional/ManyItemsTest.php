<?php

declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use YaPro\ApiPlatformUnderstanding\Entity\Snake;
use YaPro\ApiPlatformUnderstanding\Entity\SnakeColor;
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
          ],
          "snakeCountries": []
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
          "snakeInfo": null,
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
          ],
          "snakeTypes":[],
          "snakeCountries":[]
        }
        ');

        return $this->assertResourceIsCreated();
    }

    /**
     * ОЖИДАЕМО: мы не только создали еще одну Snake, но и отвязали от Snake=1 запись SnakeColor=2, привязав её к новой Snake
     *
     * @depends testCreateSnakeAndTwoSnakeColor
     *
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
          "snakeInfo": null,
          "snakeColors": [
              {
                "@id": "/api/snake_colors/2",
                "@type": "SnakeColor",
                "id": 2,
                "color": "black"
              }
          ],
          "snakeTypes":[],
          "snakeCountries":[]
        }
        ');

        return $this->assertResourceIsCreated();
    }

    /**
     * ОЖИДАЕМО: изменяем Snake.title, отвязываем SnakeColor=1, привязываем SnakeColor=2
     *
     * @depends testCreateSnakeAndTwoSnakeColor
     *
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
          "snakeInfo": null,
          "snakeColors": [
              {
                "@id": "/api/snake_colors/2",
                "@type": "SnakeColor",
                "id": 2,
                "color": "black"
              }
          ],
          "snakeTypes":[],
          "snakeCountries":[]
        }
        ');

        return $this->assertResourceIsUpdated();
    }

    /**
     * ОЖИДАЕМО: изменяем Snake.length, все остальное не изменяется
     *
     * @depends testUpdateSnake1AndRemoveSnakeColor1AndAddSnakeColor2
     */
    public function testUpdateSnakeWithoutChangeSnakeColors()
    {
        $this->putLd('/api/snakes/1', '
        {
          "length": 12
        }
        ');
        $this->assertJsonResponse('
        {
          "@context": "/api/contexts/Snake",
          "@id": "/api/snakes/1",
          "@type": "Snake",
          "id": 1,
          "title": "cobra1",
          "length": 12,
          "snakeInfo":null,
          "snakeColors": [
              {
                "@id": "/api/snake_colors/2",
                "@type": "SnakeColor",
                "id": 2,
                "color": "black"
              }
          ],
          "snakeTypes":[],
          "snakeCountries":[]
        }
        ');
        $this->assertResourceIsUpdated();
    }

    /**
     * ОЖИДАЕМО: изменяем Snake.title, отвязываем все SnakeColor`s
     *
     * @depends testUpdateSnakeWithoutChangeSnakeColors
     */
    public function testUpdateSnake1AndRemoveSnakeColors()
    {
        /** @var Snake $snake */
        $snake = self::$entityManager->find(Snake::class, 1);
        $this->assertEquals(1, $snake->getSnakeColors()->count());
        /** @var SnakeColor $snakeColor */
        $snakeColor = $snake->getSnakeColors()->first();
        self::$entityManager->clear();

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
          "snakeInfo":null,
          "snakeColors": [],
          "snakeTypes":[],
          "snakeCountries":[]
        }
        ');
        $this->assertResourceIsUpdated();

        self::$entityManager->clear();
        /** @var Snake $snake */
        $snakeColorFromDb = self::$entityManager->find(SnakeColor::class, $snakeColor->getId());
        $this->assertTrue($snakeColorFromDb instanceof SnakeColor);
        // итог: ранее привязанные SnakeColor`s не удаляются из бд, а отвязываются от Snake
    }

    /**
     * @depends testUpdateSnake1AndRemoveSnakeColors
     */
    public function testCreateSnakeWithExistingSnakeColorAndNewSnakeType()
    {
        // создаем одним запросом Parent + привязываем Kid (и изменяем ей данные) + создаем Kid
        $this->postLd('/api/snakes', '
        {
          "title": "grass-snake",
          "snakeColors": [
              {
                "@id": "/api/snake_colors/2",
                "id": 2,
                "color": "green"
              }
          ],
          "snakeTypes": [
            {"typeName": "nonpoisonous"}
          ]
        }
        ');
        self::assertResourceIsCreated();
        $this->assertJsonResponse('
        {
          "@context": "/api/contexts/Snake",
          "@id": "/api/snakes/3",
          "@type": "Snake",
          "id": 3,
          "title": "grass-snake",
          "length": null,
          "snakeInfo":null,
          "snakeColors": [
              {
                "@id": "/api/snake_colors/2",
                "@type": "SnakeColor",
                "id": 2,
                "color": "green"
              }
          ],
          "snakeTypes": [
              {
                "@id": "/api/snake_types/1",
                "@type": "SnakeType",
                "id": 1,
                "typeName": "nonpoisonous"
              }
          ],
          "snakeCountries":[]
        }
        ');
    }

    // НЕОЖИДАННО: чтобы в объекте snakeInfo появилась ссылка на "snake": "/api/snakes/1" нужно для SnakeInfo.snake
    // прописать @Groups({"apiRead", "apiWrite"})
    public function testCreateSnakeAndSnakeInfo(): int
    {
        $this->postLd('/api/snakes', '
        {
          "title": "cobra",
          "snakeInfo": {"averageLength": 123}
        }
        ');

        $this->assertJsonResponse('
        {
          "@context": "/api/contexts/Snake",
          "@id": "/api/snakes/4",
          "@type": "Snake",
          "id": 4,
          "title": "cobra",
          "length": null,
          "snakeInfo": {
              "@id": "/api/snake_infos/1",
              "@type": "SnakeInfo",
              "id": 1,
              "averageLength": 123
          },
          "snakeColors": [],
          "snakeTypes":[],
          "snakeCountries":[]
        }
        ');

        return $this->assertResourceIsCreated();
    }

    public function testUpdateSnakeAndSnakeInfo(): int
    {
        $this->putLd('/api/snakes/4', '
        {
          "title": "cobra1",
          "snakeInfo": {
             "@id": "/api/snake_infos/1",
             "averageLength": 12345
          }
        }
        ');
        $this->assertJsonResponse('
        {
          "@context": "/api/contexts/Snake",
          "@id": "/api/snakes/4",
          "@type": "Snake",
          "id": 4,
          "title": "cobra1",
          "length": null,
          "snakeInfo": null,
          "snakeInfo": {
              "@id": "/api/snake_infos/1",
              "@type": "SnakeInfo",
              "id": 1,
              "averageLength": 12345
          },
          "snakeColors": [],
          "snakeTypes":[],
          "snakeCountries":[]
        }
        ');

        return $this->assertResourceIsUpdated();
    }

    // Выше были тесты, когда SnakeColor можно было создать без связи с Snake, а в данном тесте мы проверяем создание
    // SnakeCountries которые не могут существовать без Snake, но Snake может обходиться без SnakeCountries
    public function testCreateSnakeAndTwoSnakeCountries(): int
    {
        self::truncateAllTablesInSqLite();

        $this->postLd('/api/snakes', '
        {
          "title": "cobra",
          "snakeColors": [],
          "snakeCountries": [
            {"countryName": "Russia"},
            {"countryName": "China"}
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
          "snakeInfo": null,
          "snakeColors": [],
          "snakeTypes":[],
          "snakeCountries":[
            {
                "@id": "/api/snake_countries/1",
                "@type": "SnakeCountry",
                "id": 1,
                "countryName": "Russia"
            },
            {
                "@id": "/api/snake_countries/2",
                "@type": "SnakeCountry",
                "id": 2,
                "countryName": "China"
            }
          ]
        }
        ');

        return $this->assertResourceIsCreated();
    }
}
