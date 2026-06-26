<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Laporan Harian - {{ $date }}</title>
    <style>
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            font-size: 12px; 
            color: #222;
            line-height: 1.5;
            margin: 0;
            padding: 0 10px;
        }
        .header {
            border-bottom: 2px solid #222;
            padding-bottom: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 22px;
            letter-spacing: 1px;
        }
        .header p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }
        .summary-box {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .summary-box h3 {
            margin-top: 0;
            font-size: 15px;
            color: #111;
        }
        .division-header {
            font-size: 16px;
            color: #111;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        .employee-card {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #eee;
        }
        .emp-name {
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }
        .badge {
            font-size: 10px;
            padding: 3px 6px;
            border-radius: 4px;
            font-weight: bold;
            margin-left: 8px;
            vertical-align: middle;
        }
        .badge-hadir { background-color: #e6f4ea; color: #137333; }
        .badge-alpa { background-color: #fce8e6; color: #c5221f; }
        .emp-time {
            font-size: 11px;
            color: #777;
            margin-left: 5px;
        }
        .content-section {
            margin-top: 8px;
            font-size: 12px;
        }
        .content-label {
            font-weight: bold;
            color: #555;
            font-size: 11px;
            text-transform: uppercase;
        }
        .ai-text {
            margin-top: 4px;
        }
        .ai-text p { margin: 0 0 5px 0; }
        .ai-text ul { margin: 0; padding-left: 18px; }
        .metric-text {
            color: #444;
            margin-top: 4px;
        }
        .kendala-text {
            color: #c5221f;
            margin-top: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REKAP HARIAN HERBIGREEN</h1>
        <p>Tanggal: <strong>{{ $date }}</strong></p>
    </div>
    
    @if(isset($executiveSummary))
    <div class="summary-box">
        <h3>📊 Executive Summary (AI Analysis)</h3>
        <div class="ai-text">
            {!! $executiveSummary !!}
        </div>
    </div>
    @endif

    @foreach($divisions as $division => $emps)
        <div class="division-header">
            <strong>Divisi: {{ strtoupper($division) }}</strong>
        </div>
        
        @foreach($emps as $emp)
            <div class="employee-card">
                <div>
                    <span class="emp-name">{{ $emp->name }}</span>
                    @if($emp->attendance_today)
                        <span class="badge badge-hadir">{{ strtoupper($emp->attendance_today->type) }}</span>
                        @if($emp->attendance_today->type === 'hadir' && $emp->attendance_today->clocked_in_at)
                            <span class="emp-time">({{ \Carbon\Carbon::parse($emp->attendance_today->clocked_in_at)->format('H:i') }} WIB)</span>
                        @endif
                    @else
                        <span class="badge badge-alpa">ALPA</span>
                    @endif
                </div>

                @if($emp->report_today)
                    <div class="content-section">
                        <div class="content-label">Catatan AI:</div>
                        <div class="ai-text">
                            {!! \Illuminate\Support\Str::markdown($emp->report_today->ai_insight ?? 'Tidak ada catatan khusus.') !!}
                        </div>
                    </div>

                    @if($emp->report_today->extracted_metrics && count($emp->report_today->extracted_metrics) > 0)
                        <div class="content-section">
                            <div class="content-label">Metrik Utama:</div>
                            <div class="metric-text">
                                @foreach($emp->report_today->extracted_metrics as $key => $val)
                                    {{ ucwords(str_replace('_', ' ', $key)) }}: <strong>{{ $val }}</strong> 
                                    @if(!$loop->last) | @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($emp->report_today->kendala)
                        <div class="content-section">
                            <div class="content-label">Kendala:</div>
                            <div class="kendala-text">{{ $emp->report_today->kendala }}</div>
                        </div>
                    @endif
                @else
                    <div class="content-section" style="color:#999; font-style:italic;">
                        Belum ada laporan masuk hari ini.
                    </div>
                @endif
            </div>
        @endforeach
    @endforeach
</body>
</html>
