<?php
$resources = [
    'AdminKomenReports/AdminKomenReportResource.php' => 'Admin Komen',
    'AdminTokoReports/AdminTokoReportResource.php' => 'Admin Toko',
    'AffiliateReports/AffiliateReportResource.php' => 'Affiliate',
    'Attendances/AttendanceResource.php' => 'Absensi',
    'ContentCreatorReports/ContentCreatorReportResource.php' => 'Content Creator',
    'CrmReports/CrmReportResource.php' => 'CRM',
    'EditorKontenReports/EditorKontenReportResource.php' => 'Editor Konten',
    'HostLiveReports/HostLiveReportResource.php' => 'Host Live',
    'HrReports/HrReportResource.php' => 'HR',
    'PackingReports/PackingReportResource.php' => 'Packing',
    'SosmedReports/SosmedReportResource.php' => 'Sosmed',
    'VideographerReports/VideographerReportResource.php' => 'Videographer',
];

$basePath = "c:/laragon/www/herbigreen-app/app/Filament/Resources/";

foreach ($resources as $file => $label) {
    $path = $basePath . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        $replacement = "    public static function getRecordTitle(?\\Illuminate\\Database\\Eloquent\\Model \$record): string\n" .
                       "    {\n" .
                       "        if (\$record && \$record->employee) {\n" .
                       "            return \"{$label} {\$record->employee->name}\";\n" .
                       "        }\n" .
                       "        return 'Laporan {$label}';\n" .
                       "    }";

        $content = preg_replace("/\s*protected static \?string \\\$recordTitleAttribute = 'id';/", "\n\n" . $replacement, $content);
        
        file_put_contents($path, $content);
        echo "Updated: $file\n";
    }
}

// Update EmployeeResource
$employeePath = $basePath . "Employees/EmployeeResource.php";
if (file_exists($employeePath)) {
    $content = file_get_contents($employeePath);
    $content = preg_replace("/\s*protected static \?string \\\$recordTitleAttribute = 'Employee';/", "\n\n    protected static ?string \$recordTitleAttribute = 'name';", $content);
    file_put_contents($employeePath, $content);
    echo "Updated: EmployeeResource\n";
}

echo "All done!\n";
