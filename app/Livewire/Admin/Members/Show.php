<?php

namespace App\Livewire\Admin\Members;

use App\Models\Member;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Member $member;

    // Basic
    #[Validate('required|string|max:50')]
    public string $membershipNumber = '';

    #[Validate('required|string|max:255')]
    public string $contactName = '';

    #[Validate('nullable|string|max:255')]
    public string $company = '';

    // Address A
    #[Validate('nullable|string|max:100')]
    public string $addressTypeA = '';

    #[Validate('nullable|string|max:255')]
    public string $address1A = '';

    #[Validate('nullable|string|max:255')]
    public string $address2A = '';

    #[Validate('nullable|string|max:100')]
    public string $areaA = '';

    #[Validate('nullable|string|max:100')]
    public string $cityA = '';

    #[Validate('nullable|string|max:100')]
    public string $stateA = '';

    #[Validate('nullable|string|max:20')]
    public string $pincodeA = '';

    // Address B
    #[Validate('nullable|string|max:100')]
    public string $addressTypeB = '';

    #[Validate('nullable|string|max:255')]
    public string $address1B = '';

    #[Validate('nullable|string|max:255')]
    public string $address2B = '';

    #[Validate('nullable|string|max:100')]
    public string $areaB = '';

    #[Validate('nullable|string|max:100')]
    public string $cityB = '';

    #[Validate('nullable|string|max:100')]
    public string $stateB = '';

    #[Validate('nullable|string|max:20')]
    public string $pincodeB = '';

    // Contact
    #[Validate('nullable|string|max:100')]
    public string $officePhone = '';

    #[Validate('nullable|string|max:100')]
    public string $homePhone = '';

    #[Validate('nullable|string|max:100')]
    public string $cellNo = '';

    #[Validate('nullable|string|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:255')]
    public string $web = '';

    // Membership
    #[Validate('nullable|string|max:100')]
    public string $post = '';

    #[Validate('nullable|string|max:100')]
    public string $type = '';

    // Personal
    #[Validate('nullable|string|max:50')]
    public string $dob = '';

    #[Validate('nullable|string|max:20')]
    public string $aadharNo = '';

    #[Validate('nullable|string|max:20')]
    public string $panNo = '';

    #[Validate('nullable|string|max:20')]
    public string $gstNo = '';

    // Business
    #[Validate('nullable|string|max:255')]
    public string $natureOfBusiness = '';

    #[Validate('nullable|string|max:255')]
    public string $businessSegment = '';

    #[Validate('nullable|string|max:100')]
    public string $turnOver = '';

    #[Validate('nullable|string|max:100')]
    public string $scaleOfBusiness = '';

    // Family
    #[Validate('nullable|string|max:255')]
    public string $spouseName = '';

    #[Validate('nullable|string|max:100')]
    public string $spousePhoneNo = '';

    #[Validate('nullable|string|max:20')]
    public string $bloodGroup = '';

    public bool $showDeleteModal = false;

    public function mount(Member $member): void
    {
        $this->member = $member;
        $this->fill([
            'membershipNumber' => $member->membership_number ?? '',
            'contactName' => $member->contact_name ?? '',
            'company' => $member->company ?? '',
            'addressTypeA' => $member->address_type_a ?? '',
            'address1A' => $member->address1_a ?? '',
            'address2A' => $member->address2_a ?? '',
            'areaA' => $member->area_a ?? '',
            'cityA' => $member->city_a ?? '',
            'stateA' => $member->state_a ?? '',
            'pincodeA' => $member->pincode_a ?? '',
            'addressTypeB' => $member->address_type_b ?? '',
            'address1B' => $member->address1_b ?? '',
            'address2B' => $member->address2_b ?? '',
            'areaB' => $member->area_b ?? '',
            'cityB' => $member->city_b ?? '',
            'stateB' => $member->state_b ?? '',
            'pincodeB' => $member->pincode_b ?? '',
            'officePhone' => $member->office_phone ?? '',
            'homePhone' => $member->home_phone ?? '',
            'cellNo' => $member->cell_no ?? '',
            'email' => $member->email ?? '',
            'web' => $member->web ?? '',
            'post' => $member->post ?? '',
            'type' => $member->type ?? '',
            'dob' => $member->dob ?? '',
            'aadharNo' => $member->aadhar_no ?? '',
            'panNo' => $member->pan_no ?? '',
            'gstNo' => $member->gst_no ?? '',
            'natureOfBusiness' => $member->nature_of_business ?? '',
            'businessSegment' => $member->business_segment ?? '',
            'turnOver' => $member->turn_over ?? '',
            'scaleOfBusiness' => $member->scale_of_business ?? '',
            'spouseName' => $member->spouse_name ?? '',
            'spousePhoneNo' => $member->spouse_phone_no ?? '',
            'bloodGroup' => $member->blood_group ?? '',
        ]);
    }

    public function save(): void
    {
        $this->validate();

        $this->member->update([
            'membership_number' => $this->membershipNumber,
            'contact_name' => $this->contactName,
            'company' => $this->company ?: null,
            'address_type_a' => $this->addressTypeA ?: null,
            'address1_a' => $this->address1A ?: null,
            'address2_a' => $this->address2A ?: null,
            'area_a' => $this->areaA ?: null,
            'city_a' => $this->cityA ?: null,
            'state_a' => $this->stateA ?: null,
            'pincode_a' => $this->pincodeA ?: null,
            'address_type_b' => $this->addressTypeB ?: null,
            'address1_b' => $this->address1B ?: null,
            'address2_b' => $this->address2B ?: null,
            'area_b' => $this->areaB ?: null,
            'city_b' => $this->cityB ?: null,
            'state_b' => $this->stateB ?: null,
            'pincode_b' => $this->pincodeB ?: null,
            'office_phone' => $this->officePhone ?: null,
            'home_phone' => $this->homePhone ?: null,
            'cell_no' => $this->cellNo ?: null,
            'email' => $this->email ?: null,
            'web' => $this->web ?: null,
            'post' => $this->post ?: null,
            'type' => $this->type ?: null,
            'dob' => $this->dob ?: null,
            'aadhar_no' => $this->aadharNo ?: null,
            'pan_no' => $this->panNo ?: null,
            'gst_no' => $this->gstNo ?: null,
            'nature_of_business' => $this->natureOfBusiness ?: null,
            'business_segment' => $this->businessSegment ?: null,
            'turn_over' => $this->turnOver ?: null,
            'scale_of_business' => $this->scaleOfBusiness ?: null,
            'spouse_name' => $this->spouseName ?: null,
            'spouse_phone_no' => $this->spousePhoneNo ?: null,
            'blood_group' => $this->bloodGroup ?: null,
        ]);

        Flux::toast(heading: 'Member Updated!', variant: 'success', text: 'Member details have been saved successfully.');
    }

    public function deleteMember(): void
    {
        $this->member->delete();

        $this->redirectRoute('admin.members.index', navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.members.show');
    }
}
