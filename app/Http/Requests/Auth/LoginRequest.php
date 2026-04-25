<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Company;
use App\Models\User;

class LoginRequest extends FormRequest
{
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_code' => ['nullable', 'string', 'max:20'],
            'username' => ['required', 'string', 'max:50'],
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

        $mode = $this->input('mode', 'company');

        if ($mode === 'master') {
            if (! Auth::attempt($this->only('username', 'password'), $this->boolean('remember'))) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'username' => trans('auth.failed'),
                ]);
            }
        } else {
            $companyCode = $this->normalizeCnpj((string) $this->input('company_code', ''));
            if ($companyCode === '') {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'company_code' => 'Informe o CNPJ ou CPF para acessar como equipe.',
                ]);
            }

            $company = Company::where('cnpj', $companyCode)->first();
            if (! $company) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'company_code' => 'CNPJ ou CPF inválido.',
                ]);
            }

            $user = User::where('username', (string) $this->input('username'))
                ->whereHas('companies', fn ($query) => $query->whereKey($company->id))
                ->first();

            if (! $user || ! Hash::check((string) $this->input('password'), $user->password)) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'username' => trans('auth.failed'),
                ]);
            }

            Auth::login($user, $this->boolean('remember'));
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
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
        $mode = $this->input('mode', 'company');
        $companyCode = $mode === 'company' ? $this->normalizeCnpj((string) $this->input('company_code', '')) : '';

        return Str::transliterate(Str::lower($this->string('username')).'|'.$companyCode.'|'.$this->ip());
    }

    private function normalizeCnpj(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }
}
