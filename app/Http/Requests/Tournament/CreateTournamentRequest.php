<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;
use App\Domain\Tournament\Models\Tournament;

class CreateTournamentRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'type' => 'required|string|in:' . implode(',', [
                Tournament::TYPE_MEN_SINGLES,
                Tournament::TYPE_WOMEN_SINGLES,
                Tournament::TYPE_MEN_DOUBLES,
                Tournament::TYPE_WOMEN_DOUBLES,
                Tournament::TYPE_MIXED_DOUBLES,
            ]),
            'format' => 'required|string|in:' . implode(',', [
                Tournament::FORMAT_SINGLE_ELIMINATION,
                Tournament::FORMAT_DOUBLE_ELIMINATION,
                Tournament::FORMAT_ROUND_ROBIN,
                Tournament::FORMAT_SWISS,
            ]),
            'max_participants' => 'required|integer|min:4|max:256',
            'min_participants' => 'required|integer|min:2|max:128',
            'registration_start_date' => 'nullable|date|after_or_equal:now',
            'registration_end_date' => 'nullable|date|after:registration_start_date',
            'tournament_start_date' => 'nullable|date|after:registration_end_date',
            'tournament_end_date' => 'nullable|date|after:tournament_start_date',
            'entry_fee' => 'nullable|numeric|min:0|max:999999.99',
            'venue' => 'nullable|string|max:500',
            'rules' => 'nullable|array',
            'rules.*' => 'string|max:1000',
            'prizes' => 'nullable|array',
            'prizes.*.position' => 'required_with:prizes|integer|min:1',
            'prizes.*.amount' => 'required_with:prizes|numeric|min:0',
            'prizes.*.description' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
            'settings.min_skill_level' => 'nullable|integer|min:0|max:3000',
            'settings.max_skill_level' => 'nullable|integer|min:0|max:3000|gte:settings.min_skill_level',
            'settings.min_age' => 'nullable|integer|min:5|max:100',
            'settings.max_age' => 'nullable|integer|min:5|max:100|gte:settings.min_age',
            'settings.allow_late_registration' => 'nullable|boolean',
            'settings.require_payment' => 'nullable|boolean',
            'settings.auto_seeding' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tournament name is required',
            'name.max' => 'Tournament name cannot exceed 255 characters',
            'description.required' => 'Tournament description is required',
            'description.max' => 'Tournament description cannot exceed 2000 characters',
            'type.required' => 'Tournament type is required',
            'type.in' => 'Invalid tournament type selected',
            'format.required' => 'Tournament format is required',
            'format.in' => 'Invalid tournament format selected',
            'max_participants.required' => 'Maximum participants is required',
            'max_participants.min' => 'Maximum participants must be at least 4',
            'max_participants.max' => 'Maximum participants cannot exceed 256',
            'min_participants.required' => 'Minimum participants is required',
            'min_participants.min' => 'Minimum participants must be at least 2',
            'min_participants.max' => 'Minimum participants cannot exceed 128',
            'registration_start_date.after_or_equal' => 'Registration start date cannot be in the past',
            'registration_end_date.after' => 'Registration end date must be after start date',
            'tournament_start_date.after' => 'Tournament start date must be after registration end date',
            'tournament_end_date.after' => 'Tournament end date must be after start date',
            'entry_fee.numeric' => 'Entry fee must be a valid number',
            'entry_fee.min' => 'Entry fee cannot be negative',
            'entry_fee.max' => 'Entry fee cannot exceed 999,999.99',
            'venue.max' => 'Venue description cannot exceed 500 characters',
            'settings.max_skill_level.gte' => 'Maximum skill level must be greater than or equal to minimum',
            'settings.max_age.gte' => 'Maximum age must be greater than or equal to minimum age',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Add organizer_id from authenticated user
        $this->merge([
            'organizer_id' => auth()->id(),
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'max_participants' => 'maximum participants',
            'min_participants' => 'minimum participants',
            'registration_start_date' => 'registration start date',
            'registration_end_date' => 'registration end date',
            'tournament_start_date' => 'tournament start date',
            'tournament_end_date' => 'tournament end date',
            'entry_fee' => 'entry fee',
            'settings.min_skill_level' => 'minimum skill level',
            'settings.max_skill_level' => 'maximum skill level',
            'settings.min_age' => 'minimum age',
            'settings.max_age' => 'maximum age',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation for min/max participants
            if ($this->min_participants >= $this->max_participants) {
                $validator->errors()->add(
                    'min_participants', 
                    'Minimum participants must be less than maximum participants'
                );
            }

            // Validate tournament type and format compatibility
            $this->validateTypeFormatCompatibility($validator);
        });
    }

    /**
     * Validate tournament type and format compatibility.
     */
    private function validateTypeFormatCompatibility($validator): void
    {
        if (!$this->type || !$this->format) {
            return;
        }

        $validCombinations = [
            Tournament::TYPE_MEN_SINGLES => [
                Tournament::FORMAT_SINGLE_ELIMINATION,
                Tournament::FORMAT_DOUBLE_ELIMINATION,
                Tournament::FORMAT_ROUND_ROBIN,
                Tournament::FORMAT_SWISS
            ],
            Tournament::TYPE_WOMEN_SINGLES => [
                Tournament::FORMAT_SINGLE_ELIMINATION,
                Tournament::FORMAT_DOUBLE_ELIMINATION,
                Tournament::FORMAT_ROUND_ROBIN,
                Tournament::FORMAT_SWISS
            ],
            Tournament::TYPE_MEN_DOUBLES => [
                Tournament::FORMAT_SINGLE_ELIMINATION,
                Tournament::FORMAT_DOUBLE_ELIMINATION,
                Tournament::FORMAT_ROUND_ROBIN
            ],
            Tournament::TYPE_WOMEN_DOUBLES => [
                Tournament::FORMAT_SINGLE_ELIMINATION,
                Tournament::FORMAT_DOUBLE_ELIMINATION,
                Tournament::FORMAT_ROUND_ROBIN
            ],
            Tournament::TYPE_MIXED_DOUBLES => [
                Tournament::FORMAT_SINGLE_ELIMINATION,
                Tournament::FORMAT_DOUBLE_ELIMINATION,
                Tournament::FORMAT_ROUND_ROBIN
            ],
        ];

        if (!isset($validCombinations[$this->type]) || 
            !in_array($this->format, $validCombinations[$this->type])) {
            $validator->errors()->add(
                'format', 
                "The selected format is not compatible with {$this->type} tournament type"
            );
        }
    }
} 