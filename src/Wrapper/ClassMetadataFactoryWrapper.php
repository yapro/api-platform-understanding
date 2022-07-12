<?php

declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Wrapper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

class ClassMetadataFactoryWrapper implements ClassMetadataFactoryInterface
{
    private ClassMetadataFactoryInterface $decorated;
    private ?Request $request; // nullable потому что при запуске из консоли MasterRequest-а нет

    public function __construct(ClassMetadataFactoryInterface $decorated, RequestStack $requestStack)
    {
        $this->decorated = $decorated;
        $this->request = $requestStack->getMasterRequest();
    }

    // По-умолчанию возвращаемое тут значение далее будет закэшировано в \ApiPlatform\Core\Metadata\Property\Factory\CachedPropertyMetadataFactory
    // но чтобы оно не кэшировалось, мы в \ApiPlatform\Core\Metadata\Property\Factory\CachedPropertyMetadataFactory
    // подменим сервис кэширования, заменив его на Symfony\Component\Cache\Adapter\ArrayAdapter с помощью
    // переопределения сервиса api_platform.cache.metadata.property
    public function getMetadataFor($value): ClassMetadataInterface
    {
        // $value является неймспейсом сущности
        $return = $this->decorated->getMetadataFor($value);
        $attributesMetadata = $return->getAttributesMetadata();
        foreach ($attributesMetadata as $attributeName => $attributeMetadata) {
            // запрещаем читать и писать в атрибут article:
            if ($attributeName === 'article') {
                continue;
            }
            // запрещаем читать из атрибута comments при GET-запросе:
            if (
                $attributeName === 'comments' &&
                $this->request &&
                $this->request->isMethod('get') &&
                mb_substr($this->request->getPathInfo(), 0, 23) === '/api-platform/articles/'
            ) {
                continue;
            }
            $attributeMetadata->addGroup('apiRead');
            $attributeMetadata->addGroup('apiWrite');
        }

        return $return;
    }

    public function hasMetadataFor($value): bool
    {
        return $this->decorated->hasMetadataFor($value);
    }
}
