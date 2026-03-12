<div class="space-y-6">
    <div class="flex items-center gap-4 mb-6">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.members.index')" wire:navigate>Back</flux:button>
        <flux:heading size="xl">Add Member</flux:heading>
    </div>

    <flux:card class="max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">Basic Information</flux:heading>
            <flux:text class="text-zinc-500">Enter the required fields to create the member. You can fill in additional details after.</flux:text>

            <flux:field>
                <flux:label>Membership Number <flux:badge size="sm" color="red">Required</flux:badge></flux:label>
                <flux:input wire:model="membershipNumber" placeholder="e.g. CP1, L1133" />
                <flux:error name="membershipNumber" />
            </flux:field>

            <flux:field>
                <flux:label>Contact Name <flux:badge size="sm" color="red">Required</flux:badge></flux:label>
                <flux:input wire:model="contactName" placeholder="Full name" />
                <flux:error name="contactName" />
            </flux:field>

            <flux:field>
                <flux:label>Company / Organisation</flux:label>
                <flux:input wire:model="company" placeholder="Company or organisation name" />
                <flux:error name="company" />
            </flux:field>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Create & Continue</flux:button>
                <flux:button type="button" variant="ghost" :href="route('admin.members.index')" wire:navigate>Cancel</flux:button>
            </div>
        </form>
    </flux:card>
</div>
