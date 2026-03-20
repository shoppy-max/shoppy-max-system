<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Canvas;
use Dompdf\FontMetrics;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpFoundation\HeaderUtils;

class WaybillPdfService
{
    private const PT_PER_MM = 72 / 25.4;

    public function download(Collection $orders, string $paperSize, string $filePrefix, $generatedAt): Response
    {
        $orders = $orders->values();

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML('<!DOCTYPE html><html><body></body></html>');

        if ($paperSize === 'four_by_six') {
            $pdf->setPaper([0, 0, $this->mm(101.6), $this->mm(152.4)], 'portrait');
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();

        $barcodeFiles = [];

        try {
            if ($paperSize === 'four_by_six') {
                $this->drawFourBySix($canvas, $fontMetrics, $orders, $generatedAt, $barcodeFiles);
            } else {
                $this->drawA4($canvas, $fontMetrics, $orders, $generatedAt, $barcodeFiles);
            }

            $output = $pdf->output();
        } finally {
            foreach ($barcodeFiles as $file) {
                if (is_string($file) && is_file($file)) {
                    @unlink($file);
                }
            }
        }

        $filename = $filePrefix . '_' . $paperSize . '_' . now()->format('Ymd_His') . '.pdf';

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $filename, $filename),
            'Content-Length' => strlen($output),
        ]);
    }

    private function drawA4(Canvas $canvas, FontMetrics $fontMetrics, Collection $orders, $generatedAt, array &$barcodeFiles): void
    {
        $pageWidth = $canvas->get_width();
        $pageHeight = $canvas->get_height();
        $outerMargin = $this->mm(4.5);
        $gutter = $this->mm(3.5);
        $labelWidth = ($pageWidth - ($outerMargin * 2) - $gutter) / 2;
        $labelHeight = ($pageHeight - ($outerMargin * 2) - $gutter) / 2;

        foreach ($orders as $index => $order) {
            if ($index > 0 && $index % 4 === 0) {
                $canvas->new_page();
            }

            $slot = $index % 4;
            $column = $slot % 2;
            $row = intdiv($slot, 2);

            $x = $outerMargin + ($column * ($labelWidth + $gutter));
            $y = $outerMargin + ($row * ($labelHeight + $gutter));

            $this->drawWaybillLabel($canvas, $fontMetrics, $order, $generatedAt, $x, $y, $labelWidth, $labelHeight, $barcodeFiles);
        }
    }

    private function drawFourBySix(Canvas $canvas, FontMetrics $fontMetrics, Collection $orders, $generatedAt, array &$barcodeFiles): void
    {
        $pageWidth = $canvas->get_width();
        $pageHeight = $canvas->get_height();
        $marginX = $this->mm(5.25);
        $marginY = $this->mm(5.5);
        $labelWidth = $pageWidth - ($marginX * 2);
        $labelHeight = $pageHeight - ($marginY * 2);

        foreach ($orders as $index => $order) {
            if ($index > 0) {
                $canvas->new_page();
            }

            $this->drawWaybillLabel($canvas, $fontMetrics, $order, $generatedAt, $marginX, $marginY, $labelWidth, $labelHeight, $barcodeFiles);
        }
    }

    private function drawWaybillLabel(Canvas $canvas, FontMetrics $fontMetrics, $order, $generatedAt, float $x, float $y, float $width, float $height, array &$barcodeFiles): void
    {
        $scale = min($width / $this->mm(92), $height / $this->mm(138));
        $padding = $this->mm(2.2);
        $gap = $this->mm(1.7);
        $borderColor = [0.07, 0.10, 0.15];
        $mutedColor = [0.42, 0.47, 0.54];
        $fontRegular = $fontMetrics->get_font('Helvetica', 'normal');
        $fontBold = $fontMetrics->get_font('Helvetica', 'bold');

        $innerX = $x + $padding;
        $innerY = $y + $padding;
        $innerWidth = $width - ($padding * 2);
        $innerHeight = $height - ($padding * 2);

        $headerHeight = 28 * $scale;
        $barcodeHeight = 46 * $scale;
        $shipHeight = 48 * $scale;
        $metaHeight = 25 * $scale;
        $itemsHeight = max($innerHeight - $headerHeight - $barcodeHeight - $shipHeight - $metaHeight - ($gap * 4), 22 * $scale);

        $canvas->rectangle($x, $y, $width, $height, $borderColor, 1.15);

        $collectWidth = $innerWidth * 0.36;
        $brandWidth = $innerWidth - $collectWidth - $gap;

        $brandY = $innerY + (11 * $scale);
        $this->drawText($canvas, $fontBold, 11 * $scale, $borderColor, $innerX, $brandY, config('app.name', 'ShoppyMax'));
        $this->drawText(
            $canvas,
            $fontRegular,
            5.6 * $scale,
            $mutedColor,
            $innerX,
            $brandY + (8.2 * $scale),
            trim(($order->courier->name ?? 'Courier') . ' Waybill')
        );

        $collectX = $innerX + $brandWidth + $gap;
        $collectY = $innerY;
        $collectHeight = $headerHeight;

        $canvas->rectangle($collectX, $collectY, $collectWidth, $collectHeight, $borderColor, 0.9);
        $this->drawCenteredText($canvas, $fontBold, 5.2 * $scale, $mutedColor, $collectX, $collectY + (8 * $scale), $collectWidth, 'COLLECT AMOUNT');
        $this->drawCenteredText($canvas, $fontBold, 10 * $scale, $borderColor, $collectX, $collectY + (18 * $scale), $collectWidth, $this->collectAmount($order));

        $currentY = $innerY + $headerHeight + $gap;

        $canvas->rectangle($innerX, $currentY, $innerWidth, $barcodeHeight, $borderColor, 1.0);
        $this->drawCenteredText($canvas, $fontBold, 10 * $scale, $borderColor, $innerX, $currentY + (10 * $scale), $innerWidth, (string) $order->waybill_number);

        $barcodeFile = $this->makeBarcodeImage((string) $order->waybill_number);
        $barcodeFiles[] = $barcodeFile;

        $barcodeWidth = $innerWidth - (10 * $scale);
        $barcodeX = $innerX + (($innerWidth - $barcodeWidth) / 2);
        $barcodeY = $currentY + (20 * $scale);
        $barcodeImageHeight = $barcodeHeight - (25 * $scale);
        $canvas->image($barcodeFile, $barcodeX, $barcodeY, $barcodeWidth, $barcodeImageHeight);

        $currentY += $barcodeHeight + $gap;

        $canvas->rectangle($innerX, $currentY, $innerWidth, $shipHeight, [0.82, 0.85, 0.89], 0.85);
        $shipPadding = 6 * $scale;
        $shipTextX = $innerX + $shipPadding;
        $shipTextY = $currentY + (9 * $scale);
        $this->drawText($canvas, $fontBold, 5.4 * $scale, $mutedColor, $shipTextX, $shipTextY, 'DELIVER TO');
        $this->drawText($canvas, $fontBold, 9.2 * $scale, $borderColor, $shipTextX, $shipTextY + (9 * $scale), (string) $order->customer_name);
        $shipBodyY = $shipTextY + (18 * $scale);
        $this->drawWrappedText($canvas, $fontRegular, 7.0 * $scale, $borderColor, $shipTextX, $shipBodyY, $innerWidth - ($shipPadding * 2), [
            (string) ($order->customer_phone ?? ''),
            (string) ($order->customer_address ?? ''),
            (string) ($order->city->city_name ?? $order->customer_city ?? ''),
        ], 3, 7.6 * $scale);

        $currentY += $shipHeight + $gap;

        $metaGap = 5 * $scale;
        $metaWidth = ($innerWidth - ($metaGap * 2)) / 3;
        $metaItems = [
            ['title' => 'ORDER ID', 'value' => (string) $order->order_number],
            ['title' => 'PAYMENT', 'value' => (string) ($order->payment_method ?? 'N/A')],
            ['title' => 'PRINTED', 'value' => optional($generatedAt)->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i')],
        ];

        foreach ($metaItems as $metaIndex => $meta) {
            $metaX = $innerX + ($metaIndex * ($metaWidth + $metaGap));
            $canvas->rectangle($metaX, $currentY, $metaWidth, $metaHeight, [0.82, 0.85, 0.89], 0.85);
            $this->drawText($canvas, $fontBold, 5.2 * $scale, $mutedColor, $metaX + (4 * $scale), $currentY + (8 * $scale), $meta['title']);
            $this->drawWrappedText(
                $canvas,
                $fontRegular,
                7.1 * $scale,
                $borderColor,
                $metaX + (4 * $scale),
                $currentY + (16 * $scale),
                $metaWidth - (8 * $scale),
                [(string) $meta['value']],
                2,
                7.5 * $scale
            );
        }

        $currentY += $metaHeight + $gap;

        $canvas->rectangle($innerX, $currentY, $innerWidth, $itemsHeight, [0.82, 0.85, 0.89], 0.85);
        $this->drawText($canvas, $fontBold, 5.4 * $scale, $mutedColor, $innerX + (6 * $scale), $currentY + (9 * $scale), 'ITEMS');
        $this->drawWrappedText(
            $canvas,
            $fontRegular,
            6.9 * $scale,
            $borderColor,
            $innerX + (6 * $scale),
            $currentY + (17 * $scale),
            $innerWidth - (12 * $scale),
            [(string) $this->itemsSummary($order)],
            4,
            7.4 * $scale
        );
    }

    private function collectAmount($order): string
    {
        $collectible = max((float) ($order->total_amount ?? 0) - (float) ($order->paid_amount ?? 0), 0);

        if (strtoupper((string) ($order->payment_method ?? '')) !== 'COD' || $collectible <= 0) {
            return 'Prepaid';
        }

        return 'Rs. ' . number_format($collectible, 2);
    }

    private function itemsSummary($order): string
    {
        return $order->items
            ->map(fn ($item) => trim($item->quantity . ' x ' . $item->product_name))
            ->filter()
            ->implode(', ');
    }

    private function makeBarcodeImage(string $code): string
    {
        $path = tempnam(sys_get_temp_dir(), 'wb_');
        $generator = new BarcodeGeneratorPNG();
        file_put_contents($path, $generator->getBarcode($code, $generator::TYPE_CODE_128, 2, 56));

        return $path;
    }

    private function drawText(Canvas $canvas, string $font, float $size, array $color, float $x, float $baselineY, string $text): void
    {
        $canvas->text($x, $baselineY, $text, $font, $size, $color);
    }

    private function drawCenteredText(Canvas $canvas, string $font, float $size, array $color, float $x, float $baselineY, float $width, string $text): void
    {
        $textWidth = $canvas->get_text_width($text, $font, $size);
        $textX = $x + max(($width - $textWidth) / 2, 0);
        $canvas->text($textX, $baselineY, $text, $font, $size, $color);
    }

    private function drawWrappedText(
        Canvas $canvas,
        string $font,
        float $size,
        array $color,
        float $x,
        float $baselineY,
        float $maxWidth,
        array $chunks,
        int $maxLines,
        float $lineAdvance
    ): void {
        $lines = $this->wrapChunks($canvas, $font, $size, $maxWidth, $chunks, $maxLines);

        foreach ($lines as $index => $line) {
            $canvas->text($x, $baselineY + ($index * $lineAdvance), $line, $font, $size, $color);
        }
    }

    private function wrapChunks(Canvas $canvas, string $font, float $size, float $maxWidth, array $chunks, int $maxLines): array
    {
        $lines = [];

        foreach ($chunks as $chunk) {
            $text = trim((string) $chunk);
            if ($text === '') {
                continue;
            }

            $words = preg_split('/\s+/', $text) ?: [];
            $current = '';

            foreach ($words as $word) {
                $candidate = $current === '' ? $word : $current . ' ' . $word;

                if ($canvas->get_text_width($candidate, $font, $size) <= $maxWidth) {
                    $current = $candidate;
                    continue;
                }

                if ($current !== '') {
                    $lines[] = $current;
                }

                $current = $word;

                if (count($lines) >= $maxLines) {
                    break 2;
                }
            }

            if ($current !== '') {
                $lines[] = $current;
            }

            if (count($lines) >= $maxLines) {
                break;
            }
        }

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
        }

        if (count($lines) === $maxLines) {
            $lastIndex = $maxLines - 1;
            $lines[$lastIndex] = $this->ellipsize($canvas, $font, $size, $lines[$lastIndex], $maxWidth);
        }

        return $lines;
    }

    private function ellipsize(Canvas $canvas, string $font, float $size, string $text, float $maxWidth): string
    {
        $ellipsis = '...';
        if ($canvas->get_text_width($text, $font, $size) <= $maxWidth) {
            return $text;
        }

        while ($text !== '' && $canvas->get_text_width($text . $ellipsis, $font, $size) > $maxWidth) {
            $text = mb_substr($text, 0, -1);
        }

        return rtrim($text) . $ellipsis;
    }

    private function mm(float $value): float
    {
        return $value * self::PT_PER_MM;
    }
}
