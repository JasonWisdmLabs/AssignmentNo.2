<?php
session_start();
include "databaseConnection.php";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["hashtag"])) {
    $user_id = $_SESSION["user_id"];
    $hashtag = trim($_POST["hashtag"]);

    $stmt = $conn->prepare("DELETE FROM search_history WHERE user_id = ? AND hashtag = ?");
    $stmt->bind_param("is", $user_id, $hashtag);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error deleting hashtag";
    }

    $stmt->close();
    $conn->close();
}
?>
