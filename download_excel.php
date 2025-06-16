<?php
require 'vendor/autoload.php';
require_once "db_connection.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vertical_id = intval($_POST['vertical_id']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Styles
    $styleHeader = [
        'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '000080']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    $styleFieldRow = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'B0C4DE']] // Light Cadet Blue
    ];

    $styleLightBlueRow = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'ADD8E6']] // Light Blue
    ];

    $styleBlueRow = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '87CEFA']] // Sky Blue
    ];

    // Helpers
    function formatFieldName($name) {
        return ucwords(str_replace("_", " ", $name));
    }

    function getFields($conn, $table, $exclude) {
        $fields = [];
        $res = $conn->query("SHOW COLUMNS FROM $table");
        while ($r = $res->fetch_assoc()) {
            if (!in_array($r['Field'], $exclude)) $fields[] = $r['Field'];
        }
        return $fields;
    }

    function getVendors($conn, $table) {
        $vendors = [];
        $res = $conn->query("SELECT vendor_name FROM $table ORDER BY vendor_name ASC");
        while ($r = $res->fetch_assoc()) $vendors[] = $r['vendor_name'];
        return $vendors;
    }

    function getRow($conn, $table, $pid, $start, $end) {
        $stmt = $conn->prepare("SELECT * FROM $table WHERE process_id = ? AND start_date BETWEEN ? AND ? LIMIT 1");
        $stmt->bind_param("iss", $pid, $start, $end);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?? [];
    }

    function getVendorAmounts($conn, $table, $vendorTable, $pid, $start, $end) {
        $stmt = $conn->prepare("SELECT v.vendor_name, e.amount FROM $vendorTable v LEFT JOIN $table e 
                                ON v.vendor_id = e.vendor_id AND e.process_id = ? AND e.start_date BETWEEN ? AND ?");
        $stmt->bind_param("iss", $pid, $start, $end);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($r = $res->fetch_assoc()) $data[$r['vendor_name']] = $r['amount'] ?? 0;
        return $data;
    }

    $exclude = ['id', 'vertical_id', 'process_id', 'start_date', 'end_date', 'created_at', 'updated_at'];
    $billing = getFields($conn, 'billing', $exclude);
    $infra = getFields($conn, 'infra', $exclude);
    $itVendors = getVendors($conn, 'it_vendors');
    $telecomVendors = getVendors($conn, 'telecom_vendors');

    $sections = [
        "Billing" => $billing,
        "Infra" => $infra,
        "IT Asset and Vendors" => $itVendors,
        "Telecom Expenses" => $telecomVendors
    ];

    // Header
    $sheet->mergeCells('A1:B1')->setCellValue('A1', 'PROFIT & LOSS A/C');
    $sheet->mergeCells('A2:B2')->setCellValue('A2', 'Particulars');
    $sheet->getStyle('A1:B1')->applyFromArray($styleHeader);
    $sheet->getStyle('A2:B2')->applyFromArray($styleHeader);

    // Process Headers
    $stmt = $conn->prepare("SELECT process_id, process_name FROM processes WHERE vertical_id = ?");
    $stmt->bind_param("i", $vertical_id);
    $stmt->execute();
    $processes = $stmt->get_result();

    $colStart = 3;
    $colIndex = $colStart;
    $processIDs = [];

    while ($proc = $processes->fetch_assoc()) {
        $colLetter = Coordinate::stringFromColumnIndex($colIndex);
        $sheet->setCellValue("{$colLetter}2", $proc['process_name']);
        $sheet->getStyle("{$colLetter}2")->applyFromArray($styleHeader);
        $processIDs[] = ['id' => $proc['process_id'], 'col' => $colIndex];
        $colIndex++;
    }

    $totalCol = Coordinate::stringFromColumnIndex($colIndex);
    $sheet->setCellValue("{$totalCol}2", "Total");
    $sheet->getStyle("{$totalCol}2")->applyFromArray($styleHeader);

    // Fill Data Rows
    $rowIndex = 3;

    foreach ($sections as $section => $fields) {
        // Section header in A column only
        $sheet->setCellValue("A{$rowIndex}", strtoupper($section));
        $sheet->getStyle("A{$rowIndex}:B{$rowIndex}")->applyFromArray($styleHeader);
        $rowIndex++;

        // For IT and Telecom totals calculation containers
        $totalByProcess = array_fill(0, count($processIDs), 0);

        foreach ($fields as $field) {
            $label = formatFieldName($field);
            $sheet->setCellValue("B{$rowIndex}", $label);

            $sumRange = [];

            foreach ($processIDs as $idx => $p) {
                $colLetter = Coordinate::stringFromColumnIndex($p['col']);

                if ($section === "Billing") {
                    $data = getRow($conn, "billing", $p['id'], $start_date, $end_date);
                    $val = $data[$field] ?? 0;
                } elseif ($section === "Infra") {
                    $data = getRow($conn, "infra", $p['id'], $start_date, $end_date);
                    $val = $data[$field] ?? 0;
                } elseif ($section === "IT Asset and Vendors") {
                    $data = getVendorAmounts($conn, "it_expenses", "it_vendors", $p['id'], $start_date, $end_date);
                    $val = $data[$field] ?? 0;
                    $totalByProcess[$idx] += $val; // accumulate for IT Total
                } elseif ($section === "Telecom Expenses") {
                    $data = getVendorAmounts($conn, "telecom_expense", "telecom_vendors", $p['id'], $start_date, $end_date);
                    $val = $data[$field] ?? 0;
                    $totalByProcess[$idx] += $val; // accumulate for Telecom Total
                }

                $sheet->setCellValue("{$colLetter}{$rowIndex}", $val);
                $sumRange[] = "{$colLetter}{$rowIndex}";
            }

            // TOTAL column formula
            $sheet->setCellValue("{$totalCol}{$rowIndex}", "=SUM(" . implode(',', $sumRange) . ")");

            // Row coloring
            $rowLabel = strtolower($label);
            if (in_array($rowLabel, ['net profit', 'profit %', 'infra total', 'telecom total'])) {
                $sheet->getStyle("B{$rowIndex}:{$totalCol}{$rowIndex}")->applyFromArray($styleLightBlueRow);
            }
            if ($rowLabel === 'it total') {
                $sheet->getStyle("B{$rowIndex}:{$totalCol}{$rowIndex}")->applyFromArray($styleBlueRow);
            }

            $rowIndex++;
        }

        // Add Total Row for IT Asset and Vendors section
        if ($section === "IT Asset and Vendors") {
            $sheet->setCellValue("B{$rowIndex}", "IT Total");
            // Fill totals per process
            foreach ($processIDs as $idx => $p) {
                $colLetter = Coordinate::stringFromColumnIndex($p['col']);
                $sheet->setCellValue("{$colLetter}{$rowIndex}", $totalByProcess[$idx]);
            }
            // Total sum formula across all processes
            $sumCols = [];
            foreach ($processIDs as $p) {
                $sumCols[] = Coordinate::stringFromColumnIndex($p['col']) . $rowIndex;
            }
            $sheet->setCellValue("{$totalCol}{$rowIndex}", "=SUM(" . implode(',', $sumCols) . ")");
            $sheet->getStyle("B{$rowIndex}:{$totalCol}{$rowIndex}")->applyFromArray($styleLightBlueRow);
            $rowIndex++;
        }

        // Add Total Row for Telecom Expenses section
        if ($section === "Telecom Expenses") {
            $sheet->setCellValue("B{$rowIndex}", "Telecom Total");
            // Fill totals per process
            foreach ($processIDs as $idx => $p) {
                $colLetter = Coordinate::stringFromColumnIndex($p['col']);
                $sheet->setCellValue("{$colLetter}{$rowIndex}", $totalByProcess[$idx]);
            }
            // Total sum formula across all processes
            $sumCols = [];
            foreach ($processIDs as $p) {
                $sumCols[] = Coordinate::stringFromColumnIndex($p['col']) . $rowIndex;
            }
            $sheet->setCellValue("{$totalCol}{$rowIndex}", "=SUM(" . implode(',', $sumCols) . ")");
            $sheet->getStyle("B{$rowIndex}:{$totalCol}{$rowIndex}")->applyFromArray($styleLightBlueRow);
            $rowIndex++;
        }

        $rowIndex++; // Add space after section
    }

    // Output to browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"Profit_Loss_Report.xlsx\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}
?>
