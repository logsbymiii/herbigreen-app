<?php
$map = [
    'Editor' => 'Editor Konten',
    'CRM' => 'Admin CRM',
    'Packing' => 'Tim Packing',
    'Head & Admin Toko' => 'Admin Toko'
];

foreach ($map as $oldName => $newName) {
    $oldDiv = \App\Models\Division::where('name', $oldName)->first();
    
    $newDiv = \App\Models\Division::where('name', $newName)->first();
    if (!$newDiv) {
        $newDiv = new \App\Models\Division();
        $newDiv->name = $newName;
        $newDiv->save();
    }

    if ($oldDiv) {
        \App\Models\Employee::where('division_id', $oldDiv->id)->update(['division_id' => $newDiv->id]);
        $oldDiv->delete();
        echo "Merged $oldName into $newName\n";
    } else {
        echo "$oldName not found, skipping.\n";
    }
}
echo "Cleanup done.\n";
