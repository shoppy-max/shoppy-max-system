<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    protected $request;

    public function __construct(
        $request,
        private bool $includeDirectPrice = true,
        private bool $includeResellerPrice = true
    ) {
        $this->request = $request;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = \App\Models\ProductVariant::query()->with(['product.category', 'product.subCategory', 'unit']);
        [$selectedUnitId, $selectedUnitValue, $isValueSpecificUnitFilter] = $this->resolveVariantUnitFilter();

        // Check for specific product IDs (Bulk Export Selected)
        if (isset($this->request['product_ids']) && $this->request['product_ids']) {
            $ids = explode(',', $this->request['product_ids']);
            $query->whereIn('product_id', $ids);
        }

        if (isset($this->request['search']) && $this->request['search']) {
            $search = $this->request['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($pq) use ($search) {
                    $pq->where('name', 'like', "%{$search}%")
                        ->orWhere('barcode_data', 'like', "%{$search}%");
                })
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (isset($this->request['category_id']) && $this->request['category_id']) {
            $query->whereHas('product', function ($q) {
                $q->where('category_id', $this->request['category_id']);
            });
        }

        if (isset($this->request['sub_category_id']) && $this->request['sub_category_id']) {
            $query->whereHas('product', function ($q) {
                $q->where('sub_category_id', $this->request['sub_category_id']);
            });
        }

        if ($selectedUnitId) {
            // Unit/value filter should export only in-stock matching variants.
            $query->where('unit_id', $selectedUnitId)
                ->where('quantity', '>', 0);

            if ($isValueSpecificUnitFilter) {
                if ($selectedUnitValue === '') {
                    $query->where(function ($variantQuery) {
                        $variantQuery->whereNull('unit_value')
                            ->orWhere('unit_value', '');
                    });
                } else {
                    $query->where('unit_value', $selectedUnitValue);
                }
            }
        }

        return $query->get();
    }

    public function headings(): array
    {
        $headings = [
            'Product ID',
            'Variant ID',
            'Product Name',
            'Category',
            'Sub Category',
            'Unit',
            'SKU',
        ];

        if ($this->includeDirectPrice) {
            $headings[] = 'Direct Price';
        }

        if ($this->includeResellerPrice) {
            $headings[] = 'Reseller Limit Price';
        }

        return [
            ...$headings,
            'Quantity',
            'Alert Quantity',
            'Product Created At',
        ];
    }

    public function map($variant): array
    {
        $row = [
            $variant->product->id,
            $variant->id,
            $variant->product->name,
            $variant->product->category->name ?? '',
            $variant->product->subCategory->name ?? '',
            $variant->unit->name ?? '',
            $variant->sku,
        ];

        if ($this->includeDirectPrice) {
            $row[] = number_format($variant->selling_price, 2);
        }

        if ($this->includeResellerPrice) {
            $row[] = $variant->limit_price !== null ? number_format($variant->limit_price, 2) : '';
        }

        return [
            ...$row,
            $variant->quantity,
            $variant->alert_quantity,
            $variant->product->created_at->format('Y-m-d H:i:s'),
        ];
    }

    private function resolveVariantUnitFilter(): array
    {
        if (! empty($this->request['variant_unit'])) {
            [$rawUnitId, $encodedValue] = array_pad(explode('::', (string) $this->request['variant_unit'], 2), 2, '');

            if (ctype_digit($rawUnitId)) {
                return [(int) $rawUnitId, urldecode($encodedValue), true];
            }
        }

        if (! empty($this->request['unit_id'])) {
            return [(int) $this->request['unit_id'], null, false];
        }

        return [null, null, false];
    }
}
