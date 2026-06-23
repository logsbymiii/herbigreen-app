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

<div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0.5rem;">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <!-- Avatar Initials -->
        <div style="display: flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 9999px; font-weight: bold; font-size: 1.125rem; background-color: #d1fae5; color: #065f46;">
            {{ strtoupper($initials) }}
        </div>
        
        <!-- Name, Divisi, and Phone -->
        <div style="display: flex; flex-direction: column;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-weight: bold; font-size: 1rem; line-height: 1.25;">{{ $record->name }}</span>
            </div>
            <span style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">
                {{ $formattedPhone }} &bull; <span style="color: #059669; font-weight: 600;">{{ $record->division->name ?? 'Semua Divisi' }}</span>
            </span>
        </div>
    </div>
    
    <!-- Status Dot and Chevron -->
    <div style="display: flex; align-items: center; gap: 0.75rem;">
        <div style="width: 0.75rem; height: 0.75rem; border-radius: 9999px; background-color: {{ $record->is_active ? '#10b981' : '#ef4444' }};"></div>
        <x-heroicon-m-chevron-right style="width: 1.25rem; height: 1.25rem; color: #9ca3af;" />
    </div>
</div>
