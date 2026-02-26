<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Unit;
use App\Models\ProductVariant;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductTemplateExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductImportController extends Controller
{
    public function show()
    {
        return view('product_management.products.import');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ProductTemplateExport, 'product_import_template.xlsx');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $data = Excel::toArray([], $request->file('file'));

        if (empty($data) || empty($data[0])) {
            return back()->with('error', 'The file is empty or invalid.');
        }

        $headerRow = $data[0][0] ?? [];
        $columnMap = $this->resolveImportColumnMap($headerRow);
        $rows = array_slice($data[0], 1); // Skip header
        $previewData = [];
        $validRowsCount = 0;
        $hasErrors = false;

        // Pre-fetch related data for quick validation/lookup
        $categories = Category::all()->keyBy(fn($item) => $this->normalizeLookup($item->name));
        $subCategoriesByName = SubCategory::all()->groupBy(fn($item) => $this->normalizeLookup($item->name));
        $units = Unit::all()->keyBy(fn($item) => $this->normalizeLookup($item->name));
        
        $reservedSkus = ProductVariant::pluck('sku')->map(fn($sku) => mb_strtolower($sku))->toArray();
        
        // Grouping Logic: We process rows but need to identify products
        // In this preview, we just list flattened variants but attach product-level flags

        foreach ($rows as $index => $row) {
             // Supported template columns:
             // Product Name, Category, Sub Category, Description, Unit Name, Unit Value, (optional legacy SKU), Selling Price, Limit Price, (optional legacy Quantity), Alert Quantity, Image URL
             $name = $this->readTextColumn($row, $columnMap['name']);
             if (!$name) continue;

             $catName = $this->readTextColumn($row, $columnMap['category']);
             $subCatName = $this->readTextColumn($row, $columnMap['sub_category']);
             $desc = $this->readTextColumn($row, $columnMap['description']);
             $unitName = $this->readTextColumn($row, $columnMap['unit_name']);
             $unitValue = $this->readTextColumn($row, $columnMap['unit_value']);

             $priceRaw = $this->readRawColumn($row, $columnMap['selling_price']);
             $price = is_numeric($priceRaw) ? (float) $priceRaw : null;

             $limitRaw = $this->readRawColumn($row, $columnMap['limit_price']);
             $limit = trim((string) $limitRaw) === '' ? null : (is_numeric($limitRaw) ? (float) $limitRaw : null);
             $limitInvalid = trim((string) $limitRaw) !== '' && !is_numeric($limitRaw);

             $alertRaw = $this->readRawColumn($row, $columnMap['alert_quantity']);
             $alert = trim((string) $alertRaw) === '' ? 0 : (is_numeric($alertRaw) ? (int) $alertRaw : null);
             $imageUrl = $this->readTextColumn($row, $columnMap['image_url']);
             $sku = null;

             $errors = [];
             $rowStatus = 'OK'; // OK, ERROR, MISSING_DATA

             // 1. Validation: Category
             $catId = null;
             if (!$catName) {
                 $errors['category'] = "Required";
             } else {
                 $key = $this->normalizeLookup($catName);
                 if (isset($categories[$key])) {
                     $catId = $categories[$key]->id;
                 } else {
                     $errors['category'] = "MISSING_CATEGORY"; // Special code for UI
                 }
             }

             // 2. Validation: Sub Category
             $subCatId = null;
             if ($subCatName) {
                 $key = $this->normalizeLookup($subCatName);
                 $matchingByName = $subCategoriesByName->get($key, collect());

                 if ($matchingByName->isNotEmpty()) {
                     if ($catId) {
                         $subCatObj = $matchingByName->firstWhere('category_id', $catId);
                         if (!$subCatObj) {
                             $errors['sub_category'] = "Mismatch";
                         } else {
                             $subCatId = $subCatObj->id;
                         }
                     } else {
                         if ($matchingByName->count() > 1) {
                             $errors['sub_category'] = "Ambiguous";
                         } else {
                             $subCatId = $matchingByName->first()->id;
                         }
                     }
                 } else {
                     $errors['sub_category'] = "MISSING_SUB_CATEGORY";
                 }
             }

             // 3. Validation: Unit
             $unitId = null;
             $unitShortName = null;
             if (!$unitName) {
                 $errors['unit'] = "Required";
             } else {
                 $key = $this->normalizeLookup($unitName);
                 if (isset($units[$key])) {
                     $unitId = $units[$key]->id;
                     $unitShortName = $units[$key]->short_name ?? null;
                 } else {
                      $errors['unit'] = "MISSING_UNIT";
                 }
             }

             // 4. SKU is auto-generated for import variants.
             if (!$unitId) {
                 $errors['sku'] = "Auto generation needs a valid Unit";
             } else {
                 try {
                     $sku = $this->generateUniqueSku($name, $unitValue, $unitShortName, $reservedSkus);
                 } catch (\RuntimeException $e) {
                     $errors['sku'] = "Auto generation failed";
                 }
             }

             // 5. Validation: Price
             if ($price === null || $price <= 0) $errors['price'] = "Invalid";
             if ($limitInvalid || ($limit !== null && $limit < 0)) $errors['limit_price'] = "Invalid";
             if ($limit !== null && $price !== null && $limit > $price) $errors['limit_price'] = "Must be <= price";
             if ($alert === null || $alert < 0) $errors['alert_qty'] = "Invalid";
             
             $previewData[] = [
                 'row_id' => $index, // for tracking
                 'name' => $name,
                 'category_id' => $catId,
                 'category_name' => $catName,
                 'sub_category_id' => $subCatId,
                 'sub_category_name' => $subCatName,
                 'description' => $desc,
                 'unit_id' => $unitId,
                 'unit_name' => $unitName,
                 'unit_value' => $unitValue,
                 'sku' => $sku,
                 'selling_price' => $price,
                 'limit_price' => $limit,
                 'quantity' => 0,
                 'alert_quantity' => $alert,
                 'image_url' => $imageUrl,
                 'errors' => $errors
             ];
        }

        $this->markConflictingCategoryRows($previewData);

        $validRowsCount = collect($previewData)->filter(fn ($row) => empty($row['errors']))->count();
        $hasErrors = collect($previewData)->contains(fn ($row) => !empty($row['errors']));

        session(['product_import_preview_data' => $previewData]);

        return view('product_management.products.import', compact('previewData', 'validRowsCount', 'hasErrors'));
    }

    public function store(Request $request)
    {
        $previewData = session('product_import_preview_data');

        if (!$previewData) {
            return redirect()->route('products.import.show')->with('error', 'Session expired. Please upload again.');
        }

        $count = 0;
        
        // Regroup by Product Name to avoid creating duplicate products if rows are scrambled
        // (Though usually file is sorted, better safe)
        $groupedData = collect($previewData)->groupBy(fn($row) => $this->normalizeLookup($row['name'] ?? ''));

        $reservedSkus = ProductVariant::pluck('sku')->map(fn($sku) => mb_strtolower($sku))->toArray();
        $unitShortNamesById = Unit::query()
            ->pluck('short_name', 'id')
            ->map(fn($shortName) => $shortName ? (string) $shortName : null)
            ->toArray();

        DB::transaction(function () use ($groupedData, &$count, &$reservedSkus, $unitShortNamesById) {
            foreach ($groupedData as $normalizedName => $variants) {
                // Use the FIRST valid row's creation data for the product
                $validVariants = $variants->filter(fn($row) => empty($row['errors']))->values();
                if ($validVariants->isEmpty()) {
                    continue;
                }
                $firstRow = $validVariants->first();
                $productName = trim((string) ($firstRow['name'] ?? ''));
                if ($productName === '') {
                    continue;
                }
                
                // Skip if critical product errors exist?
                // Actually we rely on UI to block import if errors exist. 
                // But if user skips invalid rows, we proceed with valid ones.
                
                // 1. Find or Create Product
                $product = Product::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($productName)])
                    ->first();

                if (!$product) {
                    $image = null;
                    if (!empty($firstRow['image_url'])) {
                        // Attempt upload
                        try {
                             $image = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::uploadApi()->upload($firstRow['image_url'], ['verify' => false])['secure_url'];
                        } catch (\Exception $e) {
                            // Ignore image error, continue creation?
                        }
                    }

                    $product = Product::create([
                        'name' => $productName,
                        'category_id' => $firstRow['category_id'],
                        'sub_category_id' => $firstRow['sub_category_id'] ?: null,
                        'description' => $firstRow['description'],
                        'image' => $image,
                    ]);
                }

                // 2. Add Variants
                foreach ($validVariants as $row) {
                    $sku = trim((string) ($row['sku'] ?? ''));
                    $normalizedSku = $sku !== '' ? mb_strtolower($sku) : '';

                    if ($sku === '' || in_array($normalizedSku, $reservedSkus, true)) {
                        $sku = $this->generateUniqueSku(
                            $productName,
                            $row['unit_value'] ?? null,
                            $unitShortNamesById[$row['unit_id']] ?? null,
                            $reservedSkus
                        );
                    } else {
                        $reservedSkus[] = $normalizedSku;
                    }

                    $variantImage = null; // Currently template supports 1 image per row, usually mapping to Product Image. 
                    // If user provides specific variant image logic, we'd need another column. 
                    // For now, let's assume image_url on row applies to Product if new, or ignored if variant?
                    // "Expert" decision: If product exists, maybe update image? No, safer to leave.
                    // Let's assume URL is for the PRODUCT.

                    $product->variants()->create([
                        'unit_id' => $row['unit_id'],
                        'unit_value' => trim((string) $row['unit_value']) !== '' ? $row['unit_value'] : null,
                        'sku' => $sku,
                        'selling_price' => $row['selling_price'],
                        'limit_price' => $row['limit_price'] !== null ? $row['limit_price'] : null,
                        'quantity' => 0,
                        'alert_quantity' => $row['alert_quantity'],
                        // 'image' => ... // Variant specific image not in simple template yet
                    ]);
                    $count++;
                }
            }
        });

        session()->forget('product_import_preview_data');

        return redirect()->route('products.index')->with('success', "Successfully processed $count variants.");
    }

    private function normalizeLookup(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function readRawColumn(array $row, ?int $index)
    {
        if ($index === null) {
            return null;
        }

        return $row[$index] ?? null;
    }

    private function readTextColumn(array $row, ?int $index): ?string
    {
        if ($index === null) {
            return null;
        }

        $value = trim((string) ($row[$index] ?? ''));
        return $value === '' ? null : $value;
    }

    private function resolveImportColumnMap(array $headerRow): array
    {
        $normalizedHeaders = [];
        foreach ($headerRow as $index => $header) {
            $normalizedHeaders[$this->normalizeImportHeader($header)] = $index;
        }

        $map = [
            'name' => $this->findHeaderIndex($normalizedHeaders, ['product name', 'name']),
            'category' => $this->findHeaderIndex($normalizedHeaders, ['category']),
            'sub_category' => $this->findHeaderIndex($normalizedHeaders, ['sub category', 'subcategory']),
            'description' => $this->findHeaderIndex($normalizedHeaders, ['description']),
            'unit_name' => $this->findHeaderIndex($normalizedHeaders, ['unit name', 'unit']),
            'unit_value' => $this->findHeaderIndex($normalizedHeaders, ['unit value', 'value']),
            'sku' => $this->findHeaderIndex($normalizedHeaders, ['sku']), // legacy files
            'selling_price' => $this->findHeaderIndex($normalizedHeaders, ['selling price', 'price']),
            'limit_price' => $this->findHeaderIndex($normalizedHeaders, ['limit price']),
            'quantity' => $this->findHeaderIndex($normalizedHeaders, ['quantity', 'qty']),
            'alert_quantity' => $this->findHeaderIndex($normalizedHeaders, ['alert quantity', 'alert qty', 'alert']),
            'image_url' => $this->findHeaderIndex($normalizedHeaders, ['image url', 'image']),
        ];

        // Fallback for files without recognizable headers.
        if ($map['name'] === null || $map['category'] === null || $map['unit_name'] === null) {
            $columnCount = count($headerRow);
            $hasLegacySkuColumn = $columnCount >= 12;
            $hasLegacyQuantityColumn = $columnCount >= ($hasLegacySkuColumn ? 12 : 11);

            return [
                'name' => 0,
                'category' => 1,
                'sub_category' => 2,
                'description' => 3,
                'unit_name' => 4,
                'unit_value' => 5,
                'sku' => $hasLegacySkuColumn ? 6 : null,
                'selling_price' => $hasLegacySkuColumn ? 7 : 6,
                'limit_price' => $hasLegacySkuColumn ? 8 : 7,
                'quantity' => $hasLegacyQuantityColumn ? ($hasLegacySkuColumn ? 9 : 8) : null,
                'alert_quantity' => $hasLegacySkuColumn
                    ? ($hasLegacyQuantityColumn ? 10 : 9)
                    : ($hasLegacyQuantityColumn ? 9 : 8),
                'image_url' => $hasLegacySkuColumn
                    ? ($hasLegacyQuantityColumn ? 11 : 10)
                    : ($hasLegacyQuantityColumn ? 10 : 9),
            ];
        }

        return $map;
    }

    private function findHeaderIndex(array $normalizedHeaders, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $normalizedHeaders)) {
                return $normalizedHeaders[$candidate];
            }
        }

        return null;
    }

    private function normalizeImportHeader($header): string
    {
        $normalized = mb_strtolower(trim((string) $header));
        $normalized = str_replace('*', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim((string) $normalized);
    }

    private function generateUniqueSku(string $productName, ?string $unitValue, ?string $unitShortName, array &$reservedSkus): string
    {
        $nameToken = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', Str::ascii($productName)), 0, 3));
        $nameToken = str_pad($nameToken !== '' ? $nameToken : 'PRO', 3, 'X');

        $valueToken = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $unitValue));
        $unitToken = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $unitShortName));
        $specToken = $valueToken . $unitToken;
        $specToken = $specToken !== '' ? substr($specToken, 0, 10) : 'GEN';

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $suffix = strtoupper(Str::random(4));
            $sku = "SK{$nameToken}-{$specToken}-{$suffix}";
            $normalizedSku = mb_strtolower($sku);

            if (in_array($normalizedSku, $reservedSkus, true)) {
                continue;
            }

            $reservedSkus[] = $normalizedSku;
            return $sku;
        }

        throw new \RuntimeException('Unable to generate unique SKU.');
    }

    private function markConflictingCategoryRows(array &$previewData): void
    {
        $rowsByNormalizedName = [];

        foreach ($previewData as $index => $row) {
            $normalizedName = $this->normalizeLookup($row['name'] ?? '');
            if ($normalizedName === '') {
                continue;
            }
            $rowsByNormalizedName[$normalizedName][] = $index;
        }

        foreach ($rowsByNormalizedName as $rowIndexes) {
            $categoryIds = collect($rowIndexes)
                ->map(fn ($rowIndex) => $previewData[$rowIndex]['category_id'] ?? null)
                ->filter(fn ($categoryId) => !is_null($categoryId))
                ->unique()
                ->values();

            if ($categoryIds->count() <= 1) {
                continue;
            }

            foreach ($rowIndexes as $rowIndex) {
                $previewData[$rowIndex]['errors']['category'] = 'Conflicting category for same product name in file';
            }
        }
    }
}
