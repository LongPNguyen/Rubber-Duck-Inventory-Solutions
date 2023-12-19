jQuery(function ($) {
  // Event handler for "Add Item" button
  $("#scanner-add-inventory").on("click", function () {
    $("#pick-scanner").hide();
    $("#rdis-matching").addClass("adding").show();
    $("#manualInputDiv").data("process", "add");
  });

  // Handle the response for the "Add" action within the AJAX success function
  window.handleAddResponse = function (response) {
    if (response.success) {
      let errorCodeItem = response.data.Results.find(
        (item) => item.Variable === "Error Code" && item.Value !== "0"
      );

      if (errorCodeItem) {
        $("#manualInputDiv p").html(
          "The " +
            response.data.SearchCriteria.replace("VIN:", "VIN: ") +
            " is invalid. Please type in VIN manually."
        );

        $("#rdis-matching .rdis-scanner-submit").hide();
        $("#manualInputDiv").removeClass("d-none");
        $("#manualInputDiv button.rdis-scanner-submit").html("Try Again");
        $("#manualInputDiv").addClass("d-flex");
        console.log($("#manualInputDiv").data("process"));
        console.log(
          $("#manualInputDiv").data("process") == undefined,
          $("#manualInputDiv").data("process") == "add",
          $("#manualInputDiv").data("process") == "sell"
        );
      } else {
        let vehicleData = extractVehicleData(response.data);
        let vinString = response.data.SearchCriteria;
        let vin = vinString.replace("VIN:", "");
        showVinConfirmation(vin, vehicleData);
      }
    } else {
      $("#manualInputDiv p").html(
        "The " +
          response.data.SearchCriteria +
          " seems to be incorrect or incomplete. If this VIN does not look correct to you please input the vin manually so that you can get a more accurate read."
      );
      $("#manualInputDiv").removeClass("d-none");
      $("#manualInputDiv").addClass("d-flex");
    }
  };

  function extractVehicleData(results) {
    var date = new Date(); // Your Date object

    var day = date.getDate();
    var month = date.getMonth() + 1; // getMonth() returns 0-11
    var year = date.getFullYear();

    // Add leading zero to month and day if they are less than 10
    month = month < 10 ? "0" + month : month;
    day = day < 10 ? "0" + day : day;

    var formattedDate = month + "/" + day + "/" + year;

    var Age =
      year - parseInt(findValueByVariable(results.Results, "Model Year"), 10);

    var Body =
      findValueByVariable(results.Results, "Body Class") +
      " " +
      findValueByVariable(results.Results, "Doors") +
      "-DR";

    var Series = findValueByVariable(results.Results, "Displacement (L)") + "L";

    var Engine =
      Series +
      " " +
      (findValueByVariable(results.Results, "Engine Number of Cylinders")
        ? "V" + findValueByVariable(results.Results, "Engine Number of Cylinders")
        : "") +
      (findValueByVariable(results.Results, "Valve Train Design")
        ? findValueByVariable(results.Results, "Valve Train Design")
        : "");

    var Fuel =
      findValueByVariable(results.Results, "Fuel Type - Primary") &&
      findValueByVariable(results.Results, "Fuel Type - Secondary") !== null
        ? "Hybrid"
        : findValueByVariable(results.Results, "Fuel Type - Primary");

    // Extracts vehicle data from the results
    let vehicleData = {
      Type: findValueByVariable(results.Results, "Vehicle Type"),
      "Receipt Date": formattedDate,
      Age: Age,
      VIN: results.SearchCriteria.replace("VIN:", ""),
      Year: findValueByVariable(results.Results, "Model Year"),
      Make: findValueByVariable(results.Results, "Make"),
      Model: findValueByVariable(results.Results, "Model"),
      Body: Body,
      Series: Series,
      Odometer: "",
      Color: "",
      Interior: "",
      "Key #": "",
      Engine: Engine,
      Transmission: findValueByVariable(results.Results, "Transmission Style"),
      Drive: findValueByVariable(results.Results, "Drive Type"),
      Fuel: Fuel,
      // ... include other attributes as needed ...
    };
    return vehicleData;
  }

  function showVinConfirmation(vin, vehicleData) {
    // Show the confirmation prompt with the OCR-read VIN

    if (confirm("Is this the correct VIN: " + vin + "?")) {
      createInventoryPost(vin, vehicleData);
    } else {
      $("#rdis-matching button.rdis-scanner-submit").hide();
      $("#manualInputDiv")
        .data("vehicleData", vehicleData)
        .removeClass("d-none")
        .addClass("d-flex")
        .find("p")
        .html(
          "Looks like the VIN:" +
            vin +
            " was incorrect. Please input your VIN manually to continue."
        );
    }
  }

  function createInventoryPost(vin, vehicleData) {
    let formattedFeatures = Object.keys(vehicleData).map((key) => {
      return { name: key, description: vehicleData[key] };
    });

    $.ajax({
      url: ajax_object.ajax_url,
      method: "POST",
      data: {
        action: "rdis_add_inventory",
        post_data: {
          title:
            vehicleData["Year"] +
            " " +
            vehicleData["Make"] +
            " " +
            vehicleData["Model"] +
            " - " +
            vin.slice(-6),
          additional_info: formattedFeatures,
        },
        nonce: ajax_object.nonce,
      },
      success: function (response) {
        if (response.success) {
          window.location.href =
            "/wp-admin/post.php?post=" + response.data.post_id + "&action=edit";
        } else {
          console.error("Error creating inventory post:", response);
        }
      },
      error: function (error) {
        console.error("Error creating inventory post:", error);
      },
    });
  }

  function findValueByVariable(results, variableName) {
    let item = results.find((item) => item.Variable === variableName);
    return item ? item.Value : "Not Found";
  }
});
