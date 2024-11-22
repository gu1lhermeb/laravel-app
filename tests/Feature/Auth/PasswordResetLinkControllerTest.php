<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PasswordResetLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function exibe_a_pagina_de_esquecimento_de_senha()
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);

        $response->assertViewIs('auth.forgot-password');
    }

    #[Test]
    public function envia_um_link_de_redefinicao_de_senha_para_um_email_valido()
    {
        $user = \App\Models\User::factory()->create([
            'email' => 'test@example.com',
        ]);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $user->email])
            ->andReturn(Password::RESET_LINK_SENT);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertStatus(302);

        $response->assertSessionHas('status', trans(Password::RESET_LINK_SENT));
    }

    #[Test]
    public function retorna_erro_se_o_email_for_invalido()
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'invalid@example.com'])
            ->andReturn(Password::INVALID_USER);

        $response = $this->post(route('password.email'), [
            'email' => 'invalid@example.com',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['email' => trans(Password::INVALID_USER)]);
    }

    #[Test]
    public function valida_o_campo_email()
    {
        $response = $this->post(route('password.email'), [
            'email' => '',
        ]);

        $response->assertStatus(302);
        
        $response->assertSessionHasErrors(['email']);
    }
}
