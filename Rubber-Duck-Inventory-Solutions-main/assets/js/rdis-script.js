jQuery(function ($) {
  $("#wpcontent").addClass("pl-0")
  //start repeater handler
  // $(document).on("click", "#add_row", function () {
  //   var repeaterRow = $(".template_row")
  //     .clone()
  //     .removeClass("template_row")
  //     .removeAttr("style");
  //   var rowCount = $(".repeater_row").length;

  //   repeaterRow.find("input, textarea").each(function () {
  //     var name = $(this).attr("name").replace("template", rowCount);
  //     $(this).attr("name", name).val("");
  //   });
  //   repeaterRow.insertAfter(".repeater_row:last");
  // });

  // $(document).on("click", ".remove_row", function () {
  //   $(this).closest(".repeater_row").remove();

  //   // Re-index the rows
  //   $(".repeater_row").each(function (rowIndex) {
  //     $(this)
  //       .find("input, textarea")
  //       .each(function () {
  //         var name = $(this).attr("name");
  //         name = name.replace(/\[\d+\]/, "[" + rowIndex + "]");
  //         $(this).attr("name", name);
  //       });
  //   });
  // });

  //end repeater handler

  //start gallery handler
  var mediaUploader;

  $("#add_gallery_image").click(function (e) {
    e.preventDefault();
    if (mediaUploader) {
      mediaUploader.open();
      return;
    }

    mediaUploader = wp.media({
      title: "Add Images to Gallery",
      button: {
        text: "Add to Gallery",
      },
      multiple: "add",
    });

    mediaUploader.on("select", function () {
      var selection = mediaUploader.state().get("selection");
      selection.map(function (attachment) {
        attachment = attachment.toJSON();
        $(".gallery-thumbnails").append(
          '<div class="gallery-thumbnail" data-attachment_id="' +
            attachment.id +
            '">' +
            '<img src="' +
            attachment.sizes.thumbnail.url +
            '" />' +
            '<button type="button" class="remove-image" data-attachment_id="' +
            attachment.id +
            '">Remove</button>' +
            "</div>"
        );
      });
      updateGalleryInput();
    });

    mediaUploader.open();
  });

  function updateGalleryInput() {
    var gallery_ids = [];
    $(".gallery-thumbnails .gallery-thumbnail").each(function () {
      var id = $(this).data("attachment_id");
      if (id) {
        gallery_ids.push(id);
      }
    });
    $("#rd_inventory_gallery").val(gallery_ids.join(","));
    console.log($("#rd_inventory_gallery").val());
  }

  // Make the gallery sortable
  $(".gallery-thumbnails").sortable({
    items: "div.gallery-thumbnail",
    cursor: "move",
    scrollSensitivity: 40,
    forcePlaceholderSize: true,
    forceHelperSize: false,
    helper: "clone",
    opacity: 0.65,
    placeholder: "sortable-placeholder",
    start: function (event, ui) {
      ui.item.css("background-color", "#f6f6f6");
    },
    stop: function (event, ui) {
      ui.item.removeAttr("style");
      updateGalleryInput();
    },
  });

  // Enable the user to remove an image from the gallery
  $("body").on("click", ".remove-image", function (e) {
    e.preventDefault();
    $(this).closest(".gallery-thumbnail").remove();
    updateGalleryInput();
  });

  // Update hidden input on sort stop
  $(".gallery-thumbnails").on("sortstop", function (event, ui) {
    updateGalleryInput();
  });
  //end gallery handler
});
