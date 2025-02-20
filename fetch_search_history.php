<?php
session_start();
include "databaseConnection.php";

if (!isset($_SESSION["user_id"])) {
    exit();
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT hashtag FROM search_history WHERE user_id = ? ORDER BY searched_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$search_history = "";

while ($row = $result->fetch_assoc()) {
    $hashtag = $row["hashtag"]; 
    $search_history .= "<li class='d-flex justify-content-between align-items-center'>
        <a href='#' class='history-item'>{$hashtag}</a>
        <button class='btn-close btn-close-white delete-history' data-hashtag='{$hashtag}' aria-label='Close'></button>
    </li>";
}

$stmt->close();

echo $search_history ?: "<li style='display: none;'>No search history</li>";
?>
