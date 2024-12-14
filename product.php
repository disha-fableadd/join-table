<?php
// header("Content-Type: application/json");

// include 'db.php';

// $sql = "SELECT p.product_id, p.name AS product_name, p.price, c.category_name 
//         FROM products p 
//         INNER JOIN categories c ON p.category_id = c.category_id";
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
        case 'showProduct':
            showProduct($conn);
            break;
        default:
            echo json_encode(["error" => "Invalid action specified"]);
            break;
    }
}

// Function to display products and their categories
function showProduct($conn) {
    $sql = "SELECT p.product_id, p.name AS product_name, p.price, c.category_name 
            FROM products p 
            INNER JOIN categories c ON p.category_id = c.category_id";
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
