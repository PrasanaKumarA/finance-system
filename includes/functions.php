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

    return $opening_balance + $income - $expense;
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