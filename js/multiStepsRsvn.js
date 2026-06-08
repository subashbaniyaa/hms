// Room prices — injected by PHP from room_prices.json so admin changes always reflect here
let DELUXE_PER_NIGHT = (window.ROOM_PRICES && window.ROOM_PRICES.deluxe) ? window.ROOM_PRICES.deluxe : null;
let DOUBLE_PER_NIGHT = (window.ROOM_PRICES && window.ROOM_PRICES.double) ? window.ROOM_PRICES.double : null;
let SINGLE_PER_NIGHT = (window.ROOM_PRICES && window.ROOM_PRICES.single) ? window.ROOM_PRICES.single : null;

const multiStepRsvnFormId = '#multiStepRsvnForm';
const multiStepRsvnformData = {
  cDate: function (dt) {
    let subject = new Date(dt);
    return [subject.getFullYear(), subject.getMonth() + 1, subject.getDate()].join('-');
  },
  d: function () {
    return {
      cid: $('input[name="cid"]').val(),
      start: $('input[name="startDate"]').val(),
      end: $('input[name="endDate"]').val(),
      type: $('select[name="roomType"]').val(),
      requirement: $('select[name="roomRequirement"]').val(),
      adults: $('select[name="adults"]').val(),
      children: $('select[name="children"]').val(),
      requests: $('textarea[name="specialRequests"]').val(),
      bookedDate: multiStepRsvnformData.cDate(document.getElementsByClassName('bookedDateTxt')[0].innerHTML),
      numNights: document.getElementsByClassName('numNightsTxt')[0].innerHTML,
      totalPrice: document.getElementsByClassName('totalTxt')[0].innerHTML,
      readySubmit: $('#rsvnNextBtn').attr('readySubmit')
    };
  }
};

// rsvn multi steps
let currentTab = 0;
showTab(currentTab);

function showTab (n) {
  let x = document.getElementsByClassName('rsvnTab');
  x[n].style.display = 'block';
  if (n === 0) {
    document.getElementById('rsvnPrevBtn').style.display = 'none';
  } else {
    document.getElementById('rsvnPrevBtn').style.display = 'inline';
  }
  let policiesBtn = document.getElementById('checkinPoliciesBtn');
  if (policiesBtn) {
    policiesBtn.style.display = (n === 0) ? 'inline-block' : 'none';
  }
  let rsvnNextBtn = $('#rsvnNextBtn');
  if (n === (x.length - 1)) {
    rsvnNextBtn.text('Submit');
    rsvnNextBtn.attr('readySubmit', 'true');
    rsvnNextBtn.attr('type', 'submit');
    rsvnNextBtn.attr('onclick', 'submitMultiStepRsvn()');
  } else {
    rsvnNextBtn.text('Next');
    rsvnNextBtn.attr('readySubmit', 'false');
    rsvnNextBtn.attr('type', 'button');
    rsvnNextBtn.attr('onclick', 'rsvnNextPrev(1)');
  }
  fixStepIndicator(n);
}

function submitMultiStepRsvn () {
  let canSubmit = document.getElementById('rsvnNextBtn').getAttribute('readySubmit');
  if (!validateRsvnForm() && !canSubmit) {
    return false;
  } else {
    let d = multiStepRsvnformData.d();
    let dataStr = Object.values(d).join(' ');
    if (!new UtilityFunctions().findMatchReservedWords(dataStr)) {
      let submitBtn = document.getElementById('rsvnNextBtn');
      submitBtn.disabled = true;
      submitBtn.textContent = 'Submitting…';

      $.ajax({
        url: 'app/process_reservation.php',
        type: 'post',
        data: d
      }).done(function (response) {
        try {
          let out = JSON.parse(response);
          $(multiStepRsvnFormId).find('.alert').remove();
          $(multiStepRsvnFormId).prepend(out.response);
          submitBtn.textContent = 'Submit';
          if (out.success !== 'true') {
            // Re-enable button so user can correct and retry
            submitBtn.disabled = false;
          }
        } catch (e) {
          $(multiStepRsvnFormId).find('.alert').remove();
          $(multiStepRsvnFormId).prepend(response);
          submitBtn.disabled = false;
          submitBtn.textContent = 'Submit';
        }
      }).fail(function () {
        $(multiStepRsvnFormId).find('.alert').remove();
        $(multiStepRsvnFormId).prepend(
          '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
          'A network error occurred. Please check your connection and try again.' +
          '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>'
        );
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit';
      });
    } else {
      alert('Something went wrong! Please check your input.');
    }
  }
}

