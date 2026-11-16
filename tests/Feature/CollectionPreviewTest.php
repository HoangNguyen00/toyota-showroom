<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_links_and_availability_actions_are_consistent(): void
    {
        $this->withoutVite()->get('/xe-preview/1')->assertOk()->assertSee('Yêu cầu tư vấn')->assertSee('Đặt lịch xem xe');
        $this->withoutVite()->get('/xe-preview/2')->assertOk()->assertSee('Yêu cầu tư vấn')->assertSee('Chưa nhận lịch xem');
        $this->withoutVite()->get('/xe-preview/5')->assertOk()->assertSee('Khám phá xe khác')->assertDontSee('data-preview="Tư vấn Toyota Innova"', false);
        $this->withoutVite()->get('/xe-preview/999')->assertNotFound();
    }

    public function test_collection_preview_renders_without_catalog_database(): void
    {
        $this->withoutVite()->get('/collection-preview')->assertOk()
            ->assertSee('Toyota Corolla Cross')
            ->assertSee('Toyota Fortuner')
            ->assertSee('Bản xem trước')
            ->assertSee('data-filters', false);
    }
}
