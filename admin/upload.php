<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['shipment_file'])) {
    try {
        // Validate file
        $file = $_FILES['shipment_file'];
        $allowedTypes = ['application/vnd.ms-excel', 'text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Only Excel (XLSX, XLS) and CSV files are allowed.');
        }

        if ($file['size'] > 10 * 1024 * 1024) { // 10MB max
            throw new Exception('File size exceeds 10MB limit.');
        }

        // Process Excel file
        require_once '../vendor/autoload.php';
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Remove header row
        array_shift($rows);

        // Prepare statement
        $stmt = $pdo->prepare("
            INSERT INTO shipments (
                invoice_number, invoice_date, trans_date, cust_po, ship_via, 
                comment, ship_to_name, item_code, description, 
                qty_ordered, qty_shipped, qty_backorder, pro_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                invoice_date = VALUES(invoice_date),
                trans_date = VALUES(trans_date),
                ship_via = VALUES(ship_via),
                comment = VALUES(comment),
                qty_shipped = VALUES(qty_shipped),
                qty_backorder = VALUES(qty_backorder)
        ");

        // Process each row
        $importedCount = 0;
        $pdo->beginTransaction();
        
        foreach ($rows as $row) {
            // Skip empty rows
            if (empty(array_filter($row))) continue;

            // Format dates (assuming Excel serial dates or Y-m-d format)
            $invoiceDate = is_numeric($row[1]) ? 
                \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[1])->format('Y-m-d') : 
                date('Y-m-d', strtotime($row[1]));
            
            $transDate = is_numeric($row[2]) ? 
                \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[2])->format('Y-m-d') : 
                date('Y-m-d', strtotime($row[2]));

            // Execute insert
            $stmt->execute([
                $row[0] ?? null, // invoice_number
                $invoiceDate,
                $transDate,
                $row[3] ?? null, // cust_po
                $row[4] ?? null, // ship_via
                $row[5] ?? null, // comment
                $row[6] ?? '',   // ship_to_name
                $row[7] ?? '',   // item_code
                $row[8] ?? '',   // description
                (int)($row[9] ?? 0), // qty_ordered
                (int)($row[10] ?? 0), // qty_shipped
                (int)($row[11] ?? 0), // qty_backorder
                $row[12] ?? null // pro_number
            ]);
            
            $importedCount++;
        }

        $pdo->commit();
        $message = "Successfully imported $importedCount shipment records.";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Shipment Data</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <?php include '../includes/admin_nav.php'; ?>
        
        <main class="container mx-auto px-4 py-8">
            <div class="max-w-3xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Upload Shipment Data</h1>
                
                <?php if ($message): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?= htmlspecialchars($message) ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="bg-white shadow rounded-lg p-6">
                    <form action="" method="post" enctype="multipart/form-data" class="space-y-6">
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                            <div class="flex justify-center mb-4">
                                <i class="fas fa-file-excel text-4xl text-green-500"></i>
                            </div>
                            <p class="text-sm text-gray-500 mb-2">Upload Excel or CSV file with shipment data</p>
                            <p class="text-xs text-gray-400 mb-4">Supported formats: .xlsx, .xls, .csv (Max 10MB)</p>
                            <label for="file-upload" class="cursor-pointer bg-blue-50 text-blue-600 hover:bg-blue-100 px-4 py-2 rounded-md text-sm font-medium transition">
                                <span>Select file</span>
                                <input id="file-upload" name="shipment_file" type="file" class="sr-only" accept=".xlsx,.xls,.csv" required>
                            </label>
                            <p id="file-name" class="text-sm text-gray-500 mt-2"></p>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                                <i class="fas fa-upload mr-2"></i> Upload and Process
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="mt-8 bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-semibold mb-4">File Format Requirements</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Column</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Required</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Invoice #</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Unique invoice identifier</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yes</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Invoice Date</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Date in MM/DD/YYYY or Excel date format</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yes</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Trans. Date</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Date in MM/DD/YYYY or Excel date format</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yes</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Cust. PO #</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Customer purchase order number</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">No</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Ship Via</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Shipping carrier/method</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">No</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Comment</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Additional notes</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">No</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Ship To Name</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Customer/company name</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yes</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Item Code</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Product/SKU identifier</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yes</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Description</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Product description</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yes</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Qty. Ordered</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Numeric quantity</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yes</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Qty. Shipped</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Numeric quantity</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yes</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Qty. Backorder</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Numeric quantity</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yes</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">PRO NUMBER</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Tracking/pro number</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">No</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('file-upload').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || 'No file selected';
        document.getElementById('file-name').textContent = fileName;
    });
    </script>
</body>
</html>