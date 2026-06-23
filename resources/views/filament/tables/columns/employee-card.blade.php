@php
    $record = $getRecord();
    $nameParts = explode(' ', $record->name);
    $initials = collect($nameParts)->map(fn($n) => substr($n, 0, 1))->take(2)->implode('');
    
    // Format phone number
    $phone = $record->phone;
    if (preg_match('/^62(\d{3})(\d{3,4})(\d{3,4})$/', $phone, $matches)) {
        $formattedPhone = '+62 ' . $matches[1] . '-' . $matches[2] . '-' . $matches[3];
    } else {
        $formattedPhone = '+' . substr($phone, 0, 2) . ' ' . substr($phone, 2);
    }
@endphp

<div class="flex items-center justify-between w-full p-2">
    <div class="flex items-center gap-4">
        <!-- Avatar Initials -->
        <div class="flex items-center justify-center w-12 h-12 rounded-full font-bold text-lg bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
            {{ strtoupper($initials) }}
        </div>
        
        <!-- Name and Phone -->
        <div class="flex flex-col">
            <span class="font-bold text-gray-900 dark:text-white text-base leading-tight">{{ $record->name }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $formattedPhone }}</span>
        </div>
    </div>
    
    <!-- Status Dot and Chevron -->
    <div class="flex items-center gap-3">
        <div class="w-2.5 h-2.5 rounded-full {{ $record->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></div>
        <x-heroicon-m-chevron-right class="w-5 h-5 text-gray-400" />
    </div>
</div>
