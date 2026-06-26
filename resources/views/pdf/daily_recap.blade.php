<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Laporan Harian - {{ $date }}</title>
    <style>
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            font-size: 11px; 
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #4EA674;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #1a4d2e;
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 12px;
        }
        .executive-summary {
            background-color: #f4fbf7;
            padding: 15px 20px;
            border-left: 5px solid #4EA674;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .executive-summary h3 {
            margin-top: 0; 
            color: #1a4d2e;
            font-size: 14px;
            border-bottom: 1px solid #c3e6cb;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .executive-summary .content {
            font-size: 12px; 
            color: #444;
        }
        .executive-summary .content ul, .executive-summary .content ol {
            padding-left: 20px;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        .executive-summary .content p {
            margin-top: 0;
            margin-bottom: 8px;
        }
        
        .division-title { 
            background-color: #1a4d2e; 
            color: white;
            padding: 8px 12px; 
            margin-top: 25px; 
            margin-bottom: 0;
            font-size: 13px;
            border-radius: 4px 4px 0 0;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
            table-layout: fixed;
        }
        th, td { 
            border: 1px solid #e0e0e0; 
            padding: 10px; 
            vertical-align: top;
            word-wrap: break-word;
        }
        th { 
            background-color: #f8f9fa; 
            color: #333;
            font-weight: bold;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        tbody tr:nth-child(even) {
            background-color: #fbfbfb;
        }
        .kendala { 
            color: #d9534f; 
            font-weight: bold; 
        }
        .ai-insight {
            background-color: #f8f9fa;
            border: 1px dashed #ccc;
            padding: 8px;
            margin-top: 8px;
            border-radius: 4px;
        }
        .ai-insight p {
            margin: 0 0 5px 0;
        }
        .ai-insight ul {
            margin: 0;
            padding-left: 15px;
        }
        .metrik-list {
            list-style-type: square;
            padding-left: 15px;
            margin: 5px 0;
            color: #555;
        }
        .badge-hadir {
            background-color: #d4edda;
            color: #155724;
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
        .badge-alpa {
            background-color: #f8d7da;
            color: #721c24;
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Rekap Laporan Harian Tim</h1>
        <p><strong>HerbiGreen Official</strong> | Tanggal: {{ $date }}</p>
    </div>
    
    @if(isset($executiveSummary))
    <div class="executive-summary">
        <h3>✨ Executive Summary (AI Analysis)</h3>
        <div class="content">
            {!! $executiveSummary !!}
        </div>
    </div>
    @endif

    @foreach($divisions as $division => $emps)
        <h3 class="division-title">📌 Divisi: {{ strtoupper($division) }}</h3>
        <table>
            <thead>
                <tr>
                    <th width="15%">Nama / Absen</th>
                    <th width="55%">Laporan & Metrik</th>
                    <th width="30%">Evaluasi AI & Kendala</th>
                </tr>
            </thead>
            <tbody>
                @foreach($emps as $emp)
                    <tr>
                        <td>
                            <strong>{{ $emp->name }}</strong>
                            <div style="margin-top: 8px;">
                                @if($emp->attendance_today)
                                    <span class="badge-hadir">{{ strtoupper($emp->attendance_today->type) }}</span><br>
                                    @if($emp->attendance_today->type === 'hadir' && $emp->attendance_today->clocked_in_at)
                                        <small style="color: #666; margin-top:3px; display:block;">🕒 {{ \Carbon\Carbon::parse($emp->attendance_today->clocked_in_at)->format('H:i') }} WIB</small>
                                    @endif
                                @else
                                    <span class="badge-alpa">ALPA</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($emp->report_today)
                                @if($emp->report_today->extracted_metrics && count($emp->report_today->extracted_metrics) > 0)
                                    <strong style="color: #1a4d2e;">Kinerja Terukur:</strong>
                                    <ul class="metrik-list">
                                        @foreach($emp->report_today->extracted_metrics as $key => $val)
                                            <li><strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong> {{ $val }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <em style="color: #999;">Tidak ada metrik terukur.</em><br>
                                @endif
                            @else
                                <em style="color: #999;">Tidak ada laporan harian.</em>
                            @endif
                        </td>
                        <td>
                            @if($emp->report_today)
                                <div class="ai-insight">
                                    <strong style="color: #0056b3; font-size: 10px; text-transform: uppercase;">Catatan Evaluasi AI:</strong>
                                    <div style="font-size: 11px; margin-top: 4px;">
                                        {!! \Illuminate\Support\Str::markdown($emp->report_today->ai_insight ?? 'Tidak ada catatan.') !!}
                                    </div>
                                </div>
                                @if($emp->report_today->kendala)
                                    <div style="margin-top: 10px;">
                                        <strong style="color: #d9534f; font-size: 10px; text-transform: uppercase;">Kendala/Bloker:</strong>
                                        <div class="kendala" style="font-size: 11px; margin-top:2px;">{{ $emp->report_today->kendala }}</div>
                                    </div>
                                @endif
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
