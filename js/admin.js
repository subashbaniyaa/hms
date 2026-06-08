const getTblContainer = function () {
  return $('#tableContainer');
};
const appendResponse = function (response) {
  getTblContainer().find('.alert').remove();
  getTblContainer().prepend(response);
};
const appendCustomerResponse = function (response) {
  $('#customerTableContainer').find('.alert').remove();
  $('#customerTableContainer').prepend(response);
};
const getSelectedItems = function () {
  return $('#reservationDataTable tr.selected');
};
const actions = {
  confirm: function () {
    return {
      mainBtn: $('#confirm-booking'),
      modalId: $('#confirmModal'),
      modalYesBtn: $('#confirmTrue')
    };
  },
  cancel: function () {
    return {
      mainBtn: $('#cancel-booking'),
      modalId: $('#cancelModal'),
      modalYesBtn: $('#cancelTrue')
    };
  },
  deleteReservation: function () {
    return {
      mainBtn: $('#delete-booking'),
      modalId: $('#deleteReservationModal'),
      modalYesBtn: $('#deleteReservationTrue')
    };
  }
};
const getBookIdFromSelected = function () {
  let id = [];
  getSelectedItems().each(function () {
    id.push($(this).find('td').attr('data-id'));
  });
  return id;
};
const confirmReservation = function () {
  actions.confirm().mainBtn.click(function () {
    if (getSelectedItems().length === 0) {
      alert('Please select at least one reservation.');
      return false;
    }
    actions.confirm().modalId.modal('show');
    actions.confirm().modalYesBtn.off('click').click(function (e) {
      e.preventDefault();
      confirmAjaxRequest(getBookIdFromSelected());
    });
  });
};
const confirmAjaxRequest = function (selectedItems) {
  $.ajax({
    url: 'app/admin/manage_reservation.php',
    type: 'post',
    data: {item: selectedItems, confirm: true}
  }).done(function (response) {
    actions.confirm().modalId.modal('hide');
    appendResponse(response);
    setTimeout(location.reload.bind(location), 3000);
  }).fail(function () {
    actions.confirm().modalId.modal('hide');
    appendResponse('<div class="alert alert-danger alert-dismissible fade show" role="alert">Network error. Could not confirm reservation(s). Please try again.<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>');
  });
};
const cancelReservation = function () {
  actions.cancel().mainBtn.click(function () {
    if (getSelectedItems().length === 0) {
      alert('Please select at least one reservation.');
      return false;
    }
    actions.cancel().modalId.modal('show');
    actions.cancel().modalYesBtn.off('click').click(function (e) {
      e.preventDefault();
      cancelAjaxRequest(getBookIdFromSelected());
    });
  });
};
const cancelAjaxRequest = function (selectedItems) {
  $.ajax({
    url: 'app/admin/manage_reservation.php',
    type: 'post',
    data: {item: selectedItems, cancel: true}
  }).done(function (response) {
    actions.cancel().modalId.modal('hide');
    appendResponse(response);
    setTimeout(location.reload.bind(location), 3000);
  }).fail(function () {
    actions.cancel().modalId.modal('hide');
    appendResponse('<div class="alert alert-danger alert-dismissible fade show" role="alert">Network error. Could not cancel reservation(s). Please try again.<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>');
  });
};

const deleteReservation = function () {
  actions.deleteReservation().mainBtn.click(function () {
    if (getSelectedItems().length === 0) {
      alert('Please select at least one reservation to delete.');
      return false;
    }
    actions.deleteReservation().modalId.modal('show');
    actions.deleteReservation().modalYesBtn.off('click').click(function (e) {
      e.preventDefault();
      deleteReservationAjaxRequest(getBookIdFromSelected());
    });
  });
};
const deleteReservationAjaxRequest = function (selectedItems) {
  $.ajax({
    url: 'app/admin/manage_reservation.php',
    type: 'post',
    data: {item: selectedItems, delete: true}
  }).done(function (response) {
    actions.deleteReservation().modalId.modal('hide');
    appendResponse(response);
    setTimeout(location.reload.bind(location), 3000);
  }).fail(function () {
    actions.deleteReservation().modalId.modal('hide');
    appendResponse('<div class="alert alert-danger alert-dismissible fade show" role="alert">Network error. Could not delete reservation(s). Please try again.<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>');
  });
};

