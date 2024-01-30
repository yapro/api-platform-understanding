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
    public function testCreateSnakeAndTwoSnakeCountries()
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
        $this->assertResourceIsCreated();
        /*
        Попытка написать тест добавляющий дочернюю сущность с указанием существующей сущности завершилась неудачей -
        следующие 2 запроса не работают в следствии того, что возникает ошибка:
            "The total number of joined relations has exceeded the specified maximum. Raise the limit if necessary with the
            "api_platform.eager_loading.max_joins" configuration key
            (https://api-platform.com/docs/core/performance/#eager-loading), or limit the maximum serialization depth using
            the "enable_max_depth" option of the Symfony serializer
            (https://symfony.com/doc/current/components/serializer.html#handling-serialization-depth)." at
            /app/vendor/api-platform/core/src/Bridge/Doctrine/Orm/Extension/EagerLoadingExtension.php line 137
        а возникает она из-за того, что:
        1. когда мы указываем Snake, он вытягивается из базы
        2. Snake полученный из базы тянет за собой все ему известные SnakeCountry
        3. SnakeCountry снова вытягивают Snake (да, он закэширован)
        4. дальше происходит рекурсия - повторение шагов 2 и 3 по-очереди до возникновения выше указанной ошибки.
        Как это можно в теории можно починить, пара способов:
        1. нужно в запросе иметь переменную, которая говорила бы на какую грубину должны вытягивать зависимости
        2. в методе \ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension::joinRelations изменить:
            if (0 === $currentDepth && ($normalizationContext[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ?? false)) {
            так, чтобы учитывалось значение -1 (возникающее, когда "enable_max_depth"=true + аннотация @MaxDepth(1)
            объявлена к свойствам обоих зависимых классов.

        $this->postLd('/api/snake_countries', '
        {
          "countryName": "USA",
          "snake": {
              "@id": "/api/snakes/1",
              "@type": "Snake",
              "id": 1
          }
        }
        ');

        $this->postLd('/api/snake_countries', '
        {
          "countryName": "USA",
          "snake": "/api/snakes/1"
        }
        ');

        this->assertResourceIsCreated();
        */

        /* Следующая идея - сделать setter который будет делать магию - превращать ID родителя в объект родителя -
        $this->postLd('/api/snake_countries', '
        {
          "countryName": "USA",
          "snakeId": {
              "@id": "/api/snakes/1",
              "@type": "Snake",
              "id": 1
          }
        }
        ');
        Увы, не получилось, т.к. в сущности нет $entityManager-а:
        public function setSnakeId(int $snakeId): self
        {
            $this->countryName = $entityManager->getreference(Snake::class, $snakeId);

            return $this;
        }
        */
    }
}
