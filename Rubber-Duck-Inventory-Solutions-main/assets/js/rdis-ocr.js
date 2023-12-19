jQuery(function ($) {
  //start scanner cropper
  var $imageToCrop = $("#image-to-crop");
  var cropper;
  let tries = 0;

  $("#rdis-image-input").on("change", function (e) {
    var files = e.target.files;
    var reader = new FileReader();
    reader.onload = function (event) {
      $imageToCrop.attr("src", event.target.result);
      $("#rdis-drop-zone").hide();
      $("#image-cropping-container").show();
      cropper = new Cropper($imageToCrop[0], {
        // Cropper options
      });
    };
    reader.readAsDataURL(files[0]);
  });

  // When the user clicks "Crop & Save"
  $("#rdis-matching button.rdis-scanner-submit").on("click", function () {
    if (cropper !== undefined) {
      tries++;
      var $nextButton = $("#rdis-matching button.rdis-scanner-submit");
      $nextButton.html(
        "Processing <span class='spinner-border spinner-border-sm text-secondary'></span>"
      );
      cropper.getCroppedCanvas().toBlob(function (blob) {
        var formData = new FormData();
        let hasError = false;

        formData.append("croppedImage", blob, "image.png");
        formData.append(
          "process_action",
          $("#rdis-matching").hasClass("adding") ? "add" : "sell"
        );
        formData.append("action", "rdis_handle_cropped_image");

        $.ajax({
          url: ajax_object.ajax_url,
          method: "POST",
          data: formData,
          processData: false,
          contentType: false,
          success: function (response) {
            if (response.success && $("#rdis-matching").hasClass("adding")) {
              window.handleAddResponse(response);
              handleTries(tries, response);
            } else if ($("#rdis-matching").hasClass("selling")) {
              window.handleSellResponse(response);
              handleTries(tries, response);
            } else {
              $("#rdis-ocr-results").html(response.data);
              $nextButton.html("Try Again");
              handleTries(tries, response);
            }
          },
          error: function (xhr, status, error) {
            console.error(error, status);
            $nextButton.html("Try Again");
            if ($("#manualInputDiv").data("process") == undefined) {
              $("#rdis-matching").hasClass("adding")
                ? $("#manualInputDiv").data("process", "add")
                : $("#manualInputDiv").data("process", "sell");
            }
            handleTries(tries, null);
          },
        });
      });
    } else {
      alert("You Must Add an Image.");
    }
  });

  // Handle the manual VIN input submission
  $("#manualInputDiv button.rdis-scanner-submit").on("click", function () {
    if ($("#manualInputDiv").data("process") == undefined) {
      $("#rdis-matching").hasClass("adding")
        ? $("#manualInputDiv").data("process", "add")
        : $("#manualInputDiv").data("process", "sell");

    }

    const manualIBtn = $("#manualInputDiv button.rdis-scanner-submit");

    var manualVin = $("#manualInputField").val();

    if ($("#manualInputDiv").data("process") == "add") {
      submitManualVin(manualVin, manualIBtn);

      let vehicleData = $("#manualInputDiv").data("vehicleData");

      vehicleData["VIN"] = manualVin; // Update the VIN in vehicleData
      console.log("manual data" + vehicleData);

      createInventoryPost(manualVin, vehicleData);

      $("#manualInputDiv").hide();
    }

    if ($("#manualInputDiv").data("process") == "sell") {
      var lastSixVin = $("#manualInputField").val();

      if (lastSixVin.length === 6) {
        manualIBtn.html(
          "Processing <span class='spinner-border spinner-border-sm text-secondary'></span>"
        );
        $.ajax({
          url: ajax_object.ajax_url,
          method: "POST",
          data: {
            action: "rdis_query_vin_last_six",
            last_six_vin: lastSixVin,
          },
          success: function (response) {
            if (response.success) {
              handlePotentialMatches(response.data);
              $("#manualInputDiv").removeClass("d-flex");
              $("#manualInputDiv").addClass("d-none");
              $("#manualInputDiv button.rdis-scanner-submit").html("Next →");
            } else {
              $("#manualInputDiv p")
                .text("No matches found for the provided VIN segment, please try again.");
              $("#manualInputDiv button.rdis-scanner-submit").html("Try Again");

            }
          },
          error: function (xhr, status, error) {
            console.error("Error querying VIN:", error);
            $("#rdis-ocr-results")
              .html("An error occurred while processing your request.")
              .show();
          },
        });
      } else {
        alert("Please enter the last 6 digits of the VIN.");
      }
    }
  });

  function handleTries(tries, response) {
    if (tries >= 2) {
      if ($("#manualInputDiv").data("process") == undefined) {
        $("#rdis-matching").hasClass("adding")
          ? $("#manualInputDiv").data("process", "add")
          : $("#manualInputDiv").data("process", "sell");
      }
      $("#rdis-matching button.rdis-scanner-submit").hide();
      $("#rdis-ocr-results")
        .html(
          "The software is having issues deciphering the characters on the image. Please input the VIN manually."
        )
        .show();
      $("#manualInputDiv").removeClass("d-none");
      $("#manualInputDiv").addClass("d-flex");
      if ($("#manualInputDiv").data("process") == "add") {
        $("#manualInputDiv p").html(
          "The " +
            response.data.SearchCriteria +
            " seems to be incorrect or incomplete. If this VIN does not look correct to you please input the vin manually so that you can get a more accurate read."
        );
      }
      if ($("#manualInputDiv").data("process") == "sell") {
        $("#manualInputDiv p").html(
          "Please enter the last 6 of the identification number to continue."
        );
      }
      tries = 0;
    }
  }
  function submitManualVin(manualVin, manualIBtn) {
    if (typeof $("#manualInputField").val() !== "string") {
      if ($("#manualInputDiv").data("process") !== undefined) {
        alert("VIN can not be empty");
      } else if (
        $("#manualInputDiv").data("process") == undefined &&
        $("#rdis-matching").hasClass("adding")
      ) {
        $("#manualInputDiv").data("process", "add");
      } else if (
        $("#manualInputDiv").data("process") == undefined &&
        $("#rdis-matching").hasClass("selling")
      ) {
        $("#manualInputDiv").data("process", "sell");
      }
    } else if ($("#rdis-matching").hasClass("adding")) {
      manualIBtn.html(
        "Processing <span class='spinner-border spinner-border-sm text-secondary'></span>"
      );
      $.ajax({
        url: ajax_object.ajax_url,
        method: "POST",
        data: {
          action: "rdis_handle_cropped_image",
          manual_vin: manualVin,
          nonce: ajax_object.nonce,
        },
        success: function (response) {
          if (response.success) {
            // Extract vehicle data from response
            window.handleAddResponse(response);
            // Additional code for success
          } else {
            console.error("Error:", response.data);
          }
        },
        error: function (error) {
          console.error("AJAX error:", error);
        },
      });
    }
  }
});
//end scanner cropper
