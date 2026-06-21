<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Laporan Harian - {{ $date }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .kendala { color: red; font-weight: bold; }
        .division-title { background-color: #e6ffe6; padding: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <h2>Rekap Laporan Harian Tim Herbigreen</h2>
    <p><strong>Tanggal:</strong> {{ $date }}</p>

    @foreach($divisions as $division => $emps)
        <h3 class="division-title">Divisi: {{ $division }}</h3>
        <table>
            <thead>
                <tr>
                    <th width="15%">Nama</th>
                    <th width="10%">Absen</th>
                    <th width="45%">Laporan / Metrik</th>
                    <th width="30%">Kendala</th>
                </tr>
            </thead>
            <tbody>
                @foreach($emps as $emp)
                    <tr>
                        <td>{{ $emp->name }}</td>
                        <td>
                            @if($emp->attendance_today)
                                {{ strtoupper($emp->attendance_today->type) }}<br>
                                @if($emp->attendance_today->type === 'hadir' && $emp->attendance_today->clocked_in_at)
                                    <small>{{ \Carbon\Carbon::parse($emp->attendance_today->clocked_in_at)->format('H:i') }}</small>
                                @endif
                            @else
                                <span style="color:red;">ALPA</span>
                            @endif
                        </td>
                        <td>
                            @if($emp->report_today)
                                <strong>Metrik:</strong><br>
                                @if($emp->report_today->extracted_metrics)
                                    <ul>
                                        @foreach($emp->report_today->extracted_metrics as $key => $val)
                                            <li>{{ ucwords(str_replace('_', ' ', $key)) }}: {{ $val }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <em>Metrik tidak ditemukan.</em><br>
                                @endif
                                <strong>Catatan AI:</strong> {{ $emp->report_today->ai_insight }}
                            @else
                                <em>Tidak ada laporan</em>
                            @endif
                        </td>
                        <td class="kendala">
                            @if($emp->report_today && $emp->report_today->kendala)
                                {{ $emp->report_today->kendala }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
