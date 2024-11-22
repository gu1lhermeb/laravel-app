<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisteredUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_de_registro_esta_sendo_exibida(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    public function test_novo_usuario_consegue_se_registrar():void
    {
        Event::fake();

        $response = $this->post('/register', [
            'name' => 'Usuário de teste',
            'email' => 'teste@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Usuário de teste',
            'email' => 'teste@gmail.com',
        ]);

        Event::assertDispatched(Registered::class);
    }
}
