<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Enlace de verificación que llega por correo: funciona aunque el navegador no tenga la sesión abierta.
 */
class VerificarCorreoTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_signed_link_verifies_the_email_and_opens_the_spa(): void
    {
        Event::fake();
        $user = User::factory()->unverified()->create();

        $this->get($this->enlace($user, sha1($user->email)))
            ->assertRedirect(config('app.frontend_url').'/contactos?verificado=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_a_wrong_hash_does_not_verify_the_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->enlace($user, sha1('otro@example.com')))
            ->assertRedirect(config('app.frontend_url').'/contactos?verificado=0');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_a_tampered_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->enlace($user, sha1($user->email)).'x')->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    private function enlace(User $user, string $hash): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => $hash]);
    }
}
