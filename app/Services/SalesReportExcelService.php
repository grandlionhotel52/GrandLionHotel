<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;
use RuntimeException;
use Throwable;

class SalesReportExcelService
{
    public function create(array $report): string
    {
        $rows = [];
        $merges = [];
        $rowNumber = 0;

        $addRow = function (array $cells, float $height = 18) use (&$rows, &$merges, &$rowNumber): void {
            $rowNumber++;
            $column = 1;
            $xml = '<row r="'.$rowNumber.'" ht="'.$height.'" customHeight="1">';

            foreach ($cells as $cell) {
                $value = $cell['value'] ?? '';
                $type = $cell['type'] ?? 'string';
                $style = (int) ($cell['style'] ?? 0);
                $mergeAcross = max(1, (int) ($cell['merge'] ?? 1));
                $reference = $this->columnName($column).$rowNumber;

                if ($type === 'number') {
                    $xml .= '<c r="'.$reference.'" s="'.$style.'"><v>'.(float) $value.'</v></c>';
                } else {
                    $xml .= '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->escape((string) $value).'</t></is></c>';
                }

                if ($mergeAcross > 1) {
                    $merges[] = $reference.':'.$this->columnName($column + $mergeAcross - 1).$rowNumber;
                }

                $column += $mergeAcross;
            }

            $rows[] = $xml.'</row>';
        };

        $text = static fn (string $value, int $style = 0, int $merge = 1): array => compact('value', 'style', 'merge') + ['type' => 'string'];
        $number = static fn (float|int $value, int $style = 5): array => compact('value', 'style') + ['type' => 'number'];
        $blankRow = static fn (): array => [];

        $addRow([$text('The Grand Lion Hotel - Sales Report', 1, 8)], 28);
        $addRow([$text('Date Range', 2), $text($report['selectedRangeLabel'], 2, 7)]);
        $addRow([$text('Payment Method', 2), $text($report['method'] === 'all' ? 'All methods' : Payment::methodLabel((string) $report['method']), 2, 7)]);
        $addRow([$text('Generated At', 2), $text(now()->format('M d, Y h:i A'), 2, 7)]);
        $addRow($blankRow());

        $summary = $report['summary'];
        $addRow([$text('SUMMARY', 3, 8)], 22);
        $addRow([$text('Metric', 4), $text('Value', 4)]);
        foreach ([
            ['Total Sales', $summary['gross_revenue'], false],
            ['Room Sales', $summary['room_sales'], false],
            ['Food Sales', $summary['food_sales'], false],
            ['Paid Bookings', $summary['paid_bookings'], true],
            ['Average Sale', $summary['average_sale'], false],
            ['Discount Total', $summary['total_discount'], false],
            ['VAT-Exempt Sales', $summary['vat_exempt_sales'], false],
            ['VAT (12/112)', $summary['vat_total'], false],
            ['Local Tax (5%)', $summary['local_tax_total'], false],
            ['Net Sales', $summary['net_sales_excluding_vat'], false],
        ] as [$label, $value, $isInteger]) {
            $addRow([$text($label), $number($value, $isInteger ? 6 : 5)]);
        }
        $addRow($blankRow());

        $addRow([$text('DAILY SALES', 3, 8)], 22);
        $addRow(array_map(static fn (string $label): array => $text($label, 4), [
            'Date', 'Paid Bookings', 'Discount Total', 'Net Sales', 'VAT', 'Local Tax', 'Gross', 'Collected',
        ]));
        foreach ($report['dailySales'] as $day) {
            $addRow([
                $text(Carbon::parse($day->date)->format('M d, Y'), 7),
                $number((int) $day->paid_bookings, 6),
                $number((float) $day->discount_total),
                $number((float) $day->net_sales_excluding_vat),
                $number((float) $day->vat_total),
                $number((float) $day->local_tax_total),
                $number((float) $day->gross_revenue),
                $number((float) $day->revenue),
            ]);
        }
        $addRow($blankRow());

        $addRow([$text('PAYMENT METHODS', 3, 8)], 22);
        $addRow(array_map(static fn (string $label): array => $text($label, 4), ['Method', 'Paid Bookings', 'Gross', 'Collected']));
        foreach ($report['methodBreakdown'] as $item) {
            $addRow([
                $text(Payment::methodLabel((string) $item->method)),
                $number((int) $item->paid_bookings, 6),
                $number((float) $item->gross_revenue),
                $number((float) $item->revenue),
            ]);
        }
        $addRow($blankRow());

        $addRow([$text('GUEST CARE STAFF PERFORMANCE', 3, 8)], 22);
        $addRow(array_map(static fn (string $label): array => $text($label, 4), ['Guest Care Staff', 'Paid Bookings', 'Gross', 'Collected']));
        foreach ($report['staffBreakdown'] as $item) {
            $addRow([
                $text((string) $item->staff_name),
                $number((int) $item->paid_bookings, 6),
                $number((float) $item->gross_revenue),
                $number((float) $item->revenue),
            ]);
        }
        $addRow($blankRow());

        $addRow([$text('PAID TRANSACTIONS', 3, 8)], 22);
        $addRow(array_map(static fn (string $label): array => $text($label, 4), [
            'Booking', 'Paid At', 'Method', 'Guest Care Staff', 'Amount', 'Transaction Reference',
        ]));
        foreach ($report['recentSales'] as $sale) {
            $staffName = trim((string) ($sale->assigned_staff_name ?? ''));
            $addRow([
                $text('#'.(int) $sale->booking_id, 7),
                $text(Carbon::parse($sale->paid_at)->format('M d, Y h:i A'), 7),
                $text(Payment::methodLabel((string) $sale->method)),
                $text($staffName !== '' ? $staffName : 'Not recorded'),
                $number((float) $sale->amount),
                $text((string) ($sale->transaction_reference ?? 'Not available'), 7),
            ]);
        }

        $sheetXml = $this->worksheetXml($rows, $merges, $rowNumber);
        $temporaryBase = tempnam(sys_get_temp_dir(), 'glh-sales-');
        if ($temporaryBase === false) {
            throw new RuntimeException('Unable to create the Excel export.');
        }

        @unlink($temporaryBase);
        $archivePath = $temporaryBase.'.zip';

        try {
            $this->writeZip($archivePath, $this->workbookFiles($sheetXml));

            return $archivePath;
        } catch (Throwable $exception) {
            @unlink($archivePath);
            throw $exception;
        }
    }

