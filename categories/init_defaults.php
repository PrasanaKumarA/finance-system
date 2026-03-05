<?php
/**
 * Seed default categories for a user if they have none.
 * Income: Salary, Business, Interest
 * Expense: Food, Rent, Travel
 */
function seed_default_categories($conn, $user_id)
{
    $user_id = intval($user_id);

    // Check if user already has categories
    $check = $conn->prepare("SELECT COUNT(*) as cnt FROM categories WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $result = $check->get_result();
    $row = $result->fetch_assoc();
    $check->close();

    if ($row['cnt'] > 0) {
        return; // User already has categories
    }

    $defaults = [
        ['Salary', 'Income'],
        ['Business', 'Income'],
        ['Interest', 'Income'],
        ['Food', 'Expense'],
        ['Rent', 'Expense'],
        ['Travel', 'Expense'],
    ];

    $stmt = $conn->prepare("INSERT INTO categories (user_id, category_name, type) VALUES (?, ?, ?)");
    foreach ($defaults as $cat) {
        $stmt->bind_param("iss", $user_id, $cat[0], $cat[1]);
        $stmt->execute();
    }
    $stmt->close();
}
?>