<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class AttendancesExport implements FromQuery, WithHeadings, WithMapping, WithDrawings, WithEvents
{
    protected Builder $query;
    protected int $row = 2; // Starts after headings
    protected array $drawings = [];
    protected array $rowHeights = [];
    protected array $tempFiles = [];

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['No', 'Nama', 'Tipe Kehadiran', 'Keterangan', 'Tanggal', 'Bukti Kehadiran', 'Bukti Surat'];
    }

    public function map($attendance): array
    {
        $rowNum = $this->row;
        $this->row++;
        static $counter = 1;

        if ($attendance->proof_path) {
            try {
                $drawing = new Drawing();
                $drawing->setName('Bukti');
                $drawing->setDescription('Bukti');
                
                if (str_starts_with($attendance->proof_path, 'http')) {
                    $imageContent = @file_get_contents($attendance->proof_path);
                } else {
                    $imageContent = Storage::disk('r2')->get($attendance->proof_path);
                }
                
                if ($imageContent) {
                    $tempPath = sys_get_temp_dir() . '/' . uniqid('export_') . '.jpg';
                    file_put_contents($tempPath, $imageContent);
                    $this->tempFiles[] = $tempPath;
                    
                    $drawing->setPath($tempPath);
                    $drawing->setHeight(80);
                    
                    if ($attendance->type === 'hadir') {
                        $drawing->setCoordinates('F' . $rowNum);
                    } else {
                        $drawing->setCoordinates('G' . $rowNum);
                    }
                    
                    $this->drawings[] = $drawing;
                    $this->rowHeights[$rowNum] = 80;
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        return [
            $counter++,
            $attendance->employee?->name ?? 'Deleted',
            ucfirst($attendance->type),
            $attendance->note,
            \Carbon\Carbon::parse($attendance->date)->format('d M Y'),
            '', // F
            '', // G
        ];
    }

    public function drawings()
    {
        return $this->drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                foreach ($this->rowHeights as $row => $height) {
                    $sheet->getRowDimension($row)->setRowHeight($height);
                }
                $sheet->getColumnDimension('F')->setWidth(20);
                $sheet->getColumnDimension('G')->setWidth(20);
            },
        ];
    }

    public function __destruct()
    {
        foreach ($this->tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }
}
