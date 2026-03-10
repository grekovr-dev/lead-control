<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_lead_and_redirects_back_with_success_message(): void
    {
        $response = $this->from('/')->post(route('leads.store'), [
            'name' => 'John Doe',
            'phone' => '+380501112233',
            'comment' => 'Please call me tomorrow.',
        ]);

        $response
            ->assertRedirectContains('#lead-form')
            ->assertSessionHas('success', 'Заявка отправлена. Мы свяжемся с вами в ближайшее время.');

        $this->assertDatabaseHas('leads', [
            'name' => 'John Doe',
            'phone' => '+380501112233',
            'comment' => 'Please call me tomorrow.',
            'status' => 'new',
        ]);
    }

    public function test_it_does_not_create_lead_and_returns_validation_error_when_phone_is_missing(): void
    {
        $response = $this->from('/')->post(route('leads.store'), [
            'name' => 'John Doe',
            'comment' => 'No phone provided.',
        ]);

        $response
            ->assertRedirectContains('#lead-form')
            ->assertSessionHasErrors(['phone']);

        $this->assertDatabaseCount('leads', 0);
    }
}
