<div>
    <div class="mb-8 text-center md:text-left">
        <h2 class="font-headline-md text-headline-md text-on-surface mb-2">Selamat Datang Kembali</h2>
        <p class="font-body-md text-body-md text-on-surface-variant">Silakan masuk ke akun Anda untuk melanjutkan.</p>
    </div>

    <form wire:submit="authenticate" class="space-y-6">
        <!-- Email/Username Field -->
        <div class="space-y-2">
            <label class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider" for="email">Email</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-on-surface-variant group-focus-within:text-primary transition-colors">
                    <span class="material-symbols-outlined text-[20px]">person</span>
                </div>
                <input wire:model="email" id="email" type="email" placeholder="admin@herbigreen.com" required 
                    class="block w-full pl-11 pr-4 py-3 bg-surface-container-lowest border @error('email') border-red-500 @else border-outline-variant @enderror rounded-lg font-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-outline" />
            </div>
            @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <!-- Password Field -->
        <div class="space-y-2" x-data="{ show: false }">
            <div class="flex items-center justify-between">
                <label class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider" for="password">Password</label>
            </div>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-on-surface-variant group-focus-within:text-primary transition-colors">
                    <span class="material-symbols-outlined text-[20px]">lock</span>
                </div>
                <input wire:model="password" id="password" :type="show ? 'text' : 'password'" placeholder="••••••••" required 
                    class="block w-full pl-11 pr-12 py-3 bg-surface-container-lowest border @error('password') border-red-500 @else border-outline-variant @enderror rounded-lg font-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-outline" />
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 flex items-center text-on-surface-variant hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>
            @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center gap-2 mt-4">
            <input wire:model="remember" id="remember" type="checkbox" class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary" />
            <label for="remember" class="text-sm text-on-surface-variant font-medium">Ingat saya</label>
        </div>

        <!-- Primary Action -->
        <button type="submit" class="w-full py-3.5 bg-primary text-on-primary font-label-md text-body-md rounded-lg shadow-lg shadow-primary/20 hover:bg-primary-container hover:text-on-primary-container active:scale-[0.98] transition-all duration-200 mt-6">
            Masuk
        </button>
    </form>
</div>
