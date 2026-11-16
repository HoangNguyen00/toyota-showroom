<?php

namespace Tests\Feature;

use Tests\TestCase;

class CollectionPreviewTest extends TestCase
{
    public function test_detail_links_and_availability_actions_are_consistent(): void
    {
        $this->withoutVite()->get('/xe/1')->assertOk()->assertSee('Đặt lịch xem xe')->assertSee('/dat-lich?car=1', false);
        $this->withoutVite()->get('/xe/2')->assertOk()->assertSee('Chưa nhận lịch xem');
        $this->withoutVite()->get('/xe/5')->assertOk()->assertSee('Khám phá xe khác')->assertDontSee('Đặt lịch xem xe');
        $this->withoutVite()->get('/xe/999')->assertNotFound();
    }

    public function test_collection_preview_renders_without_catalog_database(): void
    {
        $this->withoutVite()->get('/xe')->assertOk()
            ->assertSee('Toyota Corolla Cross')
            ->assertSee('Toyota Fortuner')
            ->assertSee('Bản xem trước')
            ->assertSee('data-filters', false);
    }
}
