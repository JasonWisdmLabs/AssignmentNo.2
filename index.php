<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "databaseConnection.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: signin.php"); 
    exit();
}
if (isset($_GET['q'])) {
    $searchQuery = trim($_GET['q']);
} else {
    $searchQuery = ""; 
}

$user_id = $_SESSION["user_id"]; 

$search_history = [];
$stmt = $conn->prepare("SELECT hashtag FROM search_history WHERE user_id = ? ORDER BY searched_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $search_history[] = $row["hashtag"];
}

$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["hashtag"])) {

    $user_id = $_SESSION["user_id"];
    $hashtag = trim($_POST["hashtag"]);

    #Storing the hashtag in the database
    if (!empty($hashtag)) {
        $stmt = $conn->prepare("INSERT INTO search_history (user_id, hashtag) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $hashtag);
        
        if ($stmt->execute()) {
            echo "Hashtag stored successfully";
        } else {
            echo "Error storing hashtag";
        }
        $stmt->close();
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="main-styles.css">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger fixed-top">
        <div class="container-fluid">
            <button class="navbar-toggler" id="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="#">
                <img src="images/Logo.png" alt="Logo" height="40">
            </a>
            <form class="d-flex ms-auto" role="search" id="searchForm">
                <input class="form-control me-2" type="search" id="searchInput" placeholder="Search for Hashtags" value="<?php echo $searchQuery; ?>">
                <button class="btn btn-outline-light" type="submit">Search</button>
            </form>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar" style="z-index:1;" id="sidebar">
        <h4>Search History</h4>
        <ul id="searchHistory">
            <?php if (!empty($search_history)): ?>
                <?php foreach ($search_history as $hashtag): ?>
                    <li class="d-flex justify-content-between align-items-center">
                        <a href="#" class="history-item"><?php echo $hashtag; ?></a>
                        <button class="btn-close btn-close-white delete-history" data-hashtag="<?php echo $hashtag; ?>" aria-label="Close"></button>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li style="display: none;">No search history</li>
            <?php endif; ?>
        </ul>


        <form action="logout.php" method="POST" class="d-grid mt-3">
            <button type="submit" class="btn btn-danger">Logout</button>
        </form>

    </div>

    <div class="container mt-4">
        <div id="welcomeMessage">
            <h1>Hello, <?php echo $_SESSION['username']; ?> 👋</h1>
            <p>Image Finder is a tool to search images by entering a hashtag.</p>
        </div>
            <div id="loadingAnimation" class="text-center mt-5" style="display: none;">
            <div class="spinner-border text-danger" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-danger">Loading images...</p>
        
        </div>
        
        <div class="row" id="imageResults" data-masonry='{"percentPosition": true }'></div>
    </div>

    <!-- Bootstrap JS & jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="config.js"></script>
    <script>

        // Toggle Sidebar Visibility
        $("#navbar-toggler").click(function () {
            $("#sidebar").toggleClass("show");
            $(".content").toggleClass("show");
        });

        $(document).ready(function () {
            
            const urlParams = new URLSearchParams(window.location.search);
            let query = urlParams.get('q');

            if (query) {
                $("#searchInput").val(query);
                $("#welcomeMessage").fadeOut(); // Hide welcome message if search exists
                searchImages(query);
            }

            $("#searchForm").submit(function (event) {
                event.preventDefault();
                let newQuery = $("#searchInput").val().trim();

                if (newQuery !== "") {
                    $("#welcomeMessage").fadeOut();

                    // Update the URL without reloading the page
                    window.history.pushState({}, '', `?q=${encodeURIComponent(newQuery)}`);

                    // Store hashtag in the database
                    $.ajax({
                        url: "index.php",
                        type: "POST",
                        data: { hashtag: newQuery },
                        success: function (response) {
                            console.log("Hashtag stored:", response);
                        },
                        error: function (error) {
                            console.error("Error storing hashtag:", error);
                        }
                    });

                    // Update search history dynamically
                    $.ajax({
                        url: "fetch_search_history.php",
                        type: "GET",
                        success: function (data) {
                            $("#searchHistory").html(data);
                        }
                    });

                    // Fetch images based on the search query
                    searchImages(newQuery);
                }
            });
        });

        //API Callings
        function searchImages(query) {
            $("#loadingAnimation").show();  // Show loading animation
            $("#imageResults").html("");  // Clear previous results

            let unsplashRequest = $.ajax({
                url: `https://api.unsplash.com/search/photos`,
                type: "GET",
                data: {
                    query: query,
                    per_page: 6,
                    order_by: "relevant",
                    client_id: config.UNSPLASH_ACCESS_KEY
                }
            });

            let pexelsRequest = $.ajax({
                url: `https://api.pexels.com/v1/search`,
                type: "GET",
                headers: { Authorization: config.PEXELS_API_KEY },
                data: {
                    query: query,
                    per_page: 6
                }
            });

            let openverseRequest = $.ajax({
                url: `https://api.openverse.org/v1/images/`,
                type: "GET",
                data: {
                    q: query,
                    per_page: 6
                }
            });

            let instagramRequest = fetchInstagramImages(query);

            $.when(unsplashRequest, pexelsRequest, openverseRequest, instagramRequest).done(function (unsplashResponse, pexelsResponse, openverseResponse, instagramResponse) {
                $("#loadingAnimation").hide();  
                $("#imageResults").html("");
                console.log("UnSplash: ", unsplashRequest);
                console.log("Pexels: ", pexelsRequest);
                console.log("OpenVerse: ", openverseRequest);
                console.log("Instagram: ", instagramRequest);

                // Display Unsplash results
                unsplashResponse[0].results.forEach(image => {
                    $("#imageResults").append(`
                <div class="col-lg-4 col-md-6 col-sm-12 mb-4">

                    <div class="card">
                        <img src="${image.urls.small}" class="card-img-top" alt="${image.alt_description}" loading="lazy">
                        <div class="card-body">
                            <p class="card-text">Unsplash: <a href="${image.user.links.html}" target="_blank">${image.user.name}</a></p>
                        </div>
                    </div>
                </div>
            `);
                });

                // Display Pexels results
                pexelsResponse[0].photos.forEach(image => {
                    $("#imageResults").append(`
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <img src="${image.src.medium}" class="card-img-top" alt="${image.photographer}" loading="lazy">
                        <div class="card-body">
                            <p class="card-text">Pexels: <a href="${image.photographer_url}" target="_blank">${image.photographer}</a></p>
                        </div>
                    </div>
                </div>
            `);
                });

                // Display Openverse results
                openverseResponse[0].results.forEach(image => {
                    $("#imageResults").append(`
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <img src="${image.url}" class="card-img-top" alt="${image.title}" loading="lazy">
                        <div class="card-body">
                            <p class="card-text">Openverse: <a href="${image.url}" target="_blank">${image.creator}</a></p>
                        </div>
                    </div>
                </div>
            `);
                });

                // Display Instagram results
                if (instagramResponse && instagramResponse.length > 0) {
                    instagramResponse.forEach(image => {
                        $("#imageResults").append(`
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <img src="${image.media_url}" class="card-img-top" alt="Instagram Image" loading="lazy">
                            <div class="card-body">
                                <p class="card-text">Instagram: <a href="${image.permalink}" target="_blank">View Post</a></p>
                            </div>
                        </div>
                    </div>
                `);
                    });
                }

            });
        }

        function fetchInstagramImages(hashtag) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: `https://graph.facebook.com/v22.0/ig_hashtag_search`,
                    type: "GET",
                    data: {
                        user_id: config.INSTAGRAM_USER_ID,
                        q: hashtag,
                        access_token: config.INSTAGRAM_ACCESS_TOKEN
                    },
                    success: function (response) {
                        if (response.data.length > 0) {
                            let hashtagId = response.data[0].id;
                            fetchInstagramMedia(hashtagId, resolve);
                        } else {
                            resolve([]);
                        }
                    },
                    error: function (error) {
                        console.error("Error fetching hashtag ID:", error);
                        resolve([]);
                    }
                });
            });
        }

        function fetchInstagramMedia(hashtagId, callback) {
            $.ajax({
                url: `https://graph.facebook.com/v22.0/${hashtagId}/top_media`,
                type: "GET",
                data: {
                    user_id: config.INSTAGRAM_USER_ID,
                    fields: "id,caption,media_type,media_url,permalink",
                    access_token: config.INSTAGRAM_ACCESS_TOKEN
                },
                success: function (response) {
                    callback(response.data || []);
                },
                error: function (error) {
                    console.error("Error fetching Instagram media:", error);
                    callback([]);
                }
            });
        }

        $("#searchHistory").on("click", ".delete-history", function () {
        let hashtag = $(this).data("hashtag");
        let listItem = $(this).closest("li");

        $.ajax({
                url: "delete_search_history.php",
                type: "POST",
                data: { hashtag: hashtag },
                success: function (response) {
                    if (response.trim() === "Success") {
                        listItem.remove();
                    } else {
                        console.error("Error deleting hashtag:", response);
                    }
                },
                error: function (error) {
                    console.error("AJAX Error:", error);
                }
            });
        });

    </script>

</body>

</html>