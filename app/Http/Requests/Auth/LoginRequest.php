<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    private $auth;
    private $rateLimiter;
    private $translator;
    private $validator;

    public function __construct(
        AuthFactory $auth,
        CacheRateLimiter $rateLimiter,
        Translator $translator,
        ValidationFactory $validator
    ) {
        parent::__construct();
        $this->auth = $auth;
        $this->rateLimiter = $rateLimiter;
        $this->translator = $translator;
        $this->validator = $validator;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (!$this->auth->guard()->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $this->rateLimiter->hit($this->throttleKey());

            throw $this->validator->make([], [])->messages([
                'email' => $this->translator->get('auth.failed'),
            ]);
        }

        $this->rateLimiter->clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!$this->rateLimiter->tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = $this->rateLimiter->availableIn($this->throttleKey());

        throw $this->validator->make([], [])->messages([
            'email' => $this->translator->get('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
