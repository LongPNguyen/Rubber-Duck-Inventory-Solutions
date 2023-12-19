jQuery(function ($) {
    // Handler for adding new feature names
    $("#add-feature-name").on("click", function () {
        var $templateRow = $(".template-feature-row").clone().removeClass('template-feature-row').removeAttr('style');
        var newIndex = $("#feature-name-fields p:not(.template-feature-row)").length - 2; // Exclude the template row
        $templateRow
            .find("input")
            .attr("name", "rd_inventory_feature_defaults[" + newIndex + "]");
        $templateRow.insertBefore($("#feature-name-fields p:last")); // Insert before the description paragraph
    });

    // Handler for removing feature names
    $("#feature-name-fields").on("click", ".remove-feature", function () {
        $(this).parent().remove();
        // Re-index the remaining rows, excluding the template and description paragraphs
        $("#feature-name-fields p:not(.template-feature-row, :last)").each(function (index) {
            $(this)
                .find("input")
                .attr("name", "rd_inventory_feature_defaults[" + index + "]");
        });
    });
});
