<?php
declare(strict_types=1);

namespace YaPro\ApiPlatformUnderstanding\Tests\Functional;

use YaPro\SymfonyHttpClientExt\HttpClientJsonLdExtTrait;
use YaPro\SymfonyHttpTestExt\BaseTestCase;

class BaseTest extends BaseTestCase
{
	use HttpClientJsonLdExtTrait;

	public function testCreateBook(): int
	{
		$json = '
		{
		  "isbn": "string",
		  "title": "string",
		  "publicationDate": "2021-06-27T05:39:19.583Z"
		}
		';
		$this->postLd('/api/books', $this->getJsonHelper()->jsonDecode($json, true));
		return $this->assertResourceIsCreated();
	}
}