const saveNote = function (bookingId, notes) {
  // bookingId may be a scalar or an array (for bulk note from "With selected")
  $.ajax({
    url: 'app/admin/manage_notes.php',
    type: 'post',
    data: {booking_id: bookingId, notes: notes}
  }).done(function (response) {
    $('#notesModal').modal('hide');
    appendResponse(response);
    setTimeout(location.reload.bind(location), 2500);
  }).fail(function () {
    alert('Failed to save note. Please try again.');
  });
};

const saveRoomPrices = function (deluxe, double, single) {
  $.ajax({
    url: 'app/admin/manage_room_prices.php',
    type: 'post',
    data: {deluxe: deluxe, double: double, single: single}
  }).done(function (response) {
    $('#pricesContainer').find('.alert').remove();
    $('#pricesContainer').prepend(response);
    setTimeout(location.reload.bind(location), 2000);
  }).fail(function () {
    alert('Failed to update prices. Please try again.');
  });
};

const viewRSVProws = {
  tableId: null,
  viewOption: null,
  setConstruct: function (tableId, viewOption) {
    viewRSVProws.tableId = tableId;
    viewRSVProws.viewOption = viewOption;
  },
  defaultConfig: function () {
    $(viewRSVProws.tableId).DataTable({
      select: {style: 'multi'},
      'pageLength': 6
    });
  },
  main: function () {
    $(viewRSVProws.viewOption).change(function () {
      if (this.checked) {
        var val = this.value;
        if (val === 'confirmed') {
          $(viewRSVProws.tableId).DataTable().search('CONFIRMED').draw();
        } else if (val === 'pending') {
          $(viewRSVProws.tableId).DataTable().search('PENDING').draw();
        } else if (val === 'cancelled') {
          $(viewRSVProws.tableId).DataTable().search('CANCELLED').draw();
        } else if (val === 'checked in') {
          $(viewRSVProws.tableId).DataTable().search('CHECKED IN').draw();
        } else if (val === 'checked out') {
          $(viewRSVProws.tableId).DataTable().search('CHECKED OUT').draw();
        } else {
          // 'all' — just clear the search filter, never destroy the table
          $(viewRSVProws.tableId).DataTable().search('').draw();
        }
      }
    });
  }
};

