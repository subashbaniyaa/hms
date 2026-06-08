<?php
ob_start();
session_start();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">

    <link rel="apple-touch-icon" sizes="180x180" href="image/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="image/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="image/favicon/favicon-16x16.png">
    <link rel="manifest" href="image/favicon/site.webmanifest">
    <link rel="mask-icon" href="image/favicon/safari-pinned-tab.svg" color="#5bbad5">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.2.5/css/select.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="css/main.css">

    <?php

    require 'lib/phpPasswordHashing/passwordLib.php';
    require 'app/DB.php';
    require 'app/Util.php';
    require 'app/dao/CustomerDAO.php';
    require 'app/dao/BookingDetailDAO.php';
    require 'app/models/RequirementEnum.php';
    require 'app/models/Customer.php';
    require 'app/models/Booking.php';
    require 'app/models/Reservation.php';
    require 'app/handlers/CustomerHandler.php';
    require 'app/handlers/BookingDetailHandler.php';


    $username = $cHandler = $bdHandler = null;
    $cBookings = [];
    $isSessionExists = false;
    $isAdmin = [];

    if (isset($_SESSION["username"]) && isset($_SESSION["authenticated"])) {
        $username = $_SESSION["username"];

        $cHandler = new CustomerHandler();
        $cHandler = $cHandler->getCustomerObj($_SESSION["accountEmail"]);
        $cAdmin = new Customer();
        $cAdmin->setEmail($cHandler->getEmail());

        $bdHandler = new BookingDetailHandler();
        $cBookings = $bdHandler->getCustomerBookings($cHandler);
        $isSessionExists = true;
        $isAdmin = $_SESSION["authenticated"];
    }

    if (isset($_SESSION["isAdmin"]) && isset($_SESSION["username"])) {
        $isSessionExists = true;
        $username = $_SESSION["username"];
        $isAdmin = $_SESSION["isAdmin"];
    }

    // if (isset($_COOKIE['is_admin'])) {
    //     echo $_COOKIE['is_admin'];
    //     var_dump($isAdmin);
    // }

    // Load room prices from config (admin can update via admin panel)
    $roomPricesFile = 'app/room_prices.json';
    $roomPrices = ["deluxe" => 250, "double" => 180, "single" => 150];
    if (file_exists($roomPricesFile)) {
        $decoded = json_decode(file_get_contents($roomPricesFile), true);
        if ($decoded) $roomPrices = $decoded;
    }
    
    ?>
    <title>Home</title>
    <?php //echo '<title>Home isAdmin=' . $isAdmin . ' $isSessionExists=' . $isSessionExists . '</title>' ?>
</head>

