# Product Information Block — Setup Instructions

## Files in this package

| File | Where it goes |
|------|--------------|
| `2024_01_07_add_product_info_to_parts.php` | `database/migrations/` |
| `product-info.blade.php` | `resources/views/partials/` |
| `product-info-editor.blade.php` | `resources/views/admin/inventory/partials/` (create this folder) |
| `InventoryController_snippet.php` | Read-only — contains the method to add to InventoryController |

---

## Step 1 — Run migration on server

```bash
php artisan migrate --force
```

---

## Step 2 — Include the product info block on the part detail page

Open `resources/views/parts/show.blade.php`.

Find the section that starts with `{{-- Donor vehicle --}}` and add this BEFORE it:

```blade
{{-- Product Information --}}
@include('partials.product-info', ['part' => $part])
```

---

## Step 3 — Include the editor on the admin inventory edit page

Open `resources/views/admin/inventory/edit.blade.php`.

Find the Description/Notes textarea and add this AFTER it:

```blade
@include('admin.inventory.partials.product-info-editor', ['part' => $part])
```

---

## Step 4 — Add product_info saving to InventoryController

Open `app/Http/Controllers/Admin/InventoryController.php`.

**A) Add the private method** (from `InventoryController_snippet.php`) at the bottom
of the class, before the closing `}`.

**B) In the `update()` method**, inside the `DB::table('parts_inventory')` update array,
add this line:

```php
'product_info' => $this->buildProductInfo($request),
```

---

## How it works

When a staff member edits a part in the admin panel, they see the
Product Information section with fields for:
- Fitment
- Type
- Origin
- Warranty
- Included
- Notes
- Extra custom bullets (unlimited)

**All fields are optional.** If left blank, the block auto-generates
from the part data:
- Fitment → from brand + model + compat_year_from/to + compatible_trims
- Type → from part_category + transmission_code_oem + pin_count
- Origin → from origin_market field
- Warranty → 90 days for engine/trans, 30 days for others, no warranty for airbags
- Included → "Complete Transmission (as pictured)" etc.
- Notes → "Reuse original sensors..." for transmission, colour note for body

The customer-facing product page shows this block with gold bullet points
matching the AllStarJDM style shown in the screenshot.

---

## Example output for 2009-2015 Corolla Transmission U341E

```
Product Information
2009-2015 TOYOTA COROLLA TRANSMISSION U341E

• Fitment: 2009-2015 Toyota Corolla
• Type: Automatic Transmission · U341E · 13-pin
• Condition: Grade A — Like new, low mileage
• Mileage: 45,000 miles at time of harvest
• Origin: JDM (Japanese Domestic Market)
• Pin Count: 13 pins · 13-pin gear (Toyota Corolla)
• Warranty: 90 Days
• Included: Complete Transmission (as pictured)
• Notes: Reuse your original sensors and components for proper installation.
• Donor VIN: JT2BF22K1W0123456
```
