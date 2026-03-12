<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.members.index')" wire:navigate>Back</flux:button>
        <div class="flex-1">
            <flux:heading size="xl">{{ $member->contact_name }}</flux:heading>
            <flux:text class="text-zinc-500">
                <flux:badge color="zinc" size="sm">{{ $member->membership_number }}</flux:badge>
                @if ($member->company)
                    <span class="ml-2">{{ $member->company }}</span>
                @endif
            </flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button variant="primary" wire:click="save">Save Changes</flux:button>
            <flux:button variant="ghost" icon="trash" color="red" wire:click="$set('showDeleteModal', true)">Delete</flux:button>
        </div>
    </div>

    <form wire:submit="save">
        <flux:tabs variant="pills">
            {{-- Basic Info Tab --}}
            <flux:tab name="basic" icon="identification">Basic Info</flux:tab>

            {{-- Addresses Tab --}}
            <flux:tab name="addresses" icon="map-pin">Addresses</flux:tab>

            {{-- Contact Tab --}}
            <flux:tab name="contact" icon="phone">Contact</flux:tab>

            {{-- Membership Tab --}}
            <flux:tab name="membership" icon="identification">Membership</flux:tab>

            {{-- Personal Tab --}}
            <flux:tab name="personal" icon="user">Personal</flux:tab>

            {{-- Business Tab --}}
            <flux:tab name="business" icon="building-office-2">Business</flux:tab>

            {{-- Family Tab --}}
            <flux:tab name="family" icon="heart">Family</flux:tab>

            {{-- Basic Info Panel --}}
            <flux:tab.panel name="basic">
                <flux:card class="mt-4 space-y-6">
                    <flux:heading size="lg">Basic Information</flux:heading>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:field>
                            <flux:label>Membership Number <flux:badge size="sm" color="red">Required</flux:badge></flux:label>
                            <flux:input wire:model="membershipNumber" placeholder="e.g. CP1" />
                            <flux:error name="membershipNumber" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Contact Name <flux:badge size="sm" color="red">Required</flux:badge></flux:label>
                            <flux:input wire:model="contactName" placeholder="Full name" />
                            <flux:error name="contactName" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Company / Organisation</flux:label>
                        <flux:input wire:model="company" placeholder="Company or organisation name" />
                        <flux:error name="company" />
                    </flux:field>
                </flux:card>
            </flux:tab.panel>

            {{-- Addresses Panel --}}
            <flux:tab.panel name="addresses">
                <flux:card class="mt-4 space-y-6">
                    <flux:heading size="lg">Address A</flux:heading>

                    <flux:field>
                        <flux:label>Address Type</flux:label>
                        <flux:input wire:model="addressTypeA" placeholder="e.g. Office, Residence" />
                        <flux:error name="addressTypeA" />
                    </flux:field>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:field>
                            <flux:label>Address Line 1</flux:label>
                            <flux:input wire:model="address1A" placeholder="Street address" />
                            <flux:error name="address1A" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Address Line 2</flux:label>
                            <flux:input wire:model="address2A" placeholder="Building, landmark" />
                            <flux:error name="address2A" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Area</flux:label>
                            <flux:input wire:model="areaA" placeholder="Area / locality" />
                            <flux:error name="areaA" />
                        </flux:field>

                        <flux:field>
                            <flux:label>City</flux:label>
                            <flux:input wire:model="cityA" placeholder="City" />
                            <flux:error name="cityA" />
                        </flux:field>

                        <flux:field>
                            <flux:label>State</flux:label>
                            <flux:input wire:model="stateA" placeholder="State" />
                            <flux:error name="stateA" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Pincode</flux:label>
                            <flux:input wire:model="pincodeA" placeholder="Pincode" />
                            <flux:error name="pincodeA" />
                        </flux:field>
                    </div>

                    <flux:separator />

                    <flux:heading size="lg">Address B</flux:heading>

                    <flux:field>
                        <flux:label>Address Type</flux:label>
                        <flux:input wire:model="addressTypeB" placeholder="e.g. Office, Residence" />
                        <flux:error name="addressTypeB" />
                    </flux:field>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:field>
                            <flux:label>Address Line 1</flux:label>
                            <flux:input wire:model="address1B" placeholder="Street address" />
                            <flux:error name="address1B" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Address Line 2</flux:label>
                            <flux:input wire:model="address2B" placeholder="Building, landmark" />
                            <flux:error name="address2B" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Area</flux:label>
                            <flux:input wire:model="areaB" placeholder="Area / locality" />
                            <flux:error name="areaB" />
                        </flux:field>

                        <flux:field>
                            <flux:label>City</flux:label>
                            <flux:input wire:model="cityB" placeholder="City" />
                            <flux:error name="cityB" />
                        </flux:field>

                        <flux:field>
                            <flux:label>State</flux:label>
                            <flux:input wire:model="stateB" placeholder="State" />
                            <flux:error name="stateB" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Pincode</flux:label>
                            <flux:input wire:model="pincodeB" placeholder="Pincode" />
                            <flux:error name="pincodeB" />
                        </flux:field>
                    </div>
                </flux:card>
            </flux:tab.panel>

            {{-- Contact Panel --}}
            <flux:tab.panel name="contact">
                <flux:card class="mt-4 space-y-6">
                    <flux:heading size="lg">Contact Details</flux:heading>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:field>
                            <flux:label>Office Phone</flux:label>
                            <flux:input wire:model="officePhone" placeholder="Office phone number" />
                            <flux:error name="officePhone" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Home Phone</flux:label>
                            <flux:input wire:model="homePhone" placeholder="Home phone number" />
                            <flux:error name="homePhone" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Cell No</flux:label>
                            <flux:input wire:model="cellNo" placeholder="Mobile number" />
                            <flux:error name="cellNo" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Email Address</flux:label>
                            <flux:input wire:model="email" placeholder="Email address" />
                            <flux:error name="email" />
                        </flux:field>

                        <flux:field class="md:col-span-2">
                            <flux:label>Website</flux:label>
                            <flux:input wire:model="web" placeholder="https://..." />
                            <flux:error name="web" />
                        </flux:field>
                    </div>
                </flux:card>
            </flux:tab.panel>

            {{-- Membership Panel --}}
            <flux:tab.panel name="membership">
                <flux:card class="mt-4 space-y-6">
                    <flux:heading size="lg">Membership Details</flux:heading>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:field>
                            <flux:label>Post</flux:label>
                            <flux:input wire:model="post" placeholder="e.g. Chief Patron" />
                            <flux:error name="post" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Type</flux:label>
                            <flux:input wire:model="type" placeholder="e.g. Company, Individual, Association" />
                            <flux:error name="type" />
                        </flux:field>
                    </div>
                </flux:card>
            </flux:tab.panel>

            {{-- Personal Panel --}}
            <flux:tab.panel name="personal">
                <flux:card class="mt-4 space-y-6">
                    <flux:heading size="lg">Personal Details</flux:heading>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:field>
                            <flux:label>Date of Birth</flux:label>
                            <flux:input wire:model="dob" placeholder="e.g. 14.10.1980" />
                            <flux:error name="dob" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Blood Group</flux:label>
                            <flux:input wire:model="bloodGroup" placeholder="e.g. B+" />
                            <flux:error name="bloodGroup" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Aadhar No</flux:label>
                            <flux:input wire:model="aadharNo" placeholder="Aadhar number" />
                            <flux:error name="aadharNo" />
                        </flux:field>

                        <flux:field>
                            <flux:label>PAN No</flux:label>
                            <flux:input wire:model="panNo" placeholder="PAN number" />
                            <flux:error name="panNo" />
                        </flux:field>

                        <flux:field class="md:col-span-2">
                            <flux:label>GST No</flux:label>
                            <flux:input wire:model="gstNo" placeholder="GST number" />
                            <flux:error name="gstNo" />
                        </flux:field>
                    </div>
                </flux:card>
            </flux:tab.panel>

            {{-- Business Panel --}}
            <flux:tab.panel name="business">
                <flux:card class="mt-4 space-y-6">
                    <flux:heading size="lg">Business Information</flux:heading>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:field>
                            <flux:label>Nature of Business</flux:label>
                            <flux:input wire:model="natureOfBusiness" placeholder="Nature of business" />
                            <flux:error name="natureOfBusiness" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Business Segment</flux:label>
                            <flux:input wire:model="businessSegment" placeholder="Business segment" />
                            <flux:error name="businessSegment" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Turn Over</flux:label>
                            <flux:input wire:model="turnOver" placeholder="Annual turnover" />
                            <flux:error name="turnOver" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Scale of Business</flux:label>
                            <flux:input wire:model="scaleOfBusiness" placeholder="Scale of business" />
                            <flux:error name="scaleOfBusiness" />
                        </flux:field>
                    </div>
                </flux:card>
            </flux:tab.panel>

            {{-- Family Panel --}}
            <flux:tab.panel name="family">
                <flux:card class="mt-4 space-y-6">
                    <flux:heading size="lg">Family Details</flux:heading>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:field>
                            <flux:label>Spouse Name</flux:label>
                            <flux:input wire:model="spouseName" placeholder="Spouse name" />
                            <flux:error name="spouseName" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Spouse Phone No</flux:label>
                            <flux:input wire:model="spousePhoneNo" placeholder="Spouse phone number" />
                            <flux:error name="spousePhoneNo" />
                        </flux:field>
                    </div>
                </flux:card>
            </flux:tab.panel>
        </flux:tabs>

        {{-- Sticky save bar --}}
        <div class="flex justify-end gap-2 mt-6">
            <flux:button type="submit" variant="primary">Save Changes</flux:button>
            <flux:button type="button" :href="route('admin.members.index')" variant="ghost" wire:navigate>Cancel</flux:button>
        </div>
    </form>

    {{-- Delete Confirmation --}}
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-6">
            <flux:heading size="lg">Delete Member</flux:heading>
            <flux:text>Are you sure you want to delete <strong>{{ $member->contact_name }}</strong> ({{ $member->membership_number }})? This action cannot be undone.</flux:text>
            <div class="flex gap-2">
                <flux:button variant="danger" wire:click="deleteMember">Delete</flux:button>
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