$(document).ready(function () {
  // Enable Bootstrap tooltips (used for truncated Notes column)
  $('[data-toggle="tooltip"]').tooltip();

  // Card footer clicks: switch to the right tab and apply status filter
  $('.card-footer[href]').on('click', function (e) {
    e.preventDefault();
    var tabTarget = $(this).attr('href');          // e.g. '#reservation'
    var filter    = $(this).data('filter') || '';  // 'confirmed' | 'pending' | 'cancelled' | 'all' | ''

    // Switch to target tab
    $('#adminTab a[href="' + tabTarget + '"]').tab('show');

    // Apply DataTable filter via the radio buttons
    if (filter && filter !== 'all') {
      var radio = $('input[type=radio][name=viewOption][value="' + filter + '"]');
      radio.prop('checked', true).trigger('change');
    } else if (filter === 'all') {
      var allRadio = $('input[type=radio][name=viewOption][value="all"]');
      allRadio.prop('checked', true).trigger('change');
    }
  });
  viewRSVProws.setConstruct('#reservationDataTable', 'input[type=radio][name=viewOption]');
  viewRSVProws.defaultConfig();
  viewRSVProws.main();
  $('#customerTable').DataTable();
  confirmReservation();
  cancelReservation();
  deleteReservation();

  // Watch DataTable row selection — enable Add Note only when exactly 1 row is selected
  // Bind directly on the table element (DataTables Select fires DOM events here)
  $('#reservationDataTable').on('select.dt deselect.dt', function () {
    var count = getSelectedItems().length;
    if (count === 1) {
      $('#bulk-add-note').prop('disabled', false).attr('title', 'Add note to selected reservation');
    } else {
      $('#bulk-add-note').prop('disabled', true).attr('title', 'Select exactly 1 reservation to add a note');
    }
  });

  // "Add Note" from "With selected" toolbar — exactly 1 row must be selected
  $('#bulk-add-note').on('click', function () {
    var count = getSelectedItems().length;
    if (count !== 1) {
      return; // button is disabled anyway, but guard just in case
    }
    var ids = getBookIdFromSelected();
    $('#notesBookingId').val(ids[0]);
    $('#notesTextarea').val('');
    $('#notesModal').modal('show');
  });

  // Save note button — single ID only (bulk removed)
  $('#saveNoteBtn').on('click', function () {
    let bookingId = $('#notesBookingId').val();
    let notes = $('#notesTextarea').val();
    if (!bookingId) {
      alert('Invalid booking ID.');
      return;
    }
    saveNote(bookingId, notes);
  });

  // Enter key inside Update Customer modal fields triggers Save Changes
  $('#editCustomerModal').on('keydown', function (e) {
    if (e.key === 'Enter' && !$(e.target).is('textarea')) {
      e.preventDefault();
      $('#saveCustomerEditBtn').trigger('click');
    }
  });

  // Save room prices
  $('#savePricesBtn').on('click', function () {
    let deluxe = parseInt($('#priceDeluxe').val());
    let dbl = parseInt($('#priceDouble').val());
    let single = parseInt($('#priceSingle').val());
    if (isNaN(deluxe) || isNaN(dbl) || isNaN(single) || deluxe <= 0 || dbl <= 0 || single <= 0) {
      alert('Please enter valid prices greater than zero.');
      return;
    }
    saveRoomPrices(deluxe, dbl, single);
  });

  // ---- CUSTOMER: Edit ----
  $(document).on('click', '.edit-customer-btn', function () {
    let cid      = $(this).data('cid');
    let fullname = $(this).data('fullname');
    let email    = $(this).data('email');
    let phone    = $(this).data('phone');
    $('#editCustomerCid').val(cid);
    $('#editCustomerFullname').val(fullname);
    $('#editCustomerEmail').val(email);
    $('#editCustomerPhone').val(phone);
    $('#editCustomerModal').modal('show');
  });

  $('#saveCustomerEditBtn').on('click', function () {
    let cid      = $('#editCustomerCid').val();
    let fullname = $('#editCustomerFullname').val().trim();
    let phone    = $('#editCustomerPhone').val().trim();
    if (!cid || !fullname) {
      alert('Name is required.');
      return;
    }
    $.ajax({
      url: 'app/admin/manage_customer.php',
      type: 'post',
      data: {action: 'update', cid: cid, fullname: fullname, phone: phone}
    }).done(function (response) {
      $('#editCustomerModal').modal('hide');
      appendCustomerResponse(response);
      setTimeout(location.reload.bind(location), 2500);
    }).fail(function () {
      alert('Network error. Could not update customer. Please try again.');
    });
  });

  // ---- CUSTOMER: Delete ----
  $(document).on('click', '.delete-customer-btn', function () {
    let cid  = $(this).data('cid');
    let name = $(this).data('name');
    $('#deleteCustomerCid').val(cid);
    $('#deleteCustomerName').text(name);
    $('#deleteCustomerModal').modal('show');
  });

  $('#deleteCustomerConfirmBtn').on('click', function () {
    let cid = $('#deleteCustomerCid').val();
    if (!cid) {
      alert('Invalid customer ID.');
      return;
    }
    $.ajax({
      url: 'app/admin/manage_customer.php',
      type: 'post',
      data: {action: 'delete', cid: cid}
    }).done(function (response) {
      $('#deleteCustomerModal').modal('hide');
      appendCustomerResponse(response);
      setTimeout(location.reload.bind(location), 2500);
    }).fail(function () {
      alert('Network error. Could not delete customer. Please try again.');
    });
  });
});
