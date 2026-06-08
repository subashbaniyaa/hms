const formIds = {
  register: '#registration-form',
  login: '#login-form',
  logout: '#sign-out-link',
  reservation: '#reservation-form',
  updateProfile: '#update-profile-form'
};

const formData = {
  registration: function () {
    return {
      fullName: $('input[name="registrationFullName"]').val(),
      phoneNumber: $("input[name='registrationPhoneNumber']").val(),
      email: $("input[name='registrationEmail']").val(),
      password: $("input[name='registrationPassword']").val(),
      password2: $("input[name='registrationPassword2']").val(),
      submitBtn: $('input[name="registerSubmitBtn"]').val()
    };
  },
  login: function () {
    return {
      email: $('input[name="loginEmail"]').val(),
      password: $('input[name="loginPassword"]').val(),
      submitBtn: $('input[name="loginSubmitBtn"]').val()
    };
  },
  reservation: function () {
    return {
      cid: $('input[name="cid"]').val(),
      start: $('input[name="startDate"]').val(),
      end: $('input[name="endDate"]').val(),
      type: $('select[name="roomType"]').val(),
      requirement: $('select[name="roomRequirement"]').val(),
      adults: $('select[name="adults"]').val(),
      children: $('select[name="children"]').val(),
      requests: $('textarea[name="specialRequests"]').val(),
      submitBtn: $('input[name="reservationSubmitBtn"]').val()
    };
  },
  updateProfile: function () {
    return {
      cid: $('input[name="customerId"]').val(),
      fullName: $('input[name="updateFullName"]').val(),
      phone: $("input[name='updatePhoneNumber']").val(),
      email: $("input[name='updateEmail']").val(),
      newPassword: $("input[name='updatePassword']").val(),
      submitBtn: $('input[name="updateProfileSubmitBtn"]').val()
    };
  }
};

// Helper: set a submit button into loading state and return a restore function
const setLoading = function (btnSelector, loadingText) {
  var btn = $(btnSelector);
  var original = btn.val() || btn.text();
  var isInput = btn.is('input');
  btn.prop('disabled', true);
  if (isInput) btn.val(loadingText || 'Please wait…');
  else btn.text(loadingText || 'Please wait…');
  return function () {
    btn.prop('disabled', false);
    if (isInput) btn.val(original);
    else btn.text(original);
  };
};

const registrationSubmit = function () {
  var restore = setLoading('input[name="registerSubmitBtn"]', 'Registering…');
  let registrationData = formData.registration();
  registrationData.submitBtn = 'updatebtn';
  let dataStr = Object.values(registrationData).join(' ');
  if (!new UtilityFunctions().findMatchReservedWords(dataStr)) {
    $.ajax({
      url: 'app/process_registration.php',
      type: 'post',
      data: registrationData
    }).done(function (response) {
      $(formIds.register).find('.alert').remove();
      $(formIds.register).prepend(response);
    }).fail(function () {
      $(formIds.register).find('.alert').remove();
      $(formIds.register).prepend(
        '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
        'A network error occurred. Please check your connection and try again.' +
        '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>'
      );
    }).always(function () {
      restore();
    });
  } else {
    restore();
    alert('Something went wrong! Please check your input.');
  }
};

const loginSubmit = function () {
  var restore = setLoading('input[name="loginSubmitBtn"]', 'Signing in…');
  let loginData = formData.login();
  $.ajax({
    url: 'app/process_login.php',
    type: 'post',
    data: loginData
  }).done(function (response) {
    try {
      let resp = JSON.parse(response);
      if (resp[0] === 1) {
        new UtilityFunctions().setCookie('is_admin', resp[1]);
        let locHref = location.href;
        let homePageLink = locHref.substring(0, locHref.lastIndexOf('/')) + '/index.php';
        window.location.replace(homePageLink);
        return; // don't restore — page is navigating
      } else {
        $(formIds.login).find('.alert').remove();
        $(formIds.login).prepend(response);
      }
    } catch (e) {
      // Server returned HTML error alert, not JSON — display it
      $(formIds.login).find('.alert').remove();
      $(formIds.login).prepend(response);
    }
    restore();
  }).fail(function () {
    $(formIds.login).find('.alert').remove();
    $(formIds.login).prepend(
      '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
      'A network error occurred. Please check your connection and try again.' +
      '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>'
    );
    restore();
  });
};

const clickSignOut = function () {
  // Direct redirect to logout.php — reliable server-side session destruction
  window.location.href = 'logout.php';
};

const reservationSubmit = function () {
  let reservation = formData.reservation();
  $.ajax({
    url: 'app/process_reservation.php',
    type: 'post',
    data: reservation
  }).done(function (response) {
    $(formIds.reservation).find('.alert').remove();
    try {
      let out = JSON.parse(response);
      if (out.success === 'true') {
        $(formIds.reservation).prepend(out.response);
        $(formIds.reservation).find('input[type=submit]').prop('disabled', true);
      }
    } catch (string) {
      $(formIds.reservation).prepend(response);
    }
  }).fail(function () {
    $(formIds.reservation).find('.alert').remove();
    $(formIds.reservation).prepend(
      '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
      'A network error occurred. Please try again.' +
      '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>'
    );
  });
};

const updateProfileSubmit = function () {
  var restore = setLoading('input[name="updateProfileSubmitBtn"]', 'Saving…');
  let updateData = formData.updateProfile();
  updateData.submitBtn = 'updatebtn';

  let dataStr = Object.values(updateData).join(' ');
  if (!new UtilityFunctions().findMatchReservedWords(dataStr)) {
    $.ajax({
      url: 'app/process_update_profile.php',
      type: 'post',
      data: updateData
    }).done(function (response) {
      $(formIds.updateProfile).find('.alert').remove();
      try {
        let out = JSON.parse(response);
        $(formIds.updateProfile).prepend(out.response);
      } catch (e) {
        $(formIds.updateProfile).prepend(response);
      }
      restore();
    }).fail(function () {
      $(formIds.updateProfile).find('.alert').remove();
      $(formIds.updateProfile).prepend(
        '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
        'A network error occurred. Please check your connection and try again.' +
        '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>'
      );
      restore();
    });
  } else {
    restore();
    alert('Something went wrong! Please check your input.');
  }
};

$(document).ready(function () {
  $(formIds.register).submit(function (event) {
    registrationSubmit();
    event.preventDefault();
    return false;
  });

  $(formIds.login).submit(function (event) {
    loginSubmit();
    event.preventDefault();
    return false;
  });

  $(formIds.logout).on('click', function (event) {
    event.preventDefault();
    clickSignOut();
    return false;
  });

  $(formIds.reservation).submit(function (event) {
    reservationSubmit();
    event.preventDefault();
    return false;
  });

  $(formIds.updateProfile).submit(function (event) {
    updateProfileSubmit();
    event.preventDefault();
    return false;
  });
});

// success: set success action before making the request
// done: set success action just after starting the request
