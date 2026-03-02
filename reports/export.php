<?php
include "../includes/auth.php";
include "../includes/db.php";

$user_id = $_SESSION['user_id'];

$type = $_GET['type'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$format = $_GET['format'] ?? 'pdf';

$query = "
    SELECT t.*, a.account_name, c.category_name
    FROM transactions t
    JOIN accounts a ON t.account_id = a.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = $user_id
";

if ($from && $to) {
    $query .= " AND t.transaction_date BETWEEN '$from' AND '$to'";
}
if ($type) {
    $query .= " AND t.type = '$type'";
}

$query .= " ORDER BY t.transaction_date DESC";

$result = mysqli_query($conn, $query);

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}

/* ================= EXCEL EXPORT ================= */
if ($format == "excel") {

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=finance_report.xls");

    echo "Finance Report\n";
    echo "From: " . ($from ?: "All") . "\n";
    echo "To: " . ($to ?: "All") . "\n";
    echo "Type: " . ($type ?: "All") . "\n\n";

    echo "Date\tAccount\tCategory\tType\tAmount\n";

    foreach ($rows as $r) {
        echo $r['transaction_date'] . "\t" .
            $r['account_name'] . "\t" .
            $r['category_name'] . "\t" .
            $r['type'] . "\t" .
            $r['amount'] . "\n";
    }
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Finance Report</title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }

        h2 {
            text-align: center;
        }

        p {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f2f2f2;
        }

        button {
            padding: 10px 15px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 15px;
        }

        @media print {
            button {
                display: none;
            }
        }
    </style>
</head>

<body>

    <h2>Finance Report</h2>

    <p>
        <strong>From:</strong> <?= $from ?: 'All' ?> |
        <strong>To:</strong> <?= $to ?: 'All' ?> |
        <strong>Type:</strong> <?= $type ?: 'All' ?>
    </p>

    <button onclick="window.print()">Download PDF</button>

    <table>
        <tr>
            <th>Date</th>
            <th>Account</th>
            <th>Category</th>
            <th>Type</th>
            <th>Amount</th>
        </tr>

                <?php if (count($rows) > 0) { ?>
                    <?php foreach ($rows as $r) { ?>
                <tr>
                    <td><?= $r['transaction_date'] ?></td>
                    <td><?= $r['account_name'] ?></td>
                    <td><?= $r['category_name'] ?></td>
                    <td><?= $r['type'] ?></td>
                    <td><?= number_format($r['amount'], 2) ?></td>
                </tr>
                    <?php } ?>
                <?php } else { ?>
            <tr>
                <td colspan="5" style="text-align:center;">No records found.</td>
            </tr>
                <?php } ?>

    </table>

</body>

</html>