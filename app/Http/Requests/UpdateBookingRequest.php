<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
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
            'faciaName' => ['nullable', 'string', 'max:255'],
            'trophyName' => ['nullable', 'string', 'max:255'],
            'companyLogo' => ['nullable', 'array'],
            'companyLogo.path' => ['nullable', 'string'],
            'companyLogo.filename' => ['nullable', 'string'],
            'contactPerson' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'phoneCode' => ['required', 'string', 'max:10'],
            'phoneNumber' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'billingAddress' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:255'],
            'customCity' => ['required_if:city,Others', 'nullable', 'string', 'max:255'],
            'gstNumber' => ['nullable', 'string', 'max:15'],
            'productProfile' => ['required', 'array', 'min:1'],
            'productProfile.*' => ['string'],
            'hasExhibitedBefore' => ['boolean'],
            'participationYears' => ['nullable', 'array'],
            'participationYears.*' => ['string'],
            'isSgcciMember' => ['boolean'],
            'membershipType' => ['nullable', 'string'],
            'membershipNumber' => ['required_with:membershipType', 'string', 'max:100'],
            'spaceType' => ['required', 'string', 'in:standard,raw'],
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
            'address.required' => 'Please enter your address.',
            'billingAddress.required' => 'Please enter your billing address.',
            'city.required' => 'Please select a city.',
            'customCity.required_if' => 'Please enter your city name.',
            'productProfile.required' => 'Please select at least one product profile.',
            'productProfile.min' => 'Please select at least one product profile.',
            'spaceType.required' => 'Please select a space type.',
            'spaceType.in' => 'Invalid space type selected.',
            'membershipNumber.required_with' => 'Please enter your membership number.',
        ];
    }
}
