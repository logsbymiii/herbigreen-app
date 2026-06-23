<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Matrix Absensi - {{ $monthName }}</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 font-bold bg-white dark:bg-gray-800 sticky left-0 z-10 border-r dark:border-gray-700">
                            Nama Karyawan
                        </th>
                        @foreach($dates as $date)
                            <th scope="col" class="px-2 py-3 text-center border-b dark:border-gray-700 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($date)->format('d') }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($matrix as $empName => $attendances)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white sticky left-0 bg-white dark:bg-gray-800 border-r dark:border-gray-700">
                                {{ $empName }}
                            </td>
                            @foreach($dates as $date)
                                @php
                                    $type = $attendances[$date];
                                    $colorClass = 'text-gray-400'; // Default '-'
                                    if ($type === 'hadir') $colorClass = 'text-green-500 font-bold';
                                    if ($type === 'wfh') $colorClass = 'text-blue-500 font-bold';
                                    if ($type === 'sakit') $colorClass = 'text-yellow-500 font-bold';
                                    if ($type === 'izin') $colorClass = 'text-orange-500 font-bold';
                                    if ($type === 'cuti') $colorClass = 'text-purple-500 font-bold';
                                    if ($type === 'alpa') $colorClass = 'text-red-500 font-bold';
                                @endphp
                                <td class="px-2 py-4 text-center border-b dark:border-gray-700">
                                    <span class="{{ $colorClass }}" title="{{ $date }} - {{ strtoupper($type) }}">
                                        @if($type === 'hadir')
                                            H
                                        @elseif($type === 'wfh')
                                            W
                                        @elseif($type === 'sakit')
                                            S
                                        @elseif($type === 'izin')
                                            I
                                        @elseif($type === 'alpa')
                                            A
                                        @else
                                            -
                                        @endif
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 text-xs text-gray-500 flex gap-4">
            <span><strong class="text-green-500">H</strong>: Hadir</span>
            <span><strong class="text-blue-500">W</strong>: WFH</span>
            <span><strong class="text-yellow-500">S</strong>: Sakit</span>
            <span><strong class="text-orange-500">I</strong>: Izin</span>
            <span><strong class="text-red-500">A</strong>: Alpa</span>
            <span><strong class="text-red-500">T</strong>: Telat</span>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
