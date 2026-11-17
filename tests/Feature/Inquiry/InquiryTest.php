<?php

namespace Tests\Feature\Inquiry;

use App\Enums\InquiryStatus;
use App\Enums\UserRole;
use App\Events\InquiryMessageCreated;
use App\Jobs\BroadcastInquiryMessage;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\Inquiry;
use App\Models\Message;
use App\Models\User;
use App\Services\Account\UpdateUserStatus;
use App\Services\Inquiry\InquiryLock;
use App\Services\Inquiry\InquiryService;
use App\Services\Inquiry\MessageService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InquiryTest extends TestCase
{
    use DatabaseMigrations;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.$connection.database");
        if (! ($connection === 'sqlite' && $database === ':memory:')
            && ! ($connection === 'mysql' && config('database.connections.mysql.host') === '127.0.0.1'
                && ($database === 'toyota_showroom_test' || preg_match('/^toyota_showroom_tv4_test_\d+$/', $database)))) {
            throw new \RuntimeException('TV4 tests require SQLite memory or a dedicated local TV4 MySQL test database.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(UserRole $role = UserRole::Customer): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function inquiry(?User $customer = null, ?User $sales = null, string $status = 'assigned'): Inquiry
    {
        return Inquiry::create([
            'customer_id' => $customer?->id, 'assigned_to' => $sales?->id,
            'contact_name' => 'Khách demo', 'contact_phone' => '0900000000',
            'content' => 'Tư vấn chiếc xe Toyota', 'status' => $status,
            'internal_note' => 'PRIVATE-NOTE',
        ]);
    }

    private function form(): array
    {
        return ['name' => 'Khách', 'phone' => '0900000000', 'email' => 'demo@example.test', 'content' => 'Tôi cần tư vấn xe Toyota'];
    }

    private function message(): array
    {
        return ['content' => 'Xin chào Toyota', 'client_message_id' => (string) Str::uuid()];
    }

    public function test_guest_and_customer_create_allowlisted_inquiries_and_fourth_request_is_throttled(): void
    {
        $this->postJson('/tu-van', $this->form() + ['customer_id' => 999, 'assigned_to' => 999, 'status' => 'closed'])
            ->assertCreated()->assertExactJson(['id' => 1, 'message' => 'Đã tiếp nhận yêu cầu tư vấn.']);
        $this->assertDatabaseHas('inquiries', ['id' => 1, 'customer_id' => null, 'assigned_to' => null, 'status' => 'new']);
        $customer = $this->user();
        $this->actingAs($customer)->postJson('/tu-van', $this->form())->assertCreated();
        $this->assertDatabaseHas('inquiries', ['id' => 2, 'customer_id' => $customer->id, 'contact_email' => 'demo@example.test']);
        $this->postJson('/tu-van', $this->form())->assertCreated();
        $this->postJson('/tu-van', $this->form())->assertStatus(429);
    }

    public function test_public_validation_and_staff_cannot_impersonate_customer(): void
    {
        $this->postJson('/tu-van', ['name' => str_repeat('a', 101), 'phone' => '0123', 'email' => 'bad', 'content' => 'short'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'phone', 'email', 'content']);
        $this->actingAs($this->user(UserRole::Sales))->postJson('/tu-van', $this->form())->assertForbidden();
        $this->actingAs($this->user(UserRole::Admin))->postJson('/tu-van', $this->form())->assertForbidden();
    }

    public function test_car_visibility_and_status_use_database_records(): void
    {
        $category = CarCategory::create(['brand' => 'Toyota', 'name' => 'Camry', 'slug' => 'camry', 'is_visible' => true]);
        $car = Car::create(['category_id' => $category->id, 'code' => 'TV4-TEST', 'slug' => 'tv4-test', 'name' => 'Camry', 'year' => 2026, 'color' => 'Đen', 'price' => '1000000000', 'is_visible' => true]);
        $service = app(InquiryService::class);
        foreach (['available', 'reserved'] as $status) {
            $car->update(['status' => $status]);
            $this->assertSame($car->id, $service->create(null, $this->form() + ['car_id' => $car->id])->car_id);
        }
        foreach ([['status' => 'sold'], ['status' => 'available', 'is_visible' => false]] as $fields) {
            $car->update($fields);
            try {
                $service->create(null, $this->form() + ['car_id' => $car->id]);
                $this->fail('Invalid car accepted');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('car_id', $e->errors());
            }
        }
        $car->update(['is_visible' => true]);
        $category->update(['is_visible' => false]);
        $this->postJson('/tu-van', $this->form() + ['car_id' => $car->id])->assertUnprocessable();
    }

    public function test_lists_details_and_views_are_scoped_without_internal_note_leaks(): void
    {
        $a = $this->user();
        $b = $this->user();
        $sales = $this->user(UserRole::Sales);
        $own = $this->inquiry($a, $sales);
        $other = $this->inquiry($b);
        $this->actingAs($a)->getJson('/yeu-cau')->assertOk()->assertJsonCount(1, 'data')->assertDontSee('PRIVATE-NOTE');
        $this->getJson('/yeu-cau/'.$other->id)->assertForbidden();
        $this->get('/yeu-cau/'.$own->id)->assertOk()->assertDontSee('PRIVATE-NOTE');
        $this->actingAs($sales)->getJson('/staff/inquiries')->assertJsonCount(1, 'data');
        $this->getJson('/staff/inquiries/'.$other->id)->assertForbidden();
        $this->get('/staff/inquiries/'.$own->id)->assertOk()->assertSee('PRIVATE-NOTE');
        $this->actingAs($this->user(UserRole::Admin))->getJson('/admin/inquiries')->assertJsonCount(2, 'data');
        $this->get('/admin/inquiries/'.$own->id)->assertOk();
        $this->getJson('/admin/inquiries/99999')->assertNotFound();
        $this->actingAs($a)->getJson('/admin/inquiries')->assertForbidden();
    }

    public function test_assignment_reassignment_state_notes_and_closing_rules(): void
    {
        $admin = $this->user(UserRole::Admin);
        $sales = $this->user(UserRole::Sales);
        $next = $this->user(UserRole::Sales);
        $inquiry = $this->inquiry($this->user(), null, 'new');
        $url = '/admin/inquiries/'.$inquiry->id;
        $this->actingAs($admin)->patchJson($url.'/close', ['close_result' => 'da_tu_van'])->assertUnprocessable();
        $this->patchJson($url.'/assign', ['assigned_to' => $sales->id])->assertOk()->assertJsonPath('status', 'assigned');
        $this->assertSame(2, $inquiry->fresh()->access_version);
        $this->patchJson($url.'/assign', ['assigned_to' => $sales->id])->assertOk();
        $this->assertSame(2, $inquiry->fresh()->access_version);
        $this->patchJson($url.'/assign', ['assigned_to' => (string) $sales->id])->assertOk();
        $this->assertSame(2, $inquiry->fresh()->access_version);
        $this->patchJson($url.'/assign', ['assigned_to' => $next->id])->assertOk();
        $this->assertSame(3, $inquiry->fresh()->access_version);
        $this->actingAs($sales)->patchJson('/staff/inquiries/'.$inquiry->id.'/start')->assertForbidden();
        $this->actingAs($next)->patchJson('/staff/inquiries/'.$inquiry->id.'/start')->assertOk()->assertJsonPath('status', 'in_progress');
        $this->patchJson('/staff/inquiries/'.$inquiry->id.'/start')->assertUnprocessable();
        $this->patchJson('/staff/inquiries/'.$inquiry->id.'/note', ['internal_note' => 'PRIVATE UPDATED'])->assertOk();
        $this->patchJson('/staff/inquiries/'.$inquiry->id.'/close', ['close_result' => 'bad'])->assertUnprocessable();
        $this->patchJson('/staff/inquiries/'.$inquiry->id.'/close', ['close_result' => 'da_tu_van'])->assertOk()->assertJsonPath('status', 'closed');
        $this->patchJson('/staff/inquiries/'.$inquiry->id.'/note', ['internal_note' => 'overwrite'])->assertUnprocessable();
        $this->actingAs($admin)->patchJson($url.'/assign', ['assigned_to' => $sales->id])->assertUnprocessable();
        $this->assertSame('PRIVATE UPDATED', $inquiry->fresh()->internal_note);
    }

    public function test_assignment_rejects_non_sales_and_locked_users(): void
    {
        $admin = $this->user(UserRole::Admin);
        $customer = $this->user();
        $sales = $this->user(UserRole::Sales);
        $sales->forceFill(['is_active' => false])->save();
        $i = $this->inquiry($customer, null, 'new');
        foreach ([$admin, $customer, $sales] as $target) {
            $this->actingAs($admin)->patchJson('/admin/inquiries/'.$i->id.'/assign', ['assigned_to' => $target->id])->assertUnprocessable();
        }
        $this->assertSame(InquiryStatus::New, $i->fresh()->status);
    }

    public function test_pagination_filters_and_customer_management_denial(): void
    {
        $customer = $this->user();
        for ($n = 0; $n < 18; $n++) {
            $this->inquiry($customer, null, $n === 17 ? 'closed' : 'new');
        }
        $this->inquiry($this->user(), null, 'new');
        $this->actingAs($customer)->getJson('/yeu-cau')->assertJsonCount(15, 'data')->assertJsonPath('last_page', 2);
        $this->getJson('/yeu-cau?page=2')->assertJsonCount(3, 'data');
        $this->getJson('/yeu-cau?status=closed')->assertJsonCount(1, 'data');
        $this->getJson('/yeu-cau?status=other')->assertUnprocessable();
        $this->patchJson('/staff/inquiries/1/note', ['internal_note' => 'bad'])->assertForbidden();
        $this->deleteJson('/yeu-cau/1')->assertStatus(405);
    }

    public function test_locked_sales_and_closed_inquiries_discard_queued_broadcasts(): void
    {
        Event::fake([InquiryMessageCreated::class]);
        $admin = $this->user(UserRole::Admin);
        $sales = $this->user(UserRole::Sales);
        $customer = $this->user();
        $i = $this->inquiry($customer, $sales);
        [$message] = app(MessageService::class)->send($sales, $i, $this->message());
        app(UpdateUserStatus::class)->handle($admin, $sales, false);
        (new BroadcastInquiryMessage($message->id, 1))->handle(app(InquiryLock::class));
        Event::assertNotDispatched(InquiryMessageCreated::class);
        $this->actingAs($sales)->getJson('/yeu-cau/'.$i->id.'/tin-nhan')->assertForbidden();
        app(UpdateUserStatus::class)->handle($admin, $sales, true);
        [$next] = app(MessageService::class)->send($customer, $i->fresh(), $this->message());
        app(InquiryService::class)->update($admin, $i->fresh(), 'close', ['close_result' => 'da_tu_van']);
        (new BroadcastInquiryMessage($next->id, 2))->handle(app(InquiryLock::class));
        Event::assertNotDispatched(InquiryMessageCreated::class);
        $this->assertDatabaseCount('messages', 2);
    }

    public function test_messages_are_allowlisted_idempotent_and_conflicting_uuid_is_rejected(): void
    {
        $customer = $this->user();
        $sales = $this->user(UserRole::Sales);
        $i = $this->inquiry($customer, $sales);
        $url = '/yeu-cau/'.$i->id.'/tin-nhan';
        $data = $this->message();
        $this->actingAs($customer)->postJson($url, $data + ['sender_id' => $sales->id])->assertCreated()
            ->assertJsonPath('sender.id', $customer->id)->assertJsonStructure(['id', 'inquiry_id', 'sender' => ['id', 'name'], 'content', 'created_at'])
            ->assertDontSee('PRIVATE-NOTE')->assertDontSee('client_message_id');
        $this->postJson($url, $data)->assertOk();
        $this->postJson($url, array_replace($data, ['content' => 'Khác']))->assertStatus(409);
        $other = $this->inquiry($customer, $sales);
        $this->postJson('/yeu-cau/'.$other->id.'/tin-nhan', $data)->assertStatus(409);
        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseCount('jobs', 1);
        $this->actingAs($sales)->postJson($url, $this->message())->assertCreated();
        $this->assertSame(InquiryStatus::InProgress, $i->fresh()->status);
        $this->actingAs($this->user(UserRole::Admin))->postJson($url, $this->message())->assertCreated();
        $this->getJson($url)->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_message_validation_and_unauthorized_reads_writes(): void
    {
        $customer = $this->user();
        $sales = $this->user(UserRole::Sales);
        $i = $this->inquiry($customer, $sales);
        $url = '/yeu-cau/'.$i->id.'/tin-nhan';
        $this->getJson($url)->assertUnauthorized();
        foreach ([$this->user(), $this->user(UserRole::Sales)] as $other) {
            $this->actingAs($other)->getJson($url)->assertForbidden();
            $this->postJson($url, $this->message())->assertForbidden();
        }
        $this->actingAs($customer)->postJson($url, ['content' => '', 'client_message_id' => 'bad'])->assertUnprocessable();
        $this->postJson($url, ['content' => str_repeat('x', 1001), 'client_message_id' => (string) Str::uuid()])->assertUnprocessable();
        foreach (['new', 'closed'] as $status) {
            $i->update(['status' => $status]);
            $this->postJson($url, $this->message())->assertForbidden();
            $this->getJson($url)->assertOk();
        }
        $guestInquiry = $this->inquiry(null, $sales);
        $this->actingAs($sales)->getJson('/yeu-cau/'.$guestInquiry->id.'/tin-nhan')->assertForbidden();
    }

    public function test_history_has_bounded_cursors_and_catchup_does_not_mix_inquiries(): void
    {
        $user = $this->user();
        $i = $this->inquiry($user);
        $other = $this->inquiry($user);
        for ($n = 0; $n < 55; $n++) {
            Message::create(['inquiry_id' => $i->id, 'sender_id' => $user->id, 'content' => 'Tin '.$n, 'client_message_id' => (string) Str::uuid()]);
        }
        Message::create(['inquiry_id' => $other->id, 'sender_id' => $user->id, 'content' => 'OTHER-THREAD', 'client_message_id' => (string) Str::uuid()]);
        $url = '/yeu-cau/'.$i->id.'/tin-nhan';
        $this->actingAs($user)->getJson($url)->assertJsonCount(50, 'data')->assertJsonPath('has_more', true)->assertJsonPath('data.0.id', 6);
        $this->getJson($url.'?before_id=6')->assertJsonCount(5, 'data')->assertJsonPath('has_more', false);
        $this->getJson($url.'?after_id=0')->assertJsonCount(50, 'data')->assertJsonPath('next_after_id', 50);
        $this->getJson($url.'?after_id=50')->assertJsonCount(5, 'data')->assertDontSee('OTHER-THREAD');
        $this->getJson($url.'?limit=51')->assertUnprocessable();
        $this->getJson($url.'?after_id=0&before_id=10')->assertUnprocessable();
    }

    public function test_rollback_discards_messages_and_after_commit_job(): void
    {
        $user = $this->user();
        $i = $this->inquiry($user, $this->user(UserRole::Sales));
        DB::beginTransaction();
        app(MessageService::class)->send($user, $i, $this->message());
        // Intent exists transactionally, but no event is emitted before commit.
        $this->assertDatabaseCount('jobs', 1);
        DB::rollBack();
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('jobs', 0);
        app(MessageService::class)->send($user, $i, $this->message());
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_channels_and_queued_jobs_recheck_reassignment_and_account_lock(): void
    {
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'testing-key', 'broadcasting.connections.reverb.secret' => 'testing-secret', 'broadcasting.connections.reverb.app_id' => 'testing-app']);
        require base_path('routes/channels.php');
        Event::fake([InquiryMessageCreated::class]);
        $admin = $this->user(UserRole::Admin);
        $customer = $this->user();
        $sales = $this->user(UserRole::Sales);
        $next = $this->user(UserRole::Sales);
        $i = $this->inquiry($customer, $sales);
        $auth = ['socket_id' => '123.456', 'channel_name' => 'private-inquiry.'.$i->id.'.v1'];
        $this->actingAs($customer)->postJson('/broadcasting/auth', $auth)->assertOk();
        $this->actingAs($sales)->postJson('/broadcasting/auth', $auth)->assertOk();
        $this->actingAs($next)->postJson('/broadcasting/auth', $auth)->assertForbidden();
        [$message] = app(MessageService::class)->send($customer, $i, $this->message());
        (new BroadcastInquiryMessage($message->id, 1))->handle(app(InquiryLock::class));
        Event::assertDispatched(InquiryMessageCreated::class, 1);
        app(InquiryService::class)->update($admin, $i, 'assign', ['assigned_to' => $next->id]);
        (new BroadcastInquiryMessage($message->id, 1))->handle(app(InquiryLock::class));
        Event::assertDispatched(InquiryMessageCreated::class, 1);
        $this->actingAs($sales)->postJson('/broadcasting/auth', $auth)->assertForbidden();
        $this->actingAs($customer)->postJson('/broadcasting/auth', $auth)->assertForbidden();
        $auth['channel_name'] = 'private-inquiry.'.$i->id.'.v2';
        $this->postJson('/broadcasting/auth', $auth)->assertOk();
        [$message2] = app(MessageService::class)->send($customer, $i->fresh(), $this->message());
        app(UpdateUserStatus::class)->handle($admin, $customer, false);
        (new BroadcastInquiryMessage($message2->id, 2))->handle(app(InquiryLock::class));
        Event::assertDispatched(InquiryMessageCreated::class, 1);
        $this->postJson('/broadcasting/auth', $auth)->assertForbidden();
        $this->assertSame(3, $i->fresh()->access_version);
        $this->actingAs($customer)->postJson('/yeu-cau/'.$i->id.'/tin-nhan', $this->message())->assertForbidden();
    }

    public function test_enqueue_failure_rolls_back_message_intent_and_sales_state_then_retry_recovers(): void
    {
        $sales = $this->user(UserRole::Sales);
        $i = $this->inquiry($this->user(), $sales);
        $data = $this->message();
        $armed = true;
        DB::listen(function ($query) use (&$armed): void {
            if ($armed && preg_match('/^insert into [`"]jobs[`"]/', $query->sql)) {
                $armed = false;
                throw new \RuntimeException('Injected failure after jobs insert, before commit');
            }
        });
        try {
            app(MessageService::class)->send($sales, $i, $data);
            $this->fail('Enqueue failure must propagate');
        } catch (\RuntimeException $e) {
            $this->assertSame('Injected failure after jobs insert, before commit', $e->getMessage());
        }
        $this->assertFalse($armed);
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(InquiryStatus::Assigned, $i->fresh()->status);
        [$message, $created] = app(MessageService::class)->send($sales, $i, $data);
        $this->assertTrue($created);
        [$retry, $created] = app(MessageService::class)->send($sales, $i, $data);
        $this->assertFalse($created);
        $this->assertSame($message->id, $retry->id);
        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_queue_intent_is_identifiers_only_and_bypasses_after_commit_deferral(): void
    {
        config(['queue.connections.database.after_commit' => true]);
        $customer = $this->user();
        $i = $this->inquiry($customer, $this->user(UserRole::Sales));
        Event::fake([InquiryMessageCreated::class]);
        DB::beginTransaction();
        try {
            [$message] = app(MessageService::class)->send($customer, $i, $this->message());
            $row = DB::table('jobs')->sole();
            $payload = json_decode($row->payload, true, flags: JSON_THROW_ON_ERROR);
            $job = unserialize($payload['data']['command']);
            $this->assertInstanceOf(BroadcastInquiryMessage::class, $job);
            $this->assertSame($message->id, $job->messageId);
            $this->assertSame(1, $job->version);
            $this->assertStringNotContainsString($message->content, $row->payload);
            $this->assertStringNotContainsString('PRIVATE-NOTE', $row->payload);
            Event::assertNotDispatched(InquiryMessageCreated::class);
        } finally {
            DB::rollBack();
        }
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_worker_can_retry_failed_job_without_recreating_message_or_http_retry_intent(): void
    {
        $customer = $this->user();
        $i = $this->inquiry($customer, $this->user(UserRole::Sales));
        $data = $this->message();
        [$message] = app(MessageService::class)->send($customer, $i, $data);
        $queue = app('queue')->connection('database');
        $lock = app(InquiryLock::class);
        $failingLock = \Mockery::mock(InquiryLock::class);
        $failingLock->shouldReceive('run')->once()->andThrow(new \RuntimeException('Injected worker failure'));
        $this->app->instance(InquiryLock::class, $failingLock);
        $job = $queue->pop();
        try {
            $job->fire();
            $this->fail('Worker failure must propagate');
        } catch (\RuntimeException $e) {
            $this->assertSame('Injected worker failure', $e->getMessage());
            $job->release(0);
        } finally {
            $this->app->instance(InquiryLock::class, $lock);
        }
        Event::fake([InquiryMessageCreated::class]);
        $retry = $queue->pop();
        $this->assertSame(2, $retry->attempts());
        $retry->fire();
        $retry->delete();
        Event::assertDispatched(InquiryMessageCreated::class, 1);
        [$existing, $created] = app(MessageService::class)->send($customer, $i, $data);
        $this->assertFalse($created);
        $this->assertSame($message->id, $existing->id);
        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_non_database_queue_configuration_fails_closed_without_persisting_message(): void
    {
        config(['queue.connections.database.driver' => 'sync']);
        $customer = $this->user();
        $i = $this->inquiry($customer, $this->user(UserRole::Sales));
        try {
            app(MessageService::class)->send($customer, $i, $this->message());
            $this->fail('Non-atomic queue must not be accepted');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('database queue', $e->getMessage());
        }
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_database_queue_on_a_different_connection_fails_closed(): void
    {
        config(['database.connections.tv4_other' => config('database.connections.'.config('database.default')),
            'queue.connections.database.connection' => 'tv4_other']);
        $customer = $this->user();
        $i = $this->inquiry($customer, $this->user(UserRole::Sales));
        try {
            app(MessageService::class)->send($customer, $i, $this->message());
            $this->fail('A second connection cannot participate in the message transaction');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('message connection', $e->getMessage());
        }
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_publication_holds_gate_but_no_row_transaction_and_stale_job_is_dropped(): void
    {
        $admin = $this->user(UserRole::Admin);
        $customer = $this->user();
        $i = $this->inquiry($customer, $this->user(UserRole::Sales));
        [$message] = app(MessageService::class)->send($customer, $i, $this->message());
        $published = 0;
        Event::listen(InquiryMessageCreated::class, function () use ($admin, $customer, &$published): void {
            $published++;
            $this->assertSame(0, DB::transactionLevel());
            try {
                app(UpdateUserStatus::class)->handle($admin, $customer, false);
                $this->fail('Nested revocation must not cross the publication gate');
            } catch (\LogicException $e) {
                $this->assertStringContainsString('Publication gate', $e->getMessage());
            }
        });
        (new BroadcastInquiryMessage($message->id, 1))->handle(app(InquiryLock::class));
        $this->assertSame(1, $published);
        $this->assertTrue($customer->fresh()->is_active);
        app(UpdateUserStatus::class)->handle($admin, $customer, false);
        $this->assertSame(2, $i->fresh()->access_version);
        (new BroadcastInquiryMessage($message->id, 1))->handle(app(InquiryLock::class));
        $this->assertSame(1, $published);
    }

    public function test_revocation_and_reassignment_reject_an_existing_outer_transaction(): void
    {
        $admin = $this->user(UserRole::Admin);
        $customer = $this->user();
        $sales = $this->user(UserRole::Sales);
        $i = $this->inquiry($customer, null, 'new');
        DB::beginTransaction();
        try {
            foreach ([fn () => app(UpdateUserStatus::class)->handle($admin, $customer, false),
                fn () => app(InquiryService::class)->update($admin, $i, 'assign', ['assigned_to' => $sales->id])] as $operation) {
                try {
                    $operation();
                    $this->fail('Gate must precede row transactions');
                } catch (\LogicException $e) {
                    $this->assertStringContainsString('outermost', $e->getMessage());
                }
            }
        } finally {
            DB::rollBack();
        }
        $this->assertTrue($customer->fresh()->is_active);
        $this->assertSame(1, $i->fresh()->access_version);
    }
}
