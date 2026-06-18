<?php
// ============================================================
// ADD THIS to InventoryController::update() method
// Inside the DB::table update array, add this line:
// 'product_info' => $this->buildProductInfo($request),
//
// And add this private method to the class:
// ============================================================

// In InventoryController — add this private method:

private function buildProductInfo(\Illuminate\Http\Request $request): ?string
{
    $data = [];

    if ($request->filled('pi_fitment'))  $data['fitment']  = trim($request->pi_fitment);
    if ($request->filled('pi_type'))     $data['type']     = trim($request->pi_type);
    if ($request->filled('pi_origin'))   $data['origin']   = trim($request->pi_origin);
    if ($request->filled('pi_warranty')) $data['warranty'] = trim($request->pi_warranty);
    if ($request->filled('pi_included')) $data['included'] = trim($request->pi_included);
    if ($request->filled('pi_notes'))    $data['notes']    = trim($request->pi_notes);

    // Extra custom bullets
    $labels = $request->input('pi_extra_label', []);
    $values = $request->input('pi_extra_value', []);
    $extras = [];
    foreach ($labels as $i => $label) {
        if (trim($label) && isset($values[$i]) && trim($values[$i])) {
            $extras[] = ['label' => trim($label), 'value' => trim($values[$i])];
        }
    }
    if (!empty($extras)) $data['extras'] = $extras;

    return empty($data) ? null : json_encode($data);
}
