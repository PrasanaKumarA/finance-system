<?php

function getAccountBalance($conn, $account_id)
{

    $opening_query = mysqli_query($conn, "SELECT opening_balance FROM accounts WHERE id=$account_id");
    $opening_row = mysqli_fetch_assoc($opening_query);
    $opening_balance = $opening_row['opening_balance'] ?? 0;

    $income_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE account_id=$account_id AND type='Income'");
    $income = mysqli_fetch_assoc($income_query)['total'] ?? 0;

    $expense_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE account_id=$account_id AND type='Expense'");
    $expense = mysqli_fetch_assoc($expense_query)['total'] ?? 0;

    // Transfer In adds money, Transfer Out removes money
    $transfer_in_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE account_id=$account_id AND type='Transfer' AND description LIKE 'Transfer In%'");
    $transfer_in = mysqli_fetch_assoc($transfer_in_query)['total'] ?? 0;

    $transfer_out_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE account_id=$account_id AND type='Transfer' AND description LIKE 'Transfer Out%'");
    $transfer_out = mysqli_fetch_assoc($transfer_out_query)['total'] ?? 0;

    return $opening_balance + $income - $expense + $transfer_in - $transfer_out;
}


function getTotalBalance($conn, $user_id)
{

    $accounts = mysqli_query($conn, "SELECT id FROM accounts WHERE user_id=$user_id");
    $total = 0;

    while ($row = mysqli_fetch_assoc($accounts)) {
        $total += getAccountBalance($conn, $row['id']);
    }

    return $total;
}


function getBankBalance($conn, $user_id)
{

    $accounts = mysqli_query($conn, "SELECT id FROM accounts WHERE user_id=$user_id AND account_type='Bank'");
    $total = 0;

    while ($row = mysqli_fetch_assoc($accounts)) {
        $total += getAccountBalance($conn, $row['id']);
    }

    return $total;
}


function getCashBalance($conn, $user_id)
{
    $accounts = mysqli_query($conn, "SELECT id FROM accounts WHERE user_id=$user_id AND account_type='Cash'");
    $total = 0;

    while ($row = mysqli_fetch_assoc($accounts)) {
        $total += getAccountBalance($conn, $row['id']);
    }

    return $total;
}

?>