<body>
    <header>
        <div class="bg-dark collapse" id="navbarHeader" style="">
            <div class="container">
                <div class="row">
                    <div class="col-sm-8 col-md-7 py-4">
                        <h4 class="text-white">About</h4>
                        <p class="text-muted">A brand new hotel beyond ordinary.</p>
                    </div>
                    <div class="col-sm-4 offset-md-1 py-4 text-right">
                        <?php if ($isSessionExists) { ?>
                            <h4 class="text-white"><?php echo $username; ?></h4>
                            <ul class="list-unstyled">
                                <?php if ($isAdmin[1] == "true" && isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] == "true") { ?>
                                    <li><a href="admin.php" class="text-white">Manage customer reservation(s)<i
                                                class="far fa-address-book ml-2"></i></a></li>
                                <?php } else { ?>
                                    <li><a href="#" class="text-white my-reservations">View my bookings<i
                                                class="far fa-address-book ml-2"></i></a></li>
                                    <li>
                                        <a href="#" class="text-white" data-toggle="modal" data-target="#myProfileModal">Update
                                            profile<i class="fas fa-user ml-2"></i></a>
                                    </li>
                                <?php } ?>
                                <li><a href="#" id="sign-out-link" class="text-white">Sign out<i
                                            class="fas fa-sign-out-alt ml-2"></i></a></li>
                            </ul>
                        <?php } else { ?>
                            <h4>
                                <a class="text-white" href="sign-in.php">Sign in</a> <span class="text-white">or</span>
                                <a href="register.php" class="text-white">Register </a>
                            </h4>
                            <p class="text-muted">Log in so you can take advantage with our hotel room prices.</p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="navbar navbar-dark bg-dark box-shadow">
            <div class="container d-flex justify-content-between">
                <a href="#" class="navbar-brand d-flex align-items-center">
                    <i class="fas fa-h-square mr-2"></i>
                    <strong>Heritage</strong>
                </a>
                <button class="navbar-toggler collapsed" type="button" data-toggle="collapse"
                    data-target="#navbarHeader" aria-controls="navbarHeader" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        <div class="container my-3" id="my-reservations-div">
            <h4>Reservations</h4>
            <table id="myReservationsTbl" class="table table-striped table-bordered" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th scope="col">S. No.</th>
                        <th class="text-hide p-0" data-bookId="12">12</th>
                        <th scope="col">Start date</th>
                        <th scope="col">End date</th>
                        <th scope="col">Room type</th>
                        <th scope="col">Requirements</th>
                        <th scope="col">Adults</th>
                        <th scope="col">Children</th>
                        <th scope="col">Requests</th>
                        <th scope="col">Timestamp</th>
                        <th scope="col">Status</th>
                        <th scope="col">Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cBookings) && $bdHandler->getExecutionFeedback() == 1) { ?>
                        <?php foreach ($cBookings as $k => $v) { ?>
                            <tr>
                                <th scope="row"><?php echo ($k + 1); ?></th>
                                <td class="text-hide p-0"><?php echo $v["id"]; ?></td>
                                <td><?php echo $v["start"]; ?></td>
                                <td><?php echo $v["end"]; ?></td>
                                <td><?php echo $v["type"]; ?></td>
                                <td><?php echo $v["requirement"]; ?></td>
                                <td><?php echo $v["adults"]; ?></td>
                                <td><?php echo $v["children"]; ?></td>
                                <td><?php echo $v["requests"]; ?></td>
                                <td><?php echo $v["timestamp"]; ?></td>
                                <td><?php echo $v["status"]; ?></td>
                                <td><?php echo !empty($v["notes"]) ? htmlspecialchars($v["notes"]) : '<span class="text-muted">NA</span>'; ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </header>

    <main role="main">

        <section class="jumbotron text-center">
            <div class="container">
                <div class="row" style="margin-top: -20px;">
                    <div class="col-md-12 text-center">
                        <img src="image/booknow.png" alt="Hotel Front" class="img-fluid rounded mb-1"
                            style="width: 250px; height: auto; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none;"
                            ondragstart="return false;" oncontextmenu="return false;" onclick="return false;">
                    </div>
                </div>
                <div>
                    <h1 class="display-3">Step beyond ordinary into<br>Hotel Heritage</h1>
                    <br>
                    <p class="lead text-muted">Discover timeless hospitality. Your presence is awaited...</p>
                    <p>
                        <?php if ($isSessionExists) { ?>
                            <?php if (isset($isAdmin[1]) && $isAdmin[1] == "true") { ?>
                                <a href="#" class="btn btn-success my-2" data-toggle="modal"
                                    data-target="#adminReserveModal">Reserve Now<i
                                        class="fas fa-angle-double-right ml-1"></i></a>
                            <?php } else { ?>
                                <a href="#" class="btn btn-success my-2" data-toggle="modal"
                                    data-target=".book-now-modal-lg">Reserve Now<i
                                        class="fas fa-angle-double-right ml-1"></i></a>
                            <?php } ?>
                        <?php } else { ?>
                            <a href="#" class="btn btn-success my-2" data-toggle="modal"
                                data-target=".sign-in-to-book-modal">Reserve Now<i
                                    class="fas fa-angle-double-right ml-1"></i></a>
                        <?php } ?>
                    </p>
                </div>
            </div>
        </section>
        <div class="container">
           
                <section class="py-3"
    style="background-color: #cfe2ff; border-radius: 10px; margin: 15px 0; display: flex; align-items: center; justify-content: center;">
    <div class="container text-center">
        <h1 class="display-4">Amenities</h1>
        <BR>
        <p class="lead mb-3">
            Luxurious comfort, personalized service, and comprehensive amenities.<br>
            24/7 concierge and room service, spacious soundproofed rooms with premium bedding,<br>
            gourmet dining, state-of-the-art fitness center, full-service spa, swimming pool,<br>
            business center, complimentary Wi-Fi, and elegant in-room conveniences.
        </p>
    </div>
</section>
            <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
                <h1 class="display-4">Pricing</h1>
                <BR>
                <p class="lead">At Hotel Heritage, we believe exceptional hospitality should be accessible to every
                    traveler. <br> Our thoughtfully designed rooms combine comfort, modern amenities, and warm Nepali
                    hospitality to ensure a relaxing and memorable stay, featuring premium bedding, contemporary
                    furnishings, complimentary high-speed WiFi, air conditioning, flat-screen TV, and all the essential
                    comforts that make your visit truly enjoyable and unforgettable.<BR>Book your room today and feel at
                    home with us.</p>
                <br>
            </div>
        </div>

        <div class="album py-5 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4 box-shadow">
                            <div class="card-header">
                                <h5 class="my-0 font-weight-normal">Deluxe Room</h5>
                            </div>
                            <img class="card-img-top"
                                data-src="holder.js/100px225?theme=thumb&amp;bg=55595c&amp;fg=eceeef&amp;text=Thumbnail"
                                alt="Thumbnail [100%x225]"
                                style="height: 225px; width: 100%; display: block; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none;"
                                src="image/deluxe.jpg" data-holder-rendered="true"
                                ondragstart="return false;" oncontextmenu="return false;" ondblclick="return false;">
                            <div class="card-body">
                                <p class="card-text" style="text-align: justify;">The ultimate sanctuary to recharge the
                                    senses, the beautifully-appointed 24sqm Deluxe Room exudes sheer sophistication and
                                    elegance. Located on the higher floors, each Deluxe Room is characterised by
                                    elevated ceilings and full length bay windows, transforming your living space into
                                    an atmospheric abode.<BR><BR></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="btn-group">
                                        <?php if ($isSessionExists) { ?>
                                            <?php if (isset($isAdmin[1]) && $isAdmin[1] == "true") { ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal"
                                                    data-target="#adminReserveModal">
                                                    Reserve
                                                </button>
                                            <?php } else { ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" data-rtype="Deluxe"
                                                    data-toggle="modal" data-target=".book-now-modal-lg">
                                                    Reserve
                                                </button>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal"
                                                data-target=".sign-in-to-book-modal">
                                                Reserve
                                            </button>
                                        <?php } ?>
                                    </div>
                                    <small class="text-muted">Rs. <?php echo $roomPrices['deluxe']; ?> / night</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4 box-shadow">
                            <div class="card-header">
                                <h5 class="my-0 font-weight-normal">Double Room</h5>
                            </div>
                            <img class="card-img-top"
                                data-src="holder.js/100px225?theme=thumb&amp;bg=55595c&amp;fg=eceeef&amp;text=Thumbnail"
                                alt="Thumbnail [100%x225]" src="image/double.jpg" data-holder-rendered="true"
                                style="height: 225px; width: 100%; display: block; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none;"
                                ondragstart="return false;" oncontextmenu="return false;" ondblclick="return false;">
                            <div class="card-body">
                                <p class="card-text" style="text-align: justify;">The standard twin room is equipped
                                    with two single beds to house two people. An enticing set of top notch facilities to
                                    the optimum security level, a fully air conditioned twin room remains the perfect
                                    choice for your needs. Book hotel rooms with us and enjoy your trip with full
                                    fervor.<BR><BR><BR></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <?php if ($isSessionExists) { ?>
                                        <?php if (isset($isAdmin[1]) && $isAdmin[1] == "true") { ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal"
                                                data-target="#adminReserveModal">
                                                Reserve
                                            </button>
                                        <?php } else { ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" data-rtype="Double"
                                                data-toggle="modal" data-target=".book-now-modal-lg">
                                                Reserve
                                            </button>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal"
                                            data-target=".sign-in-to-book-modal">
                                            Reserve
                                        </button>
                                    <?php } ?>
                                    <small class="text-muted">Rs. <?php echo $roomPrices['double']; ?> / night</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4 box-shadow">
                            <div class="card-header">
                                <h5 class="my-0 font-weight-normal">Single Room</h5>
                            </div>
                            <img class="card-img-top"
                                data-src="holder.js/100px225?theme=thumb&amp;bg=55595c&amp;fg=eceeef&amp;text=Thumbnail"
                                alt="Thumbnail [100%x225]" src="image/single.jpg" data-holder-rendered="true"
                                style="height: 225px; width: 100%; display: block; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none;"
                                ondragstart="return false;" oncontextmenu="return false;" ondblclick="return false;">
                            <div class="card-body">
                                <p class="card-text" style="text-align: justify;">A modestly sized single room with en
                                    suite bathroom with shower and/or bathtub, a hairdryer and complimentary toiletries.
                                    Amenities include free WiFi, a telephone, a minibar, and a flat-screen TV with a
                                    variety of channels and films.<BR><BR><BR><BR></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <?php if ($isSessionExists) { ?>
                                        <?php if (isset($isAdmin[1]) && $isAdmin[1] == "true") { ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal"
                                                data-target="#adminReserveModal">
                                                Reserve
                                            </button>
                                        <?php } else { ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal"
                                                data-rtype="Single" data-target=".book-now-modal-lg">
                                                Reserve
                                            </button>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal"
                                            data-target=".sign-in-to-book-modal">
                                            Reserve
                                        </button>
                                    <?php } ?>
                                    <small class="text-muted">Rs. <?php echo $roomPrices['single']; ?> / night</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <section class="py-3" style="background-color: #d4edda; border-radius: 10px; margin: 15px 0;">
                    <div class="container text-center">
        <h1 class="display-4">Offer!</h1>
        <BR>
                <div class="container text-center">
                    <img src="image/offer.png" alt="Special Offer" width="50" height="50"
                        style="margin-bottom: 10px; transform: none !important; -webkit-transform: none !important; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; pointer-events: none;"
                        ondragstart="return false;" oncontextmenu="return false;" onclick="return false;">
                    <p class="lead mb-3">Get 10% OFF on Deluxe Room bookings! <br> Book now and save big
                        on your stay.</p>
                </div>
                </div>
            </section>
                <div class="row" style="margin-top: -20px;">
                    <div class="col-md-12 text-center">
                        <img src="image/hotel.png" alt="Hotel Front" class="img-fluid rounded mb-1"
                            style="width: 500px; height: auto; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none;"
                            ondragstart="return false;" oncontextmenu="return false;" onclick="return false;">
                    </div>
                </div>
               <section class="py-3"
    style="background-color: #f8d7da; border-radius: 10px; margin: 15px 0; display: flex; align-items: center; justify-content: center;">
    <div class="container text-center">
        <h1 class="display-4">Reach Us</h1>
        <p class="lead mb-3">For reservations and inquiries, please contact us at:</p>
        <br>
        <div class="row justify-content-center mt-3">
            <div class="col-md-3">
                <img src="image/call.png" alt="Call" class="img-fluid mb-1"
                     style="width: 20px; height: 20px; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; pointer-events: none;"
                     draggable="false" ondragstart="return false" oncontextmenu="return false" onselectstart="return false">
                <p class="mb-0">+977-9-876543210</p>
            </div>
            <div class="col-md-3">
                <img src="image/gmail.png" alt="Email" class="img-fluid mb-1"
                     style="width: 20px; height: 20px; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; pointer-events: none;"
                     draggable="false" ondragstart="return false" oncontextmenu="return false" onselectstart="return false">
                <p class="mb-0">info@hotelheritage.com</p>
            </div>
            <div class="col-md-3">
                <img src="image/location.png" alt="Location" class="img-fluid mb-1"
                     style="width: 20px; height: 20px; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; pointer-events: none;"
                     draggable="false" ondragstart="return false" oncontextmenu="return false" onselectstart="return false">
                <p class="mb-0">Tinkune, Kathmandu</p>
            </div>
        </div>
    </div>
</section>
            </div>
        </div>
        </div>

        <?php if (isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] == "false"): ?>
            <div class="modal fade book-now-modal-lg" tabindex="-1" role="dialog" aria-labelledby="bookNowModalLarge"
                aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-dark text-white">
                            <h5 class="modal-title">Reservation Form</h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body" id="reservationModalBody">
                            <?php if ($isSessionExists == 1 && $isAdmin[1] == "false") { ?>
                                <form role="form" autocomplete="off" method="post" id="multiStepRsvnForm">
                                    <div class="rsvnTab">
                                        <?php if ($isSessionExists) { ?>
                                            <input type="number" name="cid" value="<?php echo $cHandler->getId() ?>" hidden>
                                        <?php } ?>
                                        <div class="form-group row">
                                            <label for="startDate" class="col-sm-3 col-form-label">Check-in
                                                <span class="red-asterisk"> *</span>
                                            </label>
                                            <div class="col-sm-9">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">
                                                            <i class="fa fa-calendar"></i>
                                                        </span>
                                                    </div>
                                                    <input type="date" class="form-control" name="startDate"
                                                        min="<?php echo Util::dateToday('0'); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="endDate" class="col-sm-3 col-form-label">Check-out
                                                <span class="red-asterisk"> *</span>
                                            </label>
                                            <div class="col-sm-9">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="inputGroupPrepend">
                                                            <i class="fa fa-calendar"></i>
                                                        </span>
                                                    </div>
                                                    <input type="date" class="form-control"
                                                        min="<?php echo Util::dateToday('1'); ?>" name="endDate" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row align-items-center">
                                            <label class="col-sm-3 col-form-label" for="roomType">Room type
                                                <span class="red-asterisk"> *</span>
                                            </label>
                                            <div class="col-sm-9">
                                                <select required class="custom-select mr-sm-2" name="roomType">
                                                    <option value="" disabled selected>Room type</option>
                                                    <option value="<?php echo \models\RequirementEnum::DELUXE; ?>">Deluxe room</option>
                                                    <option value="<?php echo \models\RequirementEnum::DOUBLE; ?>">Double room</option>
                                                    <option value="<?php echo \models\RequirementEnum::SINGLE; ?>">Single room</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row align-items-center">
                                            <label class="col-sm-3 col-form-label" for="roomRequirement">Room
                                                requirements</label>
                                            <div class="col-sm-9">
                                                <select class="custom-select mr-sm-2" name="roomRequirement">
                                                    <option value="no preference" selected>No preference</option>
                                                    <option value="non smoking">Non-smoking</option>
                                                    <option value="smoking">Smoking</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row align-items-center">
                                            <label class="col-sm-3 col-form-label" for="adults">Adults
                                                <span class="red-asterisk"> *</span>
                                            </label>
                                            <div class="col-sm-9">
                                                <select required class="custom-select mr-sm-2" name="adults">
                                                    <option selected value="1">1</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row align-items-center">
                                            <label class="col-sm-3 col-form-label" for="children">Children</label>
                                            <div class="col-sm-9">
                                                <select class="custom-select mr-sm-2" name="children">
                                                    <option selected value="0">-</option>
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row align-items-center">
                                            <label class="col-sm-3 col-form-label" for="specialRequests">Special
                                                requirements</label>
                                            <div class="col-sm-9">
                                                <textarea rows="3" maxlength="500" name="specialRequests"
                                                    class="form-control"></textarea>
                                            </div>
                                        </div>
                                    </div>
<BR>
                                    <div class="rsvnTab">
                                        <div class="card border-0 rounded mt-1">
                                            
                                            <div class="card-body p-0">
                                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                                    <span class="text-muted small font-weight-bold">Booked On</span>
                                                    <span class="font-weight-bold small bookedDateTxt">—</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                                    <span class="text-muted small font-weight-bold"> Stay Period</span>
                                                    <span class="font-weight-bold small fromToTxt">—</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                                    <span class="text-muted small font-weight-bold">Room Type</span>
                                                    <span class="font-weight-bold small roomTypeTxt">—</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                                    <span class="text-muted small font-weight-bold">Duration</span>
                                                    <span class="font-weight-bold small"><span class="numNightsTxt">0</span> night(s)</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                                    <span class="text-muted small font-weight-bold">Room Price</span>
                                                    <span class="font-weight-bold small">Rs. <span class="roomPriceTxt">0</span> per night</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                                    <span class="text-muted small font-weight-bold">Taxes</span>
                                                    <span class="font-weight-bold small">Rs. <span class="taxesTxt">0</span></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                                    <span class="text-muted small font-weight-bold">Total</span>
                                                    <span class="font-weight-bold small">Rs. <span class="totalTxt">0</span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </form>
                                <BR>
                                <BR>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
                                    <div style="display:flex; gap:6px;">
                                        <button id="checkinPoliciesBtn" type="button" class="btn btn-info btn-sm"
                                            data-container="body" data-toggle="popover" data-placement="top"
                                            data-content="Check-in time starts at 3 PM. If a late check-in is planned, please contact our support department.">
                                            Policies
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm" id="rsvnPrevBtn"
                                            onclick="rsvnNextPrev(-1)">Previous</button>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-success btn-sm" id="rsvnNextBtn" onclick="rsvnNextPrev(1)"
                                            readySubmit="false">Next</button>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <p>Booking is reserved for customers.</p>
                            <?php } ?>
                        </div>

                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="modal fade sign-in-to-book-modal" tabindex="-1" role="dialog" aria-labelledby="signInToBookModal"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title">Sign in required!</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <h5>You have to <a href="sign-in.php">sign in</a> in order to reserve a room.<br>Not registered? <a href="register.php">Register here</a>.</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="adminReserveModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title">Administrator!</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <h4>You don't need this! Go manage the <a href="admin.php">dashboard</a>.</h4>
                    </div>
                </div>
            </div>
        </div>

        <?php if (($isSessionExists == 1 && $isAdmin[1] == "false") && isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] == "false"): ?>
            <div class="modal fade" id="myProfileModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-dark text-white">
                            <h5 class="modal-title"><i class="fas fa-user-edit mr-2"></i>Update Profile</h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="card border-0">
                                <div class="card-body p-0">
                                    <?php if ($isSessionExists) { ?>
                                        <form class="form" role="form" autocomplete="off" id="update-profile-form"
                                            method="post">
                                            <input type="number" id="customerId" hidden name="customerId"
                                                value="<?php echo $cHandler->getId(); ?>">
                                            <div class="form-group">
                                                <label for="updateFullName">Full Name</label>
                                                <input type="text" class="form-control" id="updateFullName"
                                                    name="updateFullName" value="<?php echo $cHandler->getFullName(); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="updatePhoneNumber">Phone Number</label>
                                                <input type="text" class="form-control" id="updatePhoneNumber"
                                                    name="updatePhoneNumber" value="<?php echo $cHandler->getPhone(); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="updateEmail">Email</label>
                                                <input type="email" class="form-control" id="updateEmail" name="updateEmail"
                                                    value="<?php echo $cHandler->getEmail(); ?>" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="updatePassword">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                                                <div class="position-relative">
                                                    <input type="password" class="form-control" id="updatePassword"
                                                        name="updatePassword"
                                                        title="At least 4 characters with letters and numbers"
                                                        style="padding-right:2.5rem;">
                                                    <button type="button" id="toggleUpdatePassword" tabindex="-1"
                                                            style="position:absolute;right:0.6rem;top:50%;transform:translateY(-50%);background:none;border:none;padding:0;color:#6c757d;line-height:1;">
                                                        <i class="fas fa-eye" id="toggleUpdatePasswordIcon"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <input type="submit" class="btn btn-primary btn-md float-right"
                                                    name="updateProfileSubmitBtn" value="Update">
                                            </div>
                                        </form>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <footer class="container text-center">
        <p>Hotel Heritage &copy; 2026</p>
    </footer>
    <script src="js/utilityFunctions.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.3.min.js"
        integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"
        integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"
        integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+"
        crossorigin="anonymous"></script>

    <script defer src="https://use.fontawesome.com/releases/v5.0.10/js/all.js"
        integrity="sha384-slN8GvtUJGnv6ca26v8EzVaR9DC58QEwsIk9q1QXdCU8Yu8ck/tL/5szYlBbqmS+"
        crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.2.5/js/dataTables.select.min.js"></script>
    <script src="js/form-submission.js"></script>
    <script>
        $(document).ready(function () {
            let reservationDiv = $("#my-reservations-div");
            reservationDiv.hide();
            $(".my-reservations").click(function () {
                reservationDiv.slideToggle("slow");
            });
            $('#myReservationsTbl').DataTable();

            // dynamically entered room type value on show modal
            $('.book-now-modal-lg').on('show.bs.modal', function (event) {
                let button = $(event.relatedTarget);
                let roomType = button.data('rtype');
                let modal = $(this);
                modal.find('.modal-body select[name=roomType]').val(roomType);
            });

            // check-in policies popover
            $('[data-toggle="popover"]').popover();

            // Update profile — password show/hide toggle
            $('#toggleUpdatePassword').on('click', function () {
                var field = document.getElementById('updatePassword');
                var icon = document.getElementById('toggleUpdatePasswordIcon');
                if (field.type === 'password') {
                    field.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    field.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });

        });
    </script>
    <script>
        window.ROOM_PRICES = {
            deluxe: <?php echo intval($roomPrices['deluxe']); ?>,
            double: <?php echo intval($roomPrices['double']); ?>,
            single: <?php echo intval($roomPrices['single']); ?>
        };
    </script>
    <script src="js/multiStepsRsvn.js"></script>
</body>
</html>