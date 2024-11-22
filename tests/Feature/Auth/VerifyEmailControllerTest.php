<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VerifyEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_com_email_verificado_e_redirecionado():void
    {
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        $this->actingAs($user);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(value: 1), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $response = $this->get($url);

        $response->assertRedirect(route('dashboard') . '?verified=1');
    }

    public function test_usuario_consegue_verificar_email(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        $this->actingAs($user);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(1), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $response = $this->get($url);

        $response->assertRedirect(route('dashboard') . '?verified=1');

        $this->assertNotNull($user->fresh()->email_verified_at);

        Event::assertDispatched(Verified::class);
    }
}
