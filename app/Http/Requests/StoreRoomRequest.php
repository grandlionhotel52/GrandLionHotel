<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'view_type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'room_status_id' => ['nullable', 'integer', 'exists:room_status,room_status_id'],
            'image' => [
                'nullable',
                'string',
                'max:2048',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    $image = trim((string) $value);
                    $isRemoteUrl = filter_var($image, FILTER_VALIDATE_URL) !== false;
                    $isManagedUpload = preg_match('#^room-images/[A-Za-z0-9._/-]+$#', $image) === 1;

                    if (! $isRemoteUrl && ! $isManagedUpload) {
                        $fail('The image must be a valid URL or a managed room upload.');
                    }
                },
            ],
            'image_upload' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
