<div class="space-y-4">
   <div>
       <x-filament::section>
           <x-slot name="heading">
              Team Members
           </x-slot>

           <livewire:participants.tenant-members-table :tenant="filamentTenant()" />
       </x-filament::section>

   </div>
    <div>
        <x-filament::section>
            <x-slot name="heading">
                Invitations
            </x-slot>

           <livewire:tenancy.pending-invitation-table/>
        </x-filament::section>
    </div>
</div>
