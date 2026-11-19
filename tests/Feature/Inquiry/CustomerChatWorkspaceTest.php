<?php

namespace Tests\Feature\Inquiry;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerChatWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function inquiry(User $owner, array $extra = []): Inquiry
    {
        return Inquiry::create(array_merge([
            'customer_id' => $owner->id, 'contact_name' => $owner->name,
            'contact_phone' => '0901234567', 'content' => 'Tìm hiểu xe Toyota cho gia đình',
            'status' => InquiryStatus::New,
        ], $extra));
    }

    public function test_customer_workspace_scopes_selection_search_and_private_fields(): void
    {
        $user = User::factory()->create();
        $mine = $this->inquiry($user, ['internal_note' => 'INTERNAL-DO-NOT-EXPOSE']);
        $other = $this->inquiry(User::factory()->create(), ['content' => 'OTHER-CUSTOMER-PRIVATE-CONTENT']);
        $this->actingAs($user)->get('/tai-khoan/tin-nhan')->assertOk()
            ->assertSee($mine->content)->assertDontSee('INTERNAL-DO-NOT-EXPOSE')
            ->assertDontSee('OTHER-CUSTOMER-PRIVATE-CONTENT')->assertSee('data-inquiry-chat', false)
            ->assertDontSee('data-chat-form', false);
        $this->get('/tai-khoan/tin-nhan?conversation='.$other->id)->assertNotFound();
        $this->get('/tai-khoan/tin-nhan?q=OTHER-CUSTOMER')->assertOk()->assertSee('Không có cuộc tư vấn phù hợp.');
    }

    public function test_assigned_inquiry_has_real_composer_but_closed_is_readonly(): void
    {
        $user = User::factory()->create();
        $mine = $this->inquiry($user, ['status' => InquiryStatus::Assigned]);
        $this->actingAs($user)->get('/tai-khoan/tin-nhan?conversation='.$mine->id)->assertOk()
            ->assertSee('name="content"', false)->assertSee('maxlength="1000"', false)
            ->assertDontSee('Bản xem trước giao diện');
        $mine->update(['status' => InquiryStatus::Closed]);
        $this->get('/tai-khoan/tin-nhan?conversation='.$mine->id)->assertOk()
            ->assertSee('Cuộc tư vấn đã kết thúc.')->assertDontSee('data-chat-form', false);
    }
}
