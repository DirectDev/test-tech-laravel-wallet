<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreateRecurringTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'stop_date' => [
                'nullable',
                'date',
                'after:start_date',
            ],
            'frequency' => [
                'required',
                'integer',
                'min:1',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}

