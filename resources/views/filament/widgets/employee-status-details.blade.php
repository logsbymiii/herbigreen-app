<div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800">
            <tr>
                <th scope="col" class="px-6 py-3">Nama Karyawan</th>
                <th scope="col" class="px-6 py-3">Divisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $emp)
                <tr class="bg-white border-b dark:bg-gray-900 dark:border-gray-700">
                    <td class="px-6 py-4 font-medium">{{ $emp->name }}</td>
                    <td class="px-6 py-4">{{ $emp->division->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="px-6 py-4 text-center text-gray-500">Tidak ada data untuk status ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
