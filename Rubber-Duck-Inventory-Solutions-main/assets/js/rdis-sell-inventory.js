jQuery(function ($) {
  // Event handler for "Sell Item" button
  $("#scanner-sell-inventory").on("click", function () {
    $("#pick-scanner").hide();
    $("#rdis-matching").addClass("selling");
    $("#manualInputDiv")
      .data("process", "sell")
      .removeClass("d-none")
      .addClass("d-flex");
  });

  var today = new Date();
  var eighteenYearsAgo = new Date(
    today.getFullYear() - 18,
    today.getMonth(),
    today.getDate()
  );
  var maxDate = eighteenYearsAgo.toISOString().split("T")[0];
  $("#cust-dob").attr("max", maxDate);

  // Handle the response for the "Sell" action within the AJAX success function
  window.handleSellResponse = function (response) {
    if (response.success && response.data.current_status !== "draft") {
      handlePotentialMatches(response.data);
    } else {
      if ($("#manualInputDiv").data("process") == undefined) {
        $("#rdis-matching").hasClass("adding")
          ? $("#manualInputDiv").data("process", "add")
          : $("#manualInputDiv").data("process", "sell");
      }
      $("#rdis-matching button.rdis-scanner-submit").html("Try Again");
      $("#rdis-ocr-results")
        .html(
          "No more matches were found, try again OR enter the last 6 of the vin to continue."
        )
        .show();
      $("#manualInputDiv").removeClass("d-none");
      $("#manualInputDiv").addClass("d-flex");
    }
  };

  window.handlePotentialMatches = function (newMatches) {
    matches = newMatches;
    currentMatchIndex = 0;
    if (matches.length > 0) {
      presentMatch(matches[currentMatchIndex]);
    } else {
      $("#rdis-ocr-results").html("No matches found.");
    }
  };

  let postID = "";

  function presentMatch(match) {
    if (match.current_status == "draft") {
      $("#rdis-ocr-results")
        .html(
          "This inventory item has already been sold or is unavailable for sale. Please refresh the page to restart the process."
        )
        .show();
    } else {
      postID = match.post_id;
      // Hide the image cropping container and show the matched item container
      $("#image-cropping-container").hide();
      $("#rdis-matching button.rdis-scanner-submit").hide();
      $("#rdis-ocr-results").html("").hide();
      $("#rdis-matching").hide();

      $("#matched-item").show();
      $("#wpfooter").hide();

      // Display match details
      var detailsDiv = $("#lower-fold").empty();
      var headerDiv = $("#upper-fold").empty();

      headerDiv.append(
        $("<h3/>").html("Stock #" + match.id + " | " + match.name)
      );
      // Append the image
      headerDiv.append(
        $("<img/>", {
          src: match.first_image_url,
          alt: "First Image",
          class: "mb-2 img-fluid rounded", // img-fluid for responsive image
        })
      );

      // Create the table and append it to detailsDiv
      var table = $("<table/>", { class: "table rounded" });
      detailsDiv.append(table);

      // Function to add a row to the table
      function addRow(label, value) {
        var row = $("<tr/>").addClass(label);
        row.append($("<td/>").html(label));
        row.append($("<td/>").html(value));
        table.append(row);
        if (row.hasClass("Price")) {
          row.addClass("text-success");
        }
      }

      // Add rows for each piece of data
      // addRow("Stock #", match.id);
      // addRow("Post ID", match.post_id);
      addRow("Price", "$" + match.post_price);
      addRow("Vin", match.vin);
      // addRow("Name", match.name);
      // addRow("Receipt Date", match.receipt_date);
      // addRow("Age", match.age);
      addRow("Year", match.year);
      addRow("Make", match.make);
      addRow("Model", match.model);
      addRow("Body", match.body);
      addRow("Series", match.series);
      addRow("Odometer", match.odometer);
      addRow("Color", match.color);
      addRow("Interior", match.interior);
      addRow("Key #", match.key_number);
      addRow("Engine", match.engine);
      addRow("Transmission", match.transmission);
      addRow("Drive", match.drive);
      addRow("Type", match.type);
      addRow("Fuel", match.fuel);

      // Update Confirm and Deny button handlers
      $("#confirm-match")
        .off()
        .click(function () {
          confirmMatch(match);
        });

      $("#deny-match")
        .off()
        .click(function () {
          currentMatchIndex++;
          if (currentMatchIndex < matches.length) {
            presentMatch(matches[currentMatchIndex]);
            $("#rdis-ocr-results").html("");
          } else {
            if ($("#manualInputDiv").data("process") == undefined) {
              $("#rdis-matching").hasClass("adding")
                ? $("#manualInputDiv").data("process", "add")
                : $("#manualInputDiv").data("process", "sell");
            }
            $("#matched-item").hide();
            $("#rdis-ocr-results")
              .html(
                "No more matches available. Please input the last 6 of the VIN manaually to process matching again or start the process over."
              )
              .show();
            $("#manualInputDiv").removeClass("d-none");
            $("#manualInputDiv").addClass("d-flex");
          }
        });
    }
  }

  function confirmMatch(match) {
    // Hide matched item and show sale price div
    $("#matched-item").hide();
    $("#sale-price-div").show();
  }

  let sold_price = "";
  let qty = "";

  $("#sale-price-next").on("click", function () {
    $("#sale-price-next").html(
      "Processing <span class='spinner-border spinner-border-sm text-secondary'></span>"
    );
    sold_price = parseFloat($("#sale-price-input").val());
    qty = parseInt($("#sale-qty-input").val()) || 1; // Default to 1 if no input
    postID = parseInt(postID.match(/\d+/)[0]);

    if (sold_price > 0) {
      $("#sale-price-div").hide();
      $("#customer-info").removeClass("d-none");
    } else {
      alert("Please enter a valid price.");
    }
  });

  let dlphoto = "";
  let codlphoto = "";

  $(".drivers-license-photo").on("change", function (e) {
    var file = e.target.files[0];
    var reader = new FileReader();
    var role = $(this).data("role");
    console.log(role);

    reader.onload = function (event) {
      var img = new Image();
      img.onload = function () {
        var canvas = document.createElement("canvas");
        var ctx = canvas.getContext("2d");
        var width = 800;
        var scaleFactor = width / img.width;
        canvas.width = width;
        canvas.height = img.height * scaleFactor;

        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

        if (role == "buyer") {
          console.log(role);
          canvas.toBlob(
            function (blob) {
              dlphoto = blob;
              console.log("dlphoto blob");
              // Use dlphoto here or call a function that uses it
            },
            "image/jpeg",
            0.85
          );
        } else if (role == "cobuyer") {
          console.log(role);
          canvas.toBlob(
            function (blob) {
              codlphoto = blob;
              console.log("codlphoto blob");
              // Use codlphoto here or call a function that uses it
            },
            "image/jpeg",
            0.85
          );
        }
      };
      img.src = event.target.result; // Moved this line here
    };

    reader.readAsDataURL(file);
  });

  let coBuyerBool = 0;

  $("#co-buyer-bool").on("click", function () {
    let firstName = $("#co-cust-first");
    let lastName = $("#co-cust-last");
    let address = $("#co-cust-address");
    let city = $("#co-cust-city");
    let state = $("#co-cust-state");
    let zipcode = $("#co-cust-zipcode");
    let country = $("#co-cust-country");
    let dob = $("#co-cust-dob");
    let phone = $("#co-cust-phone");
    let email = $("#co-cust-email");
    let dl = $("#co-cust-dl");
    let dlphoto = $("#co-cust-dlp");

    if ($("#co-buyer-bool").is(":checked")) {
      coBuyerBool = 1;

      $("#co-buyer-info").removeClass("d-none");

      firstName.attr('required', true);
      lastName.attr('required', true);
      address.attr('required', true);
      city.attr('required', true);
      state.attr('required', true);
      zipcode.attr('required', true);
      country.attr('required', true);
      dob.attr('required', true);
      phone.attr('required', true);
      email.attr('required', true);
      dl.attr('required', true);
      dlphoto.attr('required', true);
    } else {
      coBuyerBool = 0;

      $("#co-buyer-info").addClass("d-none");

      firstName.attr('required', false);
      lastName.attr('required', false);
      address.attr('required', false);
      city.attr('required', false);
      state.attr('required', false);
      zipcode.attr('required', false);
      country.attr('required', false);
      dob.attr('required', false);
      phone.attr('required', false);
      email.attr('required', false);
      dl.attr('required', false);
      dlphoto.attr('required', false);
    }
  });

  $("#customer-info").on("submit", function (e) {
    e.preventDefault();

    let firstName = $("#cust-first").val();
    let lastName = $("#cust-last").val();
    let coFirstName = $("#co-cust-first").val();
    let coLastName = $("#co-cust-last").val();
    let middleName = $("#cust-middle").val();
    let address = $("#cust-address").val();
    let city = $("#cust-city").val();
    let state = $("#cust-state").val();
    let zipcode = $("#cust-zipcode").val();
    let country = $("#cust-country").val();
    let dob = $("#cust-dob").val();
    let phone = $("#cust-phone").val();
    let email = $("#cust-email").val();
    let dl = $("#cust-dl").val();

    var formData = new FormData(this);
    formData.append("action", "rdis_record_sale"); // Add the action to the form data
    formData.append(
      "cust-dlp",
      dlphoto,
      firstName + "_" + lastName + "_" + "drivers_license.jpg"
    );
    if(coBuyerBool == 1){
    formData.append(
      "co-cust-dlp",
      codlphoto,
      coFirstName + "_" + coLastName + "_" + "drivers_license.jpg"
    );
    }
    formData.append("nonce", ajax_object.nonce);
    formData.append("post_id", postID);
    formData.append("sold_price", sold_price);
    formData.append("quantity", qty);
    formData.append("co-buyer-bool", coBuyerBool);

    $.ajax({
      type: "POST",
      url: ajax_object.ajax_url, // Make sure this is the correct URL
      data: formData,
      processData: false, // Important for FormData
      contentType: false, // Important for FormData
      success: function (response) {
        console.log("Response from server: ", response);
        // Handle the response here
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("AJAX error: " + textStatus + " : " + errorThrown);
      },
    });

    console.log(postID, qty, sold_price);
    console.log(
      firstName,
      lastName,
      middleName,
      address,
      city,
      state,
      zipcode,
      country,
      dob,
      phone,
      email,
      dl,
      dlphoto
    );
  });
});

// $.ajax({
//   url: ajax_object.ajax_url,
//   method: "POST",
//   data: {
//     action: "rdis_record_sale",
//     nonce: ajax_object.nonce,
//     post_id: currentPostId, // Make sure this variable is set to the current post ID
//     sold_price: price,
//     quantity: qty,
//   },
//   success: function (response) {
//     if (response.success) {
//       // Handle successful sale record
//       $("#sale-price-next").html("Next →");
//       $("#sale-price-div").hide();
//       $("#rdis-ocr-results")
//         .append(
//           $("<p/>", { class: "lead" }).text(
//             "You've finished the sales process for this program, please continue to process the forms for customers manually."
//           )
//         )
//         .show();
//     } else {
//       console.error("Error recording sale:", response.data);
//     }
//   },
//   error: function (xhr, status, error) {
//     console.error("Error processing sale:", error);
//   },
// });