function fixStepIndicator (n) {
  let i;
  let x = document.getElementsByClassName('step');
  for (i = 0; i < x.length; i++) {
    x[i].className = x[i].className.replace(' active', '');
  }
  if (x[n]) x[n].className += ' active';
}

function rsvnNextPrev (n) {
  let x = document.getElementsByClassName('rsvnTab');
  if (n === 1 && !validateRsvnForm()) return false;
  x[currentTab].style.display = 'none';
  currentTab = currentTab + n;
  showTab(currentTab);
}

function validateRsvnForm () {
  let tab = document.getElementsByClassName('rsvnTab');
  let valid = true;
  let inputs = tab[currentTab].getElementsByTagName('input');
  for (let i = 0; i < inputs.length; i++) {
    if (inputs[i].hasAttribute('required')) {
      if (inputs[i].value === '') {
        inputs[i].className += ' invalid';
        valid = false;
      }
    }
  }

  let selects = tab[currentTab].getElementsByTagName('select');
  for (let i = 0; i < selects.length; i++) {
    if (selects[i].hasAttribute('required')) {
      if (selects[i].value === '') {
        selects[i].className += ' invalid';
        valid = false;
      }
    }
  }

  if (valid) {
    let _step = document.getElementsByClassName('step')[currentTab]; if (_step) _step.className += ' finish';
    new ReservationCost($('select[name="roomType"]').val(),
      $('input[name="startDate"]').val(),
      $('input[name="endDate"]').val()).displayAll();
  }
  return valid;
}

class ReservationCost {
  constructor (roomType, startDate, endDate) {
    let today = new Date();
    this.bookDate = today.toDateString();
    this.roomType = roomType;
    this.startDate = new Date(startDate);
    this.endDate = new Date(endDate);
  }

  priceByRoomType () {
    if (this.roomType === 'Deluxe') {
      return DELUXE_PER_NIGHT;
    }
    if (this.roomType === 'Double') {
      return DOUBLE_PER_NIGHT;
    }
    if (this.roomType === 'Single') {
      return SINGLE_PER_NIGHT;
    }
    return 0;
  }

  numNights () {
    return new UtilityFunctions().dateDiffInDays(this.startDate, this.endDate);
  }

  displayBookedDate () {
    document.getElementsByClassName('bookedDateTxt')[0].innerHTML = this.bookDate;
  }

  displayRoomPrice () {
    document.getElementsByClassName('roomPriceTxt')[0].innerHTML = this.priceByRoomType();
  }

  displayRoomType () {
    document.getElementsByClassName('roomTypeTxt')[0].innerHTML = this.roomType;
  }

  displayNumNights () {
    document.getElementsByClassName('numNightsTxt')[0].innerHTML = this.numNights().toString();
  }

  displayFromTo () {
    // Fixed: was incorrectly adding +1 to getDate() on both start and end
    let start = this.startDate.getFullYear() + '-' + (this.startDate.getMonth() + 1) + '-' + this.startDate.getDate();
    let end = this.endDate.getFullYear() + '-' + (this.endDate.getMonth() + 1) + '-' + this.endDate.getDate();
    document.getElementsByClassName('fromToTxt')[0].innerHTML = start + ' to ' + end;
  }

  displayTotalCost () {
    let totalRoomPrice = (this.numNights() * this.priceByRoomType());
    let taxesTxt = document.getElementsByClassName('taxesTxt')[0].innerHTML;
    document.getElementsByClassName('totalTxt')[0].innerHTML = (totalRoomPrice + parseInt(taxesTxt));
  }

  displayAll () {
    this.displayBookedDate();
    this.displayRoomType();
    this.displayRoomPrice();
    this.displayNumNights();
    this.displayFromTo();
    this.displayTotalCost();
  }
}
