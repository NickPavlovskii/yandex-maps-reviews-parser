<?php

namespace Tests\Feature;

use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    public function test_documentation_page_is_available(): void
    {
        $this->get('/api/documentation')
            ->assertOk()
            ->assertSee('swagger', false);
    }
}
