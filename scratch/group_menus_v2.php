<?php

$basePath = 'c:/laragon/www/herbigreen-app/app/Filament/Resources/';

$groups = [
    'AdminTokoReports\AdminTokoReportResource.php' => 'Laporan Harian Divisi',
    'AffiliateReports\AffiliateReportResource.php' => 'Laporan Harian Divisi',
    'HostLiveReports\HostLiveReportResource.php' => 'Laporan Harian Divisi',
    'VideographerReports\VideographerReportResource.php' => 'Laporan Harian Divisi',
    'ContentCreatorReports\ContentCreatorReportResource.php' => 'Laporan Harian Divisi',
    'CrmReports\CrmReportResource.php' => 'Laporan Harian Divisi',
    'SosmedReports\SosmedReportResource.php' => 'Laporan Harian Divisi',
    'HrReports\HrReportResource.php' => 'Laporan Harian Divisi',
    'EditorKontenReports\EditorKontenReportResource.php' => 'Laporan Harian Divisi',
    'PackingReports\PackingReportResource.php' => 'Laporan Harian Divisi',
    'AdminKomenReports\AdminKomenReportResource.php' => 'Laporan Harian Divisi',
    
    'SmartDailyReports\SmartDailyReportResource.php' => 'AI & Analitik',
    'GmvReports\GmvReportResource.php' => 'AI & Analitik',
    'Reports\ReportResource.php' => 'AI & Analitik',
];

foreach ($groups as $file => $groupName) {
    $path = $basePath . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        // If not already grouped
        if (strpos($content, '$navigationGroup') === false) {
            $content = preg_replace('/(protected static \?string \$model = .*?;)/', "$1\n\n    protected static string|\UnitEnum|null \$navigationGroup = '$groupName';", $content);
            file_put_contents($path, $content);
            echo "Added group to $file\n";
        }
    }
}
