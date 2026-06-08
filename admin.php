<?php
ob_start();
session_start();

// Access guard — only admin sessions may view this page
if (!isset($_SESSION["isAdmin"]) || $_SESSION["isAdmin"][1] !== "true" ||
    !isset($_COOKIE['is_admin']) || $_COOKIE['is_admin'] !== "true") {
    header("Location: index.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.2.5/css/select.dataTables.min.css">
    <link rel="stylesheet" href="css/main.css">
    <?php

    require 'lib/phpPasswordHashing/passwordLib.php';
    require 'app/DB.php';
    require 'app/Util.php';
    require 'app/models/StatusEnum.php';
    require 'app/models/RequirementEnum.php';
    require 'app/dao/CustomerDAO.php';
    require 'app/dao/BookingDetailDAO.php';
    require 'app/models/Customer.php';
    require 'app/models/Booking.php';
    require 'app/models/Reservation.php';
    require 'app/handlers/CustomerHandler.php';
    require 'app/handlers/BookingDetailHandler.php';

   $username = null;
$isSessionExists = $isAdmin = false;
$pendingReservation = $confirmedReservation = $cancelledReservation = $totalCustomers = $totalReservations = null;
$allBookings = [];
$cCommon = null;
$allCustomer = [];
if (isset($_SESSION["username"]))
{
        $username = $_SESSION["username"];
        $isSessionExists = true;

        $cHandler = new CustomerHandler();
        $cHandler = $cHandler->getCustomerObj($_SESSION["accountEmail"]);

        $cAdmin = new Customer();
        $cAdmin->setEmail($cHandler->getEmail());

        // display all reservations
        $bdHandler = new BookingDetailHandler();
        $allBookings = $bdHandler->getAllBookings();
        $cCommon = new CustomerHandler();
        $allCustomer = $cCommon->getAllCustomer();

        // reservation stats
        $pendingReservation = $bdHandler->getPending();
        $confirmedReservation = $bdHandler->getConfirmed();
        $cancelledReservation = $bdHandler->getCancelled();
        $allBookingsTemp = $bdHandler->getAllBookings();
$totalReservations = is_array($allBookingsTemp) ? count($allBookingsTemp) : 0;
      
        $totalCustomers = is_array($allCustomer) ? count($allCustomer) : 0;
    }
    if (isset($_SESSION["isAdmin"]) && isset($_SESSION["username"])) {
        $isSessionExists = true;
        $username = $_SESSION["username"];
        $isAdmin = $_SESSION["isAdmin"];
    }

    // Load current room prices
    $roomPricesFile = 'app/room_prices.json';
    $roomPrices = ["deluxe" => 250, "double" => 180, "single" => 150];
    if (file_exists($roomPricesFile)) {
        $decoded = json_decode(file_get_contents($roomPricesFile), true);
        if ($decoded) $roomPrices = $decoded;
    }

    // Compute effective display status for CONFIRMED reservations based on check-in dates
    function effectiveStatus($status, $start, $end) {
        $today = date("Y-m-d");
        if (strtoupper($status) === 'CONFIRMED') {
            if ($start && $end) {
                if ($today > $end) {
                    return 'CHECKED OUT';
                } elseif ($today >= $start && $today <= $end) {
                    return 'CHECKED IN';
                }
            }
        }
        return strtoupper($status);
    }

    ?>

    <title>Admin</title>
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
                    <!-- User full name or email if logged in -->
                    <?php if ($isSessionExists) { ?>
                    <h4 class="text-white"><?php echo $username; ?></h4>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home<i class="fas fa-home ml-2"></i></a></li>
                        <li><a href="logout.php" id="sign-out-link" class="text-white">Sign out<i class="fas fa-sign-out-alt ml-2"></i></a></li>
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
                <strong>Heritage — Dashboard</strong>
            </a>
            <button class="navbar-toggler collapsed" type="button" data-toggle="collapse" data-target="#navbarHeader" aria-controls="navbarHeader" aria-expanded="false" aria-label="Toggle navigation">
    <i class="fas fa-bars"></i>
</button>
        </div>
    </div>
</header>

<main role="main">

    <?php if ($isSessionExists && $isAdmin) { ?>
    <div class="container my-3">
    <div class="row gx-3 gy-3 mb-3">
        <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12">
            <div class="card text-white o-hidden h-100" style="background-color: #2bc0aa;">
                <div class="card-body d-flex align-items-center">
                    <i class="fa fa-bed mr-3" style="font-size: 1rem;"></i>
                    <div class="mr-5">3 Room Categories</div>
                </div>
                <a class="card-footer text-white clearfix small z-1" href="#roomprices">
                    <span class="float-left">View Details</span>
                    <span class="float-right"><i class="fa fa-angle-right"></i></span>
                </a>
            </div>
        </div>

        <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12">
            <div class="card text-white o-hidden h-100" style="background-color: #dfa424fc;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-users mr-3" style="font-size: 1rem;"></i>
                    <div class="mr-5"><?php echo $totalCustomers; ?> Registered Customers</div>
                </div>
                <a class="card-footer text-white clearfix small z-1" href="#customers">
                    <span class="float-left">View Details</span>
                    <span class="float-right"><i class="fa fa-angle-right"></i></span>
                </a>
            </div>
        </div>

        <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12">
            <div class="card text-white o-hidden h-100" style="background-color: #c22475;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-file mr-3" style="font-size: 1rem;"></i>
                    <div class="mr-5"><?php echo $totalReservations; ?> Total Reservations</div>
                </div>
                <a class="card-footer text-white clearfix small z-1" href="#reservation" data-filter="all" style="cursor:pointer;">
                    <span class="float-left">View Details</span>
                    <span class="float-right"><i class="fa fa-angle-right"></i></span>
                </a>
            </div>
        </div>
    </div>

    <div class="row gx-3 gy-3">
        <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12">
            <div class="card text-white bg-success o-hidden h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-check mr-3" style="font-size: 1rem;"></i>
                    <div class="mr-4"><?php echo $confirmedReservation; ?> Confirmed Reservations</div>
                </div>
                <a class="card-footer text-white clearfix small z-1" href="#reservation" data-filter="confirmed" style="cursor:pointer;">
                    <span class="float-left">View Details</span>
                    <span class="float-right"><i class="fa fa-angle-right"></i></span>
                </a>
            </div>
        </div>

        <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12">
            <div class="card text-white o-hidden h-100" style="background-color: #FF8C00;">
                <div class="card-body d-flex align-items-center">
                    <i class="fa fa-fw fa-support mr-3" style="font-size: 1rem;"></i>
                    <div class="mr-5"><?php echo $pendingReservation; ?> Pending Reservations</div>
                </div>
                <a class="card-footer text-white clearfix small z-1" href="#reservation" data-filter="pending" style="cursor:pointer;">
                    <span class="float-left">View Details</span>
                    <span class="float-right"><i class="fa fa-angle-right"></i></span>
                </a>
            </div>
        </div>

        <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12">
            <div class="card text-white bg-danger o-hidden h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-times mr-3" style="font-size: 1rem;"></i>
                    <div class="mr-5"><?php echo $cancelledReservation; ?> Cancelled Reservations</div>
                </div>
                <a class="card-footer text-white clearfix small z-1" href="#reservation" data-filter="cancelled" style="cursor:pointer;">
                    <span class="float-left">View Details</span>
                    <span class="float-right"><i class="fa fa-angle-right"></i></span>
                </a>
            </div>
        </div>
    </div>
</div>
<BR>
    <div class="container" id="tableContainer">
        <ul class="nav nav-tabs" id="adminTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="reservation-tab" data-toggle="tab" href="#reservation" role="tab"
                   aria-controls="reservation" aria-selected="true">Reservations</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="customers-tab" data-toggle="tab" href="#customers" role="tab"
                   aria-controls="customers" aria-selected="false">Customers</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="roomprices-tab" data-toggle="tab" href="#roomprices" role="tab"
                   aria-controls="roomprices" aria-selected="false">Update Room Price
                </a>
            </li>
        </ul>
        <div class="tab-content py-3" id="adminTabContent">
            <div class="tab-pane fade show active" id="reservation" role="tabpanel" aria-labelledby="reservation-tab">
                <table id="reservationDataTable" class="table table-striped table-bordered" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th scope="col">S. No.</th>
                        <th class="text-hide p-0" data-bookId="12">12</th>
                        <th scope="col">Email</th>
                        <th scope="col">Start</th>
                        <th scope="col">End</th>
                        <th scope="col">Type & Duration</th>
                        <th scope="col">Timestamp</th>
                        <th scope="col">Status</th>
                        <th scope="col">Remark</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($allBookings)) { ?>
                        <?php   foreach ($allBookings as $k => $v) {
                            $effStatus = effectiveStatus($v["status"], $v["start"] ?? '', $v["end"] ?? '');
                        ?>
                            <tr>
                                <th scope="row"><?php echo ($k + 1); ?></th>
                                <td class="text-hide p-0" data-id="<?php echo $v["id"]; ?>">
                                    <?php echo $v["id"]; ?>
                                </td>
                                <?php $cid = $v["cid"]; ?>
                                <td><?php echo $cCommon->getCustomerObjByCid($cid)->getEmail(); ?></td>
                                <td><?php echo $v["start"]; ?></td>
                                <td><?php echo $v["end"]; ?></td>
                                <td><?php
                                    $nights = '';
                                    if (!empty($v["start"]) && !empty($v["end"])) {
                                        $d1 = new DateTime($v["start"]);
                                        $d2 = new DateTime($v["end"]);
                                        $diff = (int)$d1->diff($d2)->days;
                                        $nights = ' / ' . $diff . ' ' . ($diff === 1 ? 'night' : 'nights');
                                    }
                                    echo htmlspecialchars($v["type"]) . $nights;
                                ?></td>
                                <td><?php echo $v["timestamp"]; ?></td>
                                <td><?php echo $effStatus; ?></td>
                                <?php $notesFull = htmlspecialchars($v["notes"] ?? '', ENT_QUOTES); ?>
                                <td style="max-width:200px;word-wrap:break-word;white-space:normal;">
                                    <?php echo $notesFull ?: '<span class="text-muted small">NA</span>'; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                    </tbody>
                </table><BR> 
                <div class="my-3">
                    <div class="row">
                        <div class="col-12 col-md-7 mb-2">
                            <label>With selected:</label>
                            <button type="button" id="confirm-booking" class="btn btn-outline-success btn-sm">Confirm</button>
                            <button type="button" id="cancel-booking" class="btn btn-outline-danger btn-sm">Cancel</button>
                            <button type="button" id="bulk-add-note" class="btn btn-outline-secondary btn-sm" disabled title="Select exactly 1 reservation to add a note">Remark</button>
                            <button type="button" id="delete-booking" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash-alt"></i> Delete</button>
                        </div>
                        <div class="col-12 col-md-5 text-md-right">
                            View:<br>
                            <input type="radio" name="viewOption" value="confirmed">&nbsp;Confirmed&nbsp;
                            <input type="radio" name="viewOption" value="pending">&nbsp;Pending&nbsp;
                            <input type="radio" name="viewOption" value="cancelled">&nbsp;Cancelled<br>
                            <input type="radio" name="viewOption" value="checked in">&nbsp;Checked&nbsp;In&nbsp;
                            <input type="radio" name="viewOption" value="checked out">&nbsp;Checked&nbsp;Out&nbsp;
                            <input type="radio" name="viewOption" value="all" checked>&nbsp;All
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="customers" role="tabpanel" aria-labelledby="customers-tab">
                <div id="customerTableContainer"></div>
                <table id="customerTable" class="table table-bordered">
                    <thead class="thead-dark">
                    <tr>
                        <th scope="col">S. No.</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($allCustomer)) { ?>
                        <?php foreach ($cCommon->getAllCustomer() as $key => $value) { ?>
                        <tr>
                            <td scope="row"><?php echo ($key + 1); ?></td>
                            <td><?php echo htmlspecialchars($value->getFullName()); ?></td>
                            <td><?php echo htmlspecialchars($value->getEmail()); ?></td>
                            <td><?php echo htmlspecialchars($value->getPhone()); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary edit-customer-btn"
                                    data-cid="<?php echo $value->getId(); ?>"
                                    data-fullname="<?php echo htmlspecialchars($value->getFullName(), ENT_QUOTES); ?>"
                                    data-email="<?php echo htmlspecialchars($value->getEmail(), ENT_QUOTES); ?>"
                                    data-phone="<?php echo htmlspecialchars($value->getPhone(), ENT_QUOTES); ?>"
                                    title="Edit customer">
                                    Update
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-customer-btn"
                                    data-cid="<?php echo $value->getId(); ?>"
                                    data-name="<?php echo htmlspecialchars($value->getFullName(), ENT_QUOTES); ?>"
                                    title="Delete customer">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="tab-pane fade" id="roomprices" role="tabpanel" aria-labelledby="roomprices-tab">
    <div id="pricesContainer" class="table-responsive">
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Room Type</th>
                    <th style="width: 55%;">Price per night</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Deluxe</strong></td>
                    <td>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rs.</span>
                            </div>
                            <input type="number" class="form-control" id="priceDeluxe" min="1" value="<?php echo $roomPrices['deluxe']; ?>">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><strong>Double</strong></td>
                    <td>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rs.</span>
                            </div>
                            <input type="number" class="form-control" id="priceDouble" min="1" value="<?php echo $roomPrices['double']; ?>">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><strong>Single</strong></td>
                    <td>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rs.</span>
                            </div>
                            <input type="number" class="form-control" id="priceSingle" min="1" value="<?php echo $roomPrices['single']; ?>">
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <button type="button" id="savePricesBtn" class="btn btn-primary">
            Update Prices
        </button>
    </div>
</div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle mr-2"></i>Confirm selected reservation(s)</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to <strong>confirm</strong> the selected reservation(s)?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="confirmTrue">Yes, Confirm</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title"><i class="fas fa-ban mr-2"></i>Cancel selected reservation(s)</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to <strong>cancel</strong> the selected reservation(s)?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" id="cancelTrue">Yes, Cancel</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Reservation Modal -->
    <div class="modal fade" id="deleteReservationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-trash-alt mr-2"></i>Delete selected reservation(s)</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Warning:</strong> This will permanently delete the selected reservation(s). This action cannot be undone.</p>
                    <p>Are you sure you want to continue?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="deleteReservationTrue">Yes, Delete</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-sticky-note mr-2"></i>Edit Reservation Note</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Stores single ID or JSON array for bulk note saving -->
                    <input type="hidden" id="notesBookingId">
                    <div class="form-group">
                        <label for="notesTextarea">Note / Internal remark for this reservation:</label>
                        <textarea class="form-control" id="notesTextarea" rows="4"
                                  placeholder="e.g. Guest requested early check-in, VIP treatment, room on upper floor..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="saveNoteBtn">
                        <i class="fas fa-save mr-1"></i> Save Note
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Edit Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-edit mr-2"></i>Edit Customer</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editCustomerCid">
                    <div class="form-group">
                        <label for="editCustomerFullname">Full Name</label>
                        <input type="text" class="form-control" id="editCustomerFullname" placeholder="Full name">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" class="form-control" id="editCustomerEmail" disabled>
                        <small class="text-muted">Email cannot be changed.</small>
                    </div>
                    <div class="form-group">
                        <label for="editCustomerPhone">Phone</label>
                        <input type="text" class="form-control" id="editCustomerPhone" placeholder="Phone number">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="saveCustomerEditBtn">
                        Save Changes
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Delete Modal -->
    <div class="modal fade" id="deleteCustomerModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-user-times mr-2"></i>Delete Customer</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete customer <strong id="deleteCustomerName"></strong>?</p>
                    <p class="text-danger"><strong>Warning:</strong> This will also delete all reservations linked to this customer. This action cannot be undone.</p>
                    <input type="hidden" id="deleteCustomerCid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="deleteCustomerConfirmBtn">Yes, Delete</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <?php } ?>

</main>

<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+" crossorigin="anonymous"></script>

<script defer src="https://use.fontawesome.com/releases/v5.0.10/js/all.js"
        integrity="sha384-slN8GvtUJGnv6ca26v8EzVaR9DC58QEwsIk9q1QXdCU8Yu8ck/tL/5szYlBbqmS+"
        crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/select/1.2.5/js/dataTables.select.min.js"></script>
<script src="js/form-submission.js"></script>
<script src="js/admin.js"></script>
</body>
</html>
