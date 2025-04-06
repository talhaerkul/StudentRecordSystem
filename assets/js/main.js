// Import jQuery
$(document).ready(() => {
  console.log("main.js yüklendi!"); // Bu mesajı konsolda görmelisiniz

  // Form submission handler - attach to the form directly
  $("#add_user").on("submit", function (event) {
    // Prevent the default form submission
    event.preventDefault();

    // Call the validation function
    if (validateForm("add_user")) {
      // If validation passes, manually submit the form
      this.submit();
    }
    // If validation fails, the form won't be submitted
  });

  // Role selection change handler
  $("#role_id").on("change", function () {
    var roleId = $(this).val();
    console.log("Seçilen rol ID:", roleId); // Seçilen rol ID'sini konsola yazdırın

    var teacherFields = $("#teacherFields");
    var studentFields = $("#studentFields");

    console.log("teacherFields:", teacherFields.length); // teacherFields öğesinin sayısını kontrol edin
    console.log("studentFields:", studentFields.length); // studentFields öğesinin sayısını kontrol edin

    const ROLE_TEACHER = 3;
    const ROLE_STUDENT = 4;

    if (roleId == ROLE_TEACHER) {
      console.log("Öğretmen alanları gösteriliyor");
      teacherFields.show();
      studentFields.hide();
    } else if (roleId == ROLE_STUDENT) {
      console.log("Öğrenci alanları gösteriliyor");
      teacherFields.hide();
      studentFields.show();
    } else {
      console.log("Hiçbir alan gösterilmiyor");
      teacherFields.hide();
      studentFields.hide();
    }
  });

  // Initialize Bootstrap tooltips
  $('[data-toggle="tooltip"]').tooltip();

  // Initialize Bootstrap popovers
  $('[data-toggle="popover"]').popover();

  // Auto-dismiss alerts after 5 seconds
  setTimeout(() => {
    $(".alert-dismissible").alert("close");
  }, 5000);

  // Confirm delete operations
  $(".delete-btn").on("click", (e) => {
    if (!confirm("Bu kaydı silmek istediğinizden emin misiniz?")) {
      e.preventDefault();
    }
  });

  // Custom file input label update
  $(".custom-file-input").on("change", function () {
    var fileName = $(this).val().split("\\").pop();
    $(this).next(".custom-file-label").html(fileName);
  });

  // Auto-focus first input in modals
  $(".modal").on("shown.bs.modal", function () {
    $(this).find("input:first").focus();
  });

  // Add active class to current nav item
  var currentPage = window.location.pathname.split("/").pop();
  $(".navbar-nav .nav-link").each(function () {
    var linkPage = $(this).attr("href");
    if (linkPage === currentPage) {
      $(this).closest("li").addClass("active");
    }
  });
});
/**
 * Validate form with custom rules
 * @param {string} formId - The ID of the form to validate
 * @return {boolean} True if form is valid, false otherwise
 */
function validateForm(formId) {
  var form = document.getElementById(formId);

  if (!form) {
    console.error("Form not found with ID: " + formId);
    return false;
  }

  // Check required fields
  var requiredFields = form.querySelectorAll("[required]");
  for (var i = 0; i < requiredFields.length; i++) {
    if (!requiredFields[i].value.trim()) {
      alert("Lütfen tüm zorunlu alanları doldurunuz.");
      requiredFields[i].focus();
      return false;
    }
  }

  // Check email fields
  var emailFields = form.querySelectorAll('input[type="email"]');
  var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  for (var j = 0; j < emailFields.length; j++) {
    if (emailFields[j].value && !emailRegex.test(emailFields[j].value)) {
      alert("Lütfen geçerli bir e-posta adresi giriniz.");
      emailFields[j].focus();
      return false;
    }
  }

  // Get the selected role_id
  var roleId = document.getElementById("role_id").value;

  // If role_id is 4, check if the email ends with @stu.okan.edu.tr
  if (roleId == 4) {
    for (var k = 0; k < emailFields.length; k++) {
      if (
        emailFields[k].value &&
        !emailFields[k].value.endsWith("@stu.okan.edu.tr")
      ) {
        alert(
          "Öğrenciler için e-posta adresi @stu.okan.edu.tr ile bitmelidir."
        );
        emailFields[k].focus();
        return false;
      }
    }
  }

  // Role-specific validation
  if (roleId == 3) {
    // Teacher
    var title = document.getElementById("title");
    var specialization = document.getElementById("specialization");
    var phone = document.getElementById("phone");

    if (
      !title.value.trim() ||
      !specialization.value.trim() ||
      !phone.value.trim()
    ) {
      alert("Lütfen tüm öğretmen bilgilerini doldurunuz.");
      return false;
    }
  } else if (roleId == 4) {
    // Student
    var studentId = document.getElementById("student_id");
    var birthdate = document.getElementById("birthdate");
    var address = document.getElementById("address");
    var advisorId = document.getElementById("advisor_id");
    var entryYear = document.getElementById("entry_year");

    if (
      !studentId.value.trim() ||
      !birthdate.value.trim() ||
      !address.value.trim() ||
      !advisorId.value.trim() ||
      !entryYear.value.trim()
    ) {
      alert("Lütfen tüm öğrenci bilgilerini doldurunuz.");
      return false;
    }
  }

  return true; // Form is valid
}

/**
 * Format date for display
 * @param {string} dateString - The date string to format
 * @return {string} Formatted date string
 */
function formatDate(dateString) {
  var date = new Date(dateString);
  return date.toLocaleDateString("tr-TR");
}
