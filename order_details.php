<?php
// header("Content-Type: application/json");

// include 'db.php';

// $order_item_id = isset($_GET['order_item_id']) ? intval($_GET['order_item_id']) : 0;

// if ($order_item_id <= 0) {
//     echo json_encode(["error" => "Invalid order item ID."]);
//     exit;
// }

// $sql = "SELECT o.order_id, o.customer_name, o.order_date, 
//                p.name AS product_name, oi.quantity, p.price, 
//                (oi.quantity * p.price) AS total_price 
//         FROM orders o
//         INNER JOIN order_items oi ON o.order_id = oi.order_id
//         INNER JOIN products p ON oi.product_id = p.product_id
//         WHERE oi.order_item_id = $order_item_id";

// $result = mysqli_query($conn, $sql);

// if ($result) {
//     $data = [];
    
//     while ($row = mysqli_fetch_assoc($result)) {
//         $data[] = $row;
//     }
    
//     echo json_encode($data);
// } else {
//     echo json_encode(["error" => "Query failed: " . mysqli_error($conn)]);
// }

// mysqli_close($conn);

header("Content-Type: application/json");

include 'db.php';

// Function to handle API actions
function handleApiAction($action, $conn) {
    switch ($action) {
        case 'displayOrder':
            displayOrder($conn);
            break;
        default:
            echo json_encode(["error" => "Invalid action specified"]);
            break;
    }
}

// Function to display order details by `order_item_id`
function displayOrder($conn) {
    $order_item_id = isset($_GET['order_item_id']) ? intval($_GET['order_item_id']) : 0;

    if ($order_item_id <= 0) {
        echo json_encode(["error" => "Invalid order item ID."]);
        return;
    }

    $sql = "SELECT o.order_id, o.customer_name, o.order_date, 
                   p.name AS product_name, oi.quantity, p.price, 
                   (oi.quantity * p.price) AS total_price 
            FROM orders o
            INNER JOIN order_items oi ON o.order_id = oi.order_id
            INNER JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_item_id = $order_item_id";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        echo json_encode($data);
    } else {
        echo json_encode(["error" => "Query failed: " . mysqli_error($conn)]);
    }
}

// Main API logic
$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($action) {
    handleApiAction($action, $conn);
} else {
    echo json_encode(["error" => "No action specified"]);
}

mysqli_close($conn);
?>

