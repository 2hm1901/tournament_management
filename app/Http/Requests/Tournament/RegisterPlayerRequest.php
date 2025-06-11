<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;
use App\Domain\Tournament\Models\TournamentParticipant;

class RegisterPlayerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'player_id' => 'required|integer|exists:players,id',
            'team_id' => 'nullable|integer|exists:teams,id',
            'seed' => 'nullable|integer|min:1|max:256',
            'notes' => 'nullable|string|max:1000',
            'payment_status' => 'nullable|string|in:' . implode(',', [
                TournamentParticipant::PAYMENT_PENDING,
                TournamentParticipant::PAYMENT_PAID,
                TournamentParticipant::PAYMENT_FAILED,
                TournamentParticipant::PAYMENT_REFUNDED,
            ]),
            'emergency_contact' => 'nullable|array',
            'emergency_contact.name' => 'required_with:emergency_contact|string|max:255',
            'emergency_contact.phone' => 'required_with:emergency_contact|string|max:20',
            'emergency_contact.relationship' => 'nullable|string|max:100',
            'emergency_contact.email' => 'nullable|email|max:255',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'player_id.required' => 'Player selection is required',
            'player_id.exists' => 'Selected player does not exist',
            'team_id.exists' => 'Selected team does not exist',
            'seed.min' => 'Seed must be at least 1',
            'seed.max' => 'Seed cannot exceed 256',
            'notes.max' => 'Notes cannot exceed 1000 characters',
            'payment_status.in' => 'Invalid payment status',
            'emergency_contact.name.required_with' => 'Emergency contact name is required when providing contact info',
            'emergency_contact.phone.required_with' => 'Emergency contact phone is required when providing contact info',
            'emergency_contact.email.email' => 'Emergency contact email must be valid',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'player_id' => 'player',
            'team_id' => 'team',
            'payment_status' => 'payment status',
            'emergency_contact.name' => 'emergency contact name',
            'emergency_contact.phone' => 'emergency contact phone',
            'emergency_contact.relationship' => 'emergency contact relationship',
            'emergency_contact.email' => 'emergency contact email',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation logic can be added here
            // For example, checking if player is already registered
        });
    }
} 