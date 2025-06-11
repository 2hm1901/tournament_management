<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;
use App\Domain\Tournament\Models\Tournament;

class TournamentFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => 'nullable|string|in:' . implode(',', [
                Tournament::TYPE_MEN_SINGLES,
                Tournament::TYPE_WOMEN_SINGLES,
                Tournament::TYPE_MEN_DOUBLES,
                Tournament::TYPE_WOMEN_DOUBLES,
                Tournament::TYPE_MIXED_DOUBLES,
            ]),
            'status' => 'nullable|string|in:' . implode(',', [
                Tournament::STATUS_DRAFT,
                Tournament::STATUS_REGISTRATION_OPEN,
                Tournament::STATUS_REGISTRATION_CLOSED,
                Tournament::STATUS_IN_PROGRESS,
                Tournament::STATUS_COMPLETED,
                Tournament::STATUS_CANCELLED,
            ]),
            'organizer_id' => 'nullable|integer|exists:users,id',
            'search' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'has_open_registration' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'venue' => 'nullable|string|max:255',
            'min_participants' => 'nullable|integer|min:2|max:256',
            'max_participants' => 'nullable|integer|min:2|max:256|gte:min_participants',
            'entry_fee_min' => 'nullable|numeric|min:0',
            'entry_fee_max' => 'nullable|numeric|min:0|gte:entry_fee_min',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:name,created_at,tournament_start_date,entry_fee,max_participants',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'with' => 'nullable|array',
            'with.*' => 'string|in:organizer,participants,participants.player,participants.team,matches',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Invalid tournament type',
            'status.in' => 'Invalid tournament status',
            'organizer_id.exists' => 'Organizer not found',
            'search.max' => 'Search query cannot exceed 255 characters',
            'date_to.after_or_equal' => 'End date must be after or equal to start date',
            'max_participants.gte' => 'Maximum participants must be greater than or equal to minimum',
            'entry_fee_max.gte' => 'Maximum entry fee must be greater than or equal to minimum',
            'per_page.max' => 'Per page cannot exceed 100 items',
            'sort_by.in' => 'Invalid sort field',
            'sort_direction.in' => 'Sort direction must be asc or desc',
            'with.*.in' => 'Invalid relationship to include',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'organizer_id' => 'organizer',
            'date_from' => 'start date',
            'date_to' => 'end date',
            'has_open_registration' => 'open registration filter',
            'is_featured' => 'featured filter',
            'min_participants' => 'minimum participants',
            'max_participants' => 'maximum participants',
            'entry_fee_min' => 'minimum entry fee',
            'entry_fee_max' => 'maximum entry fee',
            'per_page' => 'items per page',
            'sort_by' => 'sort field',
            'sort_direction' => 'sort direction',
        ];
    }
} 