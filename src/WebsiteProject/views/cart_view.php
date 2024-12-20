<?php
session_start();

// Include configuration and required files
include_once __DIR__ . '/../utility/config.php';          // Database connection
include_once __DIR__ . '/../views/partials/header.php';  // Header file
include_once __DIR__ . '/../views/partials/navbar.php';  // Navbar file
include_once __DIR__ . '/../controllers/cart_controller.php'; // Cart controller
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function confirmPurchase() {
            if (confirm("Are you sure you want to place this order?")) {
                window.location.href = "<?php echo BASE_URL; ?>/controllers/checkout.php";
            }
        }
    </script>
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Shopping Cart</h1>

        <?php if (empty($cart_details)): ?>
            <div class="alert alert-info">Your cart is empty.</div>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_details as $item): ?>
                        <tr>
                            <td>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($item['photo_blob']); ?>"
                                    alt="Product Image" class="img-thumbnail" style="width: 100px; height: auto;">
                                <br>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td>$<?php echo htmlspecialchars($item['price']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format($item['total'], 2)); ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/controllers/cart_action.php?action=remove&product_id=<?php echo $item['id']; ?>"
                                    class="btn btn-danger btn-sm">Remove One</a>
                                <a href="<?php echo BASE_URL; ?>/controllers/cart_action.php?action=remove_all&product_id=<?php echo $item['id']; ?>"
                                    class="btn btn-warning btn-sm">Remove All</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="d-flex justify-content-between align-items-center">
                <h3>Total Price: $<?php echo number_format($total_price, 2); ?></h3>
                <div>
                    <a href="<?php echo BASE_URL; ?>/controllers/cart_action.php?action=clear" class="btn btn-danger">Clear Cart</a>
                    <button onclick="confirmPurchase()" class="btn btn-success">Buy Now</button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>