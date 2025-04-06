/**
 * JavaScript for grade management
 */

// Document ready function
$(document).ready(function () {
  // Initialize grade form
  initGradeForm();

  // Add event listener for course selection
  $("#course-select").on("change", function () {
    loadStudentsList();
  });

  // Add event listener for term selection
  $("#term-select").on("change", function () {
    loadCoursesList();
  });

  // Save grades button
  $("#save-grades-btn").on("click", function () {
    saveGrades();
  });
});

/**
 * Initialize the grade form
 */
function initGradeForm() {
  loadTermsList();
}

/**
 * Load terms list
 */
function loadTermsList() {
  $.ajax({
    url: "api/terms.php",
    type: "GET",
    dataType: "json",
    success: function (response) {
      if (response.success) {
        var termSelect = $("#term-select");
        termSelect.empty();
        termSelect.append('<option value="">Dönem Seçiniz</option>');

        $.each(response.data, function (index, term) {
          termSelect.append(
            '<option value="' + term.id + '">' + term.name + "</option>"
          );
        });

        // Select current term if available
        if (response.current_term_id) {
          termSelect.val(response.current_term_id);
        }

        // Load courses for selected term
        loadCoursesList();
      } else {
        showAlert("Dönem listesi yüklenirken bir hata oluştu.", "danger");
      }
    },
    error: function (xhr, status, error) {
      showAlert(
        "Dönem listesi yüklenirken bir hata oluştu: " + error,
        "danger"
      );
    },
  });
}

/**
 * Load courses list for the selected term
 */
function loadCoursesList() {
  var termId = $("#term-select").val();

  // Clear course select and students list if no term is selected
  if (!termId) {
    $("#course-select")
      .empty()
      .append('<option value="">Önce Dönem Seçiniz</option>');
    $("#students-list").empty();
    $("#save-grades-btn").prop("disabled", true);
    return;
  }

  $.ajax({
    url: "api/courses.php",
    type: "GET",
    dataType: "json",
    data: { term_id: termId },
    success: function (response) {
      if (response.success) {
        var courseSelect = $("#course-select");
        courseSelect.empty();
        courseSelect.append('<option value="">Ders Seçiniz</option>');

        if (response.data.length > 0) {
          $.each(response.data, function (index, course) {
            courseSelect.append(
              '<option value="' +
                course.id +
                '">' +
                course.code +
                " - " +
                course.name +
                "</option>"
            );
          });
        } else {
          courseSelect.append(
            '<option value="" disabled>Bu dönem için atanmış ders bulunamadı</option>'
          );
        }

        // Clear students list when course changes
        $("#students-list").empty();
        $("#save-grades-btn").prop("disabled", true);
      } else {
        showAlert("Ders listesi yüklenirken bir hata oluştu.", "danger");
      }
    },
    error: function (xhr, status, error) {
      showAlert("Ders listesi yüklenirken bir hata oluştu: " + error, "danger");
    },
  });
}

/**
 * Load students list for the selected course and term
 */
function loadStudentsList() {
  var courseId = $("#course-select").val();
  var termId = $("#term-select").val();

  // Clear students list if no course is selected
  if (!courseId || !termId) {
    $("#students-list").empty();
    $("#save-grades-btn").prop("disabled", true);
    return;
  }

  $.ajax({
    url: "api/course_students.php",
    type: "GET",
    dataType: "json",
    data: {
      course_id: courseId,
      term_id: termId,
    },
    success: function (response) {
      if (response.success) {
        var studentsList = $("#students-list");
        studentsList.empty();

        if (response.data.length > 0) {
          // Create table header
          var table =
            '<div class="table-responsive">' +
            '<table class="table table-striped table-bordered">' +
            '<thead class="thead-dark">' +
            "<tr>" +
            "<th>Öğrenci No</th>" +
            "<th>Ad Soyad</th>" +
            "<th>Not</th>" +
            "</tr>" +
            "</thead>" +
            "<tbody>";

          // Add students rows
          $.each(response.data, function (index, student) {
            table +=
              "<tr>" +
              "<td>" +
              student.student_id +
              "</td>" +
              "<td>" +
              student.name +
              " " +
              student.surname +
              "</td>" +
              "<td>" +
              '<input type="number" class="form-control grade-input" ' +
              'id="grade-' +
              student.id +
              '" ' +
              'name="grades[' +
              student.id +
              ']" ' +
              'min="0" max="100" step="1" ' +
              'value="' +
              (student.grade ? student.grade : "") +
              '">' +
              "</td>" +
              "</tr>";
          });

          table += "</tbody></table></div>";
          studentsList.append(table);

          // Enable save button
          $("#save-grades-btn").prop("disabled", false);
        } else {
          studentsList.append(
            '<div class="alert alert-info">Bu derse kayıtlı öğrenci bulunamadı.</div>'
          );
          $("#save-grades-btn").prop("disabled", true);
        }
      } else {
        showAlert("Öğrenci listesi yüklenirken bir hata oluştu.", "danger");
      }
    },
    error: function (xhr, status, error) {
      showAlert(
        "Öğrenci listesi yüklenirken bir hata oluştu: " + error,
        "danger"
      );
    },
  });
}

/**
 * Save grades for all students in the list
 */
function saveGrades() {
  var courseId = $("#course-select").val();
  var termId = $("#term-select").val();

  if (!courseId || !termId) {
    showAlert("Lütfen dönem ve ders seçiniz.", "warning");
    return;
  }

  // Collect all grades
  var grades = {};
  var isValid = true;

  $(".grade-input").each(function () {
    var studentId = $(this).attr("id").split("-")[1];
    var grade = $(this).val();

    // Validate each grade
    if (grade !== "") {
      if (isNaN(grade) || grade < 0 || grade > 100) {
        showAlert("Notlar 0-100 arasında olmalıdır.", "warning");
        $(this).focus();
        isValid = false;
        return false; // Break the loop
      }
      grades[studentId] = grade;
    }
  });

  if (!isValid) return;

  // Display confirmation dialog
  if (!confirm("Girilen notları kaydetmek istediğinizden emin misiniz?")) {
    return;
  }

  // Send grades to server
  $.ajax({
    url: "api/save_grades.php",
    type: "POST",
    dataType: "json",
    data: {
      course_id: courseId,
      term_id: termId,
      grades: grades,
    },
    success: function (response) {
      if (response.success) {
        showAlert("Notlar başarıyla kaydedildi.", "success");

        // Refresh the students list to show updated grades
        loadStudentsList();
      } else {
        showAlert(
          "Notlar kaydedilirken bir hata oluştu: " + response.message,
          "danger"
        );
      }
    },
    error: function (xhr, status, error) {
      showAlert("Notlar kaydedilirken bir hata oluştu: " + error, "danger");
    },
  });
}

/**
 * Show alert message in the page
 * @param {string} message - The message to display
 * @param {string} type - Alert type (success, info, warning, danger)
 */
function showAlert(message, type) {
  // Remove any existing alerts
  $("#alert-container").remove();

  // Create alert container if it doesn't exist
  if ($("#alert-container").length === 0) {
    $('<div id="alert-container"></div>').insertBefore("#grade-form");
  }

  // Create alert
  var alert =
    '<div class="alert alert-' +
    type +
    ' alert-dismissible fade show">' +
    message +
    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
    "</div>";

  // Show alert
  $("#alert-container").html(alert);

  // Auto dismiss after 5 seconds
  setTimeout(function () {
    $("#alert-container .alert").alert("close");
  }, 5000);
}
