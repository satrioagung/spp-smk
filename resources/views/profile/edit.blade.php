<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold text-slate-900">Edit Profil</h2><p class="mt-1 text-sm text-slate-500">Perbarui nama, email, dan password akun Anda.</p></div></x-slot>

    <div class="mx-auto max-w-4xl space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">
                <div class="max-w-2xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">
                <div class="max-w-2xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">
                <div class="max-w-2xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
    </div>
</x-app-layout>
