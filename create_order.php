<?php
header("Content-Type: application/json");

include 'db.php';


$customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : '';
$product_names = isset($_POST['product_names']) ? explode(',', $_POST['product_names']) : [];
$quantities = isset($_POST['quantities']) ? explode(',', $_POST['quantities']) : [];


if (empty($customer_name) || empty($product_names) || empty($quantities) || count($product_names) !== count($quantities)) {
    echo json_encode(["error" => "Invalid input"]);
    exit;
}


mysqli_begin_transaction($conn);

try {

    $sql_order = "INSERT INTO orders (customer_name) VALUES ('$customer_name')";
    if (!mysqli_query($conn, $sql_order)) {
        throw new Exception("Order insertion failed: " . mysqli_error($conn));
    }
    $order_id = mysqli_insert_id($conn);


    for ($i = 0; $i < count($product_names); $i++) {
        $product_name = $product_names[$i];
        $quantity = $quantities[$i];


        $sql_product = "SELECT product_id FROM products WHERE name = '$product_name'";
        $result = mysqli_query($conn, $sql_product);

        if (mysqli_num_rows($result) == 0) {
            throw new Exception("Product not found: $product_name");
        }

        $product = mysqli_fetch_assoc($result);
        $product_id = $product['product_id'];


        $sql_item = "INSERT INTO order_items (order_id, product_id, quantity) VALUES ('$order_id', '$product_id', '$quantity')";
        if (!mysqli_query($conn, $sql_item)) {
            throw new Exception("Order item insertion failed: " . mysqli_error($conn));
        }
    }

    mysqli_commit($conn);
    echo json_encode(["success" => true, "order_id" => $order_id]);

} catch (Exception $e) {

    mysqli_rollback($conn);
    echo json_encode(["error" => $e->getMessage()]);
}

mysqli_close($conn);
?>
