<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
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
            'brandName' => ['required', 'string', 'max:255'],
            'contactPerson' => ['required', 'string', 'max:255'],
            'phoneCode' => ['required', 'string', 'max:10'],
            'phoneNumber' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'gstNumber' => ['nullable', 'string', 'max:15'],
            'productProfile' => ['required', 'array', 'min:1'],
            'productProfile.*' => ['string'],
            'hasExhibitedBefore' => ['boolean'],
            'participationYears' => ['nullable', 'array'],
            'participationYears.*' => ['string'],
            'isSgcciMember' => ['boolean'],
            'membershipType' => ['nullable', 'string'],
            'selectedStalls' => ['required', 'array', 'min:1'],
            'selectedStalls.*' => ['string'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'brandName.required' => 'Please enter your brand or dealership name.',
            'contactPerson.required' => 'Please enter the contact person name.',
            'phoneNumber.required' => 'Please enter a phone number.',
            'email.required' => 'Please enter an email address.',
            'email.email' => 'Please enter a valid email address.',
            'city.required' => 'Please select a city.',
            'productProfile.required' => 'Please select at least one product profile.',
            'productProfile.min' => 'Please select at least one product profile.',
            'selectedStalls.required' => 'Please select at least one stall.',
            'selectedStalls.min' => 'Please select at least one stall.',
        ];
    }
}
