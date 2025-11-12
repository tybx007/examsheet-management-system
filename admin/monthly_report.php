<?php
session_start();
include('../config/db.php');
require('../fpdf186/fpdf.php');

// Only admin access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$month = $_GET['month'] ?? date('m');
$year  = $_GET['year'] ?? date('Y');

// --- Collect all transaction data ---
$transactions = [];

/* ----------------------------------------------------
   INVENTORY ADDITIONS (using new column `date_added`)
---------------------------------------------------- */
$sql = "SELECT department_name, sheet_type, quantity, date_added 
        FROM inventory 
        WHERE MONTH(date_added)=$month AND YEAR(date_added)=$year";
$res = $conn->query($sql);
while($row = $res->fetch_assoc()){
    $transactions[] = [
        'date' => $row['date_added'],
        'department' => $row['department_name'] ?? 'N/A',
        'sheet_type' => $row['sheet_type'],
        'quantity' => $row['quantity'],
        'action' => 'Added to Inventory',
        'status' => 'Completed'
    ];
}

/* ----------------------------------------------------
   REQUESTS
---------------------------------------------------- */
$sql = "SELECT department_name, sheet_type, quantity, status, date_requested
        FROM requests 
        WHERE MONTH(date_requested)=$month AND YEAR(date_requested)=$year";
$res = $conn->query($sql);
while($row = $res->fetch_assoc()){
    $transactions[] = [
        'date' => $row['date_requested'],
        'department' => $row['department_name'],
        'sheet_type' => $row['sheet_type'],
        'quantity' => $row['quantity'],
        'action' => 'Request',
        'status' => ucfirst($row['status'])
    ];
}

/* ----------------------------------------------------
   RETURNS
---------------------------------------------------- */
$sql = "SELECT department, sheet_type, quantity, return_date 
        FROM return_history 
        WHERE MONTH(return_date)=$month AND YEAR(return_date)=$year";
$res = $conn->query($sql);
while($row = $res->fetch_assoc()){
    $transactions[] = [
        'date' => $row['return_date'],
        'department' => $row['department'],
        'sheet_type' => $row['sheet_type'],
        'quantity' => $row['quantity'],
        'action' => 'Return',
        'status' => 'Completed'
    ];
}

/* ----------------------------------------------------
   REJECTIONS
---------------------------------------------------- */
$sql = "SELECT r.rejection_cause, rq.department_name, rq.sheet_type, rq.quantity, r.date_rejected 
        FROM rejections r
        JOIN requests rq ON rq.id = r.request_id
        WHERE MONTH(r.date_rejected)=$month AND YEAR(r.date_rejected)=$year";
$res = $conn->query($sql);
while($row = $res->fetch_assoc()){
    $transactions[] = [
        'date' => $row['date_rejected'],
        'department' => $row['department_name'],
        'sheet_type' => $row['sheet_type'],
        'quantity' => $row['quantity'],
        'action' => 'Rejected',
        'status' => $row['rejection_cause']
    ];
}

// Sort by date
usort($transactions, fn($a, $b) => strcmp($a['date'], $b['date']));

/* ----------------------------------------------------
   PDF GENERATION
---------------------------------------------------- */
if (isset($_GET['generate_pdf'])) {
    $pdf = new FPDF();
    $pdf->AddPage('L');
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,"Monthly Transaction Report",0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8,"For ".date("F", mktime(0,0,0,$month,1))." $year",0,1,'C');
    $pdf->Ln(8);

    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(30,8,'Date',1);
    $pdf->Cell(50,8,'Department',1);
    $pdf->Cell(50,8,'Sheet Type',1);
    $pdf->Cell(25,8,'Quantity',1);
    $pdf->Cell(45,8,'Action',1);
    $pdf->Cell(60,8,'Status/Remarks',1);
    $pdf->Ln();

    $pdf->SetFont('Arial','',10);
    foreach ($transactions as $t) {
        $pdf->Cell(30,8,substr($t['date'],0,10),1);
        $pdf->Cell(50,8,$t['department'],1);
        $pdf->Cell(50,8,$t['sheet_type'],1);
        $pdf->Cell(25,8,$t['quantity'],1,0,'C');
        $pdf->Cell(45,8,$t['action'],1);
        $pdf->Cell(60,8,$t['status'],1);
        $pdf->Ln();
    }

    $pdf->Output('D',"Transaction_Report_{$month}_{$year}.pdf");
    exit;
}

include('../includes/header.php');
?>

<div class="report-container">
    <h2>📊 Monthly Transaction Flow</h2>

    <form method="GET" class="filter-form">
        <label>Month:</label>
        <select name="month">
            <?php for ($m=1; $m<=12; $m++): ?>
                <option value="<?= $m ?>" <?= ($m==$month?'selected':'') ?>><?= date("F", mktime(0,0,0,$m,1)) ?></option>
            <?php endfor; ?>
        </select>

        <label>Year:</label>
        <select name="year">
            <?php for ($y=date('Y')-3; $y<=date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= ($y==$year?'selected':'') ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>

        <button type="submit">View</button>
        <button type="submit" name="generate_pdf" value="1" class="pdf-btn">Generate PDF</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Department</th>
                <th>Sheet Type</th>
                <th>Quantity</th>
                <th>Action</th>
                <th>Status / Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($transactions)): ?>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars(substr($t['date'],0,10)) ?></td>
                        <td><?= htmlspecialchars($t['department']) ?></td>
                        <td><?= htmlspecialchars($t['sheet_type']) ?></td>
                        <td><?= htmlspecialchars($t['quantity']) ?></td>
                        <td><?= htmlspecialchars($t['action']) ?></td>
                        <td><?= htmlspecialchars($t['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;">
                        No transactions found in <?= date("F", mktime(0,0,0,$month,1)) ?> <?= $year ?>.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include('../includes/footer.php'); ?>

