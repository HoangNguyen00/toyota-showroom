<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePreviewTest extends TestCase
{
    public function test_home_has_camry_story_and_working_discovery_links(): void
    {
        $this->withoutVite()->get('/')->assertOk()
            ->assertSee('data-camry-story', false)
            ->assertSee('camry-exploded.mp4')
            ->assertSee('assets/home/toyota-headlight.jpg')
            ->assertSee('assets/home/toyota-steering-wheel.jpg')
            ->assertSee('data-home-reveal', false)
            ->assertSee('id="dich-vu"', false)
            ->assertSee('id="showroom"', false)
            ->assertSee(route('cars.index'));
    }
}
