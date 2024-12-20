<?php
session_start();
include_once __DIR__ . '/../utility/config.php'; // Database connection

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must log in to place an order.";
    header("Location: " . BASE_URL . "/views/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$conn->begin_transaction();

try {
    // Fetch cart details
    $stmt = $conn->prepare("SELECT product_id, quantity FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart_items = [];
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
    }
    $stmt->close();

    // Check if cart is empty
    if (empty($cart_items)) {
        throw new Exception("Your cart is empty!");
    }

    // Create an order group
    $stmt = $conn->prepare("INSERT INTO order_groups (user_id, created_at) VALUES (?, NOW())");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $order_group_id = $stmt->insert_id;
    $stmt->close();

    // Insert each cart item into orders table
    $stmt = $conn->prepare("INSERT INTO orders (order_group_id, user_id, product_id, quantity) VALUES (?, ?, ?, ?)");
    foreach ($cart_items as $item) {
        $stmt->bind_param('iiii', $order_group_id, $user_id, $item['product_id'], $item['quantity']);
        $stmt->execute();
    }
    $stmt->close();

    // Clear the cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();

    // Commit transaction
    $conn->commit();

    $_SESSION['order_group_id'] = $order_group_id;
    header("Location: " . BASE_URL . "/views/order_success.php");
    exit;
} catch (Exception $e) {
    // Rollback transaction on failure
    $conn->rollback();
    $_SESSION['message'] = $e->getMessage();
    header("Location: " . BASE_URL . "/views/cart_view.php");
    exit;
}