    private function writeZip(string $archivePath, array $files): void
    {
        $archive = '';
        $centralDirectory = '';
        $offset = 0;
        $entryCount = 0;
        $now = getdate();
        $dosTime = (($now['hours'] & 0x1f) << 11) | (($now['minutes'] & 0x3f) << 5) | ((int) ($now['seconds'] / 2) & 0x1f);
        $dosDate = (((max(1980, $now['year']) - 1980) & 0x7f) << 9) | (($now['mon'] & 0x0f) << 5) | ($now['mday'] & 0x1f);

        foreach ($files as $path => $contents) {
            $fileName = str_replace('\\', '/', (string) $path);
            $data = (string) $contents;
            $size = strlen($data);
            $crc = crc32($data);
            $nameLength = strlen($fileName);

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                $nameLength,
                0
            );

            $archive .= $localHeader.$fileName.$data;
            $centralDirectory .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                $nameLength,
                0,
                0,
                0,
                0,
                0,
                $offset
            ).$fileName;

            $offset += strlen($localHeader) + $nameLength + $size;
            $entryCount++;
        }

        $centralOffset = strlen($archive);
        $centralSize = strlen($centralDirectory);
        $endOfDirectory = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $entryCount,
            $entryCount,
            $centralSize,
            $centralOffset,
            0
        );

        if (file_put_contents($archivePath, $archive.$centralDirectory.$endOfDirectory) === false) {
            throw new RuntimeException('Unable to write the Excel export.');
        }
    }

    private function worksheetXml(array $rows, array $merges, int $lastRow): string
    {
        $mergeXml = $merges === []
            ? ''
            : '<mergeCells count="'.count($merges).'">'.implode('', array_map(
                fn (string $range): string => '<mergeCell ref="'.$range.'"/>',
                $merges
            )).'</mergeCells>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            .'<dimension ref="A1:H'.$lastRow.'"/>'
            .'<sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="18"/>'
            .'<cols><col min="1" max="1" width="22" customWidth="1"/><col min="2" max="2" width="23" customWidth="1"/>'
            .'<col min="3" max="4" width="22" customWidth="1"/><col min="5" max="8" width="19" customWidth="1"/></cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'.$mergeXml
            .'<printOptions horizontalCentered="1"/>'
            .'<pageMargins left="0.25" right="0.25" top="0.4" bottom="0.4" header="0.2" footer="0.2"/>'
            .'<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            .'</worksheet>';
    }

    private function workbookFiles(string $sheetXml): array
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
                .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
                .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
                .'</Relationships>',
            'docProps/app.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
                .'<Application>The Grand Lion Hotel</Application></Properties>',
            'docProps/core.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                .'<dc:creator>The Grand Lion Hotel</dc:creator><dc:title>Sales Report</dc:title>'
                .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:created></cp:coreProperties>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<bookViews><workbookView/></bookViews><sheets><sheet name="Sales Report" sheetId="1" r:id="rId1"/></sheets>'
                .'<calcPr calcId="191029"/></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                .'</Relationships>',
            'xl/styles.xml' => $this->stylesXml(),
            'xl/worksheets/sheet1.xml' => $sheetXml,
        ];
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="₱#,##0.00"/></numFmts>'
            .'<fonts count="4"><font><sz val="10"/><name val="Aptos"/></font>'
            .'<font><b/><sz val="16"/><color rgb="FF078443"/><name val="Aptos Display"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Aptos"/></font>'
            .'<font><b/><sz val="10"/><color rgb="FF1F2530"/><name val="Aptos"/></font></fonts>'
            .'<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF92713C"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF3EBDD"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border/><border><left style="thin"><color rgb="FFD9C8A8"/></left><right style="thin"><color rgb="FFD9C8A8"/></right>'
            .'<top style="thin"><color rgb="FFD9C8A8"/></top><bottom style="thin"><color rgb="FFD9C8A8"/></bottom></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="8">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="1" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function columnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)).$name;
            $column = intdiv($column, 26);
        }

        return $name;
    }

    private function escape(string $value): string
    {
        $safe = preg_match('/^[=+\-@]/u', $value) === 1 ? "'".$value : $value;

        return htmlspecialchars($safe, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
