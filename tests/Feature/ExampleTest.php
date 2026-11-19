<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_home_page_opens_the_react_spa(): void
    {
        $this->get('/')->assertRedirect(config('app.frontend_url'));
    }
}
