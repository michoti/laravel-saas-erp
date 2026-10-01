<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $company = '';
    public string $interest = 'pos';
    public string $message = '';
    public bool $sent = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            // Kenyan mobile: 07xx / 01xx / +2547xx / 2547xx
            'phone' => ['required', 'regex:/^(?:\+?254|0)[17]\d{8}$/'],
            'company' => ['nullable', 'string', 'max:100'],
            'interest' => ['required', 'in:pos,inventory,crm,not-sure'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'phone.regex' => 'Enter a Kenyan mobile number, for example 0712 345 678.',
            'name.required' => 'Tell us your name so we know who to ask for.',
        ];
    }

    public function updated(string $property): void
    {
        $this->validateOnly($property); // real-time, field-level feedback
    }

    public function submit(): void
    {
        $key = 'demo-request:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('phone', 'Too many requests. Please try again in a few minutes.');
            return;
        }

        $data = $this->validate();
        RateLimiter::hit($key, 600);

        // TODO: persist on the central connection and/or notify sales, e.g. Mail::to(...)->send(new DemoRequested($data)).
        Log::info('Demo request', $data);

        $this->sent = true;
        $this->dispatch('notify', message: 'Asante! We will contact you within one business day.');
    }

    public function again(): void
    {
        $this->reset();
        $this->resetValidation();
    }
};
