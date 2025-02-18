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
        <button class="navbar-toggler" id="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand" href="#">
            <img src="images/Logo.png" alt="Logo" height="40" class="d-inline-block align-text-top">
        </a>
        <form class="d-flex ms-auto" role="search" id="searchForm">
            <input class="form-control me-2" type="search" id="searchInput" placeholder="Search for Hashtags" aria-label="Search">
            <button class="btn btn-outline-light" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                </svg>
            </button>
        </form>
    </div>
</nav>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <h4>Sidebar Menu</h4>
    <ul>
        <li><a href="#">Dashboard</a></li>
        <li><a href="#">Search History</a></li>
        <li><a href="#">Settings</a></li>
    </ul>
</div>

<div class="container mt-4">
    <div class="row" id="imageResults">
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="config.js"></script>
<script>

    
    // Toggle Sidebar Visibility
    $("#navbar-toggler").click(function() {
        $("#sidebar").toggleClass("show");
        $(".content").toggleClass("show");
    });


    $(document).ready(function() {
        $("#searchForm").submit(function(event) {
            event.preventDefault();
            let query = $("#searchInput").val();
            searchImages(query);
        });
    });

    function searchImages(query) {

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

        $.when(unsplashRequest, pexelsRequest).done(function(unsplashResponse, pexelsResponse) {
            console.log("Unsplash API Response:", unsplashResponse);
            console.log("Pexels API Response:", pexelsResponse);
            $("#imageResults").html(""); 

            // Display Unsplash results
            unsplashResponse[0].results.forEach(image => {
                $("#imageResults").append(`
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <img src="${image.urls.small}" class="card-img-top" alt="${image.alt_description}">
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
                            <img src="${image.src.medium}" class="card-img-top" alt="${image.photographer}">
                            <div class="card-body">
                                <p class="card-text">Pexels: <a href="${image.photographer_url}" target="_blank">${image.photographer}</a></p>
                            </div>
                        </div>
                    </div>
                `);
            });
        })
    }
</script>
</body>
</html>
