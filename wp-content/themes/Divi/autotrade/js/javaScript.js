function drag_and_drop_elements($, type, counter) {
    var $gallery = $(".gallery"),
        $trash = $(".trash"),
        droppableDiv;

    // Let the gallery items be draggable
    $("li", $(".gallery")).draggable({
        revert: "invalid", // when not dropped, the item will revert back to its initial position
        helper: "clone",
        cursor: "move",
    });

    // Let the trash be droppable, accepting the gallery items
    if (type == "default") {
        droppableDiv = $(".trash");
    } else {
        droppableDiv = $(".gallery_new" + counter).find(".trash");
    }

    $(".trash_dont_accept").droppable({
        drop: function (event, ui) {
            if (
                $(this).closest(".add_new_stage").prev().find("td ul li")
                    .length > 3
            ) {
                $("#addrow").trigger("click");
                var append_ = $("table.order-list tbody:visible");
                var cloning_data = $(
                    "table.order-list tbody:visible tr:nth-last-child(2) td"
                ).find("ul.trash");
                var element = ui.draggable
                    .clone()
                    .prependTo($(cloning_data))
                    .hide()
                    .fadeIn();
                var row = element.closest("td");

                var current_element_id = ui.draggable
                    .find("img")
                    .attr("data-elementid");

                var tooltip_content = element.find("span[data-template]");

                tooltip_content.find("[disabled]").removeAttr("disabled");
                tooltip_content.find("button").removeClass("d-none");

                var index_of_element = tooltip_content.attr("class").split("-");

                var tooltipClass =
                    row.attr("class") +
                    current_element_id +
                    "tooltip-content" +
                    index_of_element[1];

                tooltip_content.attr("class", tooltipClass);
                element.attr("data-tooltip-content", "#" + tooltipClass);

                // Configuration
                row.find(".configuration").insertBefore(row.find("li:first"));
                row.find(".door_image_li").insertAfter(row.find("li:last"));
                // row.find('.dashed_image_li').hide();
                setScrollAndIndex($);
                element.addClass("shadow");
                // dropping element from previous tr to the last tr
                element.removeAttr("style");

                //INIT ALBA 25/11 Parametro solo salida - Correcion cuando añade una row
                var tooltip_parameter = tooltip_content
                    .find("[class=tooltip_content_container]")
                    .find("[class=testing]");
                var close_tab = $("div")
                    .find(`[data-tab_id='10']`)
                    .attr("data-id-omc");
                var tab_panel = $(event.target)
                    .parents(":eq(4)")
                    .attr("data-id-omc");

                if (close_tab == tab_panel) {
                    console.log("Ejecucion");
                    element.find("div[data-only-exit]").each(function () {
                        //console.log($(this).find('[data-only-exit=1]').attr('data-only-exit'));

                        var only_exit = $(this).attr("data-only-exit");
                        if (only_exit == 1) {
                            $(this).attr("style", "display : show");
                        }
                    });
                }
                //END ALBA 25/11 Parametro solo salida - Correcion cuando añade una row

                // Guardar estrategia
                $("#save_data").trigger("click", ["auto-save"]);

                element.trigger("click");
            } else {
                alert(
                    "Please drop atleast one element in previous scenario..."
                );
                $(".right_side").css("z-index", "1");
            }

            var tr = getAncestorElement($(this), "tr");
            var index = tr.parent().children().index(tr);

            showHideDashedImage($("table.order-list tbody:visible"));

            // Change dashed image color
            $(this)
                .find(".dashed_image_li .dashed-div-2")
                .css("border", "3px dashed #d4d3d3");

            // hide dashed image when drop element

            $(this).find(".dashed_image_li").addClass("display_none");
            $(this)
                .find(".dashed_image_li .dashed-div")
                .css("border", "3px dashed #d4d3d3");
        },
        activate(event, ui) {
            if (!ui.draggable.hasClass("shadow")) {
                $("table.order-list tbody:visible ")
                    .find("ul.trash_dont_accept .dashed_image_li")
                    .removeClass("display_none");
            }
        },
        over(event, ui) {
            // removing left_tooltip
            $("div.tooltipster-base").remove();

            var dashed_img = $(this).find(".dashed_image_li .dashed-div-2");
            //
            $(dashed_img).css({
                border: "5px dashed #009681",
            });
        },
        out(event, ui) {
            var dashed_img = $(this).find(".dashed_image_li .dashed-div-2");
            $(dashed_img).css({
                border: "3px dashed #d4d3d3",
            });
        },
    });

    droppableDiv.droppable({
        accept: ".gallery > li",
        classes: {
            "ui-droppable-active": "ui-state-highlight",
        },

        drop: function (event, ui) {
            console.log("workingdrop");

            if ($.tooltipster.instances($(".build-next")).length > 0) {
                $(".build-next").tooltipster("destroy");
            }

            $(".build-next").removeClass("button_clicked");

            $(".delete_element_tab").trigger("click");
            // console.log($(this));

            var element = ui.draggable
                .clone()
                .appendTo($(this))
                .hide()
                .fadeIn();

            var row = element.closest("td");

            var current_element_id = ui.draggable
                .find("img")
                .attr("data-elementid");

            var tooltip_content = element.find("span[data-template]");

            //INIT ALBA 18/11 Parametro solo salida
            var tooltip_parameter = tooltip_content
                .find("[class=tooltip_content_container]")
                .find("[class=testing]");
            var close_tab = $("div")
                .find(`[data-tab_id='10']`)
                .attr("data-id-omc");
            var tab_panel = $(event.target)
                .parents(":eq(5)")
                .attr("data-id-omc");

            if (close_tab == tab_panel) {
                console.log("Ejecucion");
                element.find("div[data-only-exit]").each(function () {
                    //console.log($(this).find('[data-only-exit=1]').attr('data-only-exit'));
                    console.log("change view");
                    var only_exit = $(this).attr("data-only-exit");
                    if (only_exit == 1) {
                        console.log("changed view");

                        $(this).attr("style", "display : show");
                    }
                });
            }
            //END ALBA 18/11 Parametro solo salida

            tooltip_content.find("[disabled]").removeAttr("disabled");
            tooltip_content.find("button").removeClass("d-none");

            var index_of_element = tooltip_content.attr("class").split("-");

            var tooltipClass =
                row.attr("class") +
                current_element_id +
                "tooltip-content" +
                index_of_element[1];

            tooltip_content.attr("class", tooltipClass);
            element.attr("data-tooltip-content", "#" + tooltipClass);

            // Configuration
            if (row.find("li").length > 4) {
                $(
                    '<li class="d-sort sign fixHover" >  <img src="images/plus_sign.png">  </li>'
                ).insertBefore(row.find("li").eq(-1));
            }

            row.find(".dashed_image_li").insertAfter(row.find("li:last"));
            row.find(".door_image_li").insertAfter(row.find("li:last"));
            // row.find('.dashed_image_li').hide();

            setScrollAndIndex($);
            element.addClass("shadow");

            // Change dashed image color

            $("table.order-list tbody:visible ")
                .find("ul.trash_dont_accept .dashed_image_li .dashed-div")
                .css("border", "3px dashed #d4d3d3");
            element
                .next("li")
                .find(".dashed-div")
                .css("border", "3px dashed #d4d3d3");

            // hide dashed image when drop element
            $("table.order-list tbody:visible ")
                .find("ul.trash .dashed_image_li")
                .addClass("display_none");
            $("table.order-list tbody:visible ")
                .find("ul.trash_dont_accept .dashed_image_li")
                .addClass("display_none");

            // Guardar estrategia
            $("#save_data").trigger("click", ["auto-save"]);

            element.trigger("click");
        },
        activate(event, ui) {
            $("table.order-list tbody:visible ")
                .find("ul.trash .dashed_image_li")
                .removeClass("display_none");

            if ($(".tooltipster-base").is(":visible")) {
                $(".tooltipster-base").hide();
            }
            // $('.left_elements_tab').css({
            // 	visibility: 'hidden',
            // 	left: '84px'
            // });

            $(".left_elements_tab").addClass("element_visibility");

            setTimeout(function () {
                if (
                    !$(
                        "div.tooltipster-base .tooltipster-box .tooltipster-content span div.main_head"
                    )
                        .next("div")
                        .hasClass("design_1")
                ) {
                    $("div.tooltipster-base").remove();
                }
            }, 50);

            // console.log($(ui.helper[0]).attr('style'));

            $(ui.helper[0]).css({ visibility: "visible", "z-index": "1000" });
        },
        deactivate(event, ui) {
            // removing left_tooltip
            // ui.draggable.removeClass('tooltipstered');

            $(".left_elements_tab").removeClass("active");

            $(".add_elements_plus").removeClass("active");

            // $(".left_elements_tab").animate({
            // 	left: "-999px",
            // }, 'slow', function(){
            // 	console.log("deactivate");

            // });

            showHideDashedImage($("table.order-list tbody:visible"));
            $("table.order-list tbody:visible ")
                .find("ul.trash_dont_accept .dashed_image_li")
                .addClass("display_none");
        },
        over(event, ui) {
            // removing left_tooltip
            // $('div.tooltipster-base').remove();

            $(".trash.ui-droppable-hover")
                .find("li.dashed_image_li")
                .find(".dashed-div")
                .css("border", "5px dashed #009681");
        },
        out(event, ui) {
            var dashed_img = $("table.order-list tbody:visible").find(
                ".dashed_image_li .dashed-div"
            );
            $(dashed_img).css("border", "3px dashed #d4d3d3");
        },
    });
    counter++;

    // Let the gallery be droppable as well, accepting items from the trash
    // $(".gallery").droppable({
    // 	accept: ".trash li",
    // 	classes: {
    // 		"ui-droppable-active": "custom-state-active"
    // 	},

    // 	activate( event, ui ) {
    // 		// $('.add_elements_plus').css('z-index', '-100');
    // 		$('.delete-div > div').css('z-index', '100');
    // 	},

    // 	deactivate( event, ui ) {
    // 		// $('.add_elements_plus').css('z-index', '10');
    // 		$('.delete-div > div').css('z-index', '-999');
    // 	},

    // 	over( event, ui ) {
    // 		$('.delete-div > div').addClass('hover-del');
    // 	},

    // 	out( event, ui ) {
    // 		$('.delete-div > div').removeClass('hover-del');
    // 	},

    // 	drop: function(event, ui) {

    // 		console.log("workingdrag");

    // 		if($.tooltipster.instances($('.build-next')).length > 0){

    // 			$(".build-next").tooltipster('destroy');

    // 		}

    // 		$(".build-next").removeClass('button_clicked');

    // 		var current_row_tr = ui.draggable.closest('tr').find('.trash');

    // 		recycleImage(ui.draggable);

    // 		var prevEl = ui.draggable.prev();
    // 		var nextEl = ui.draggable.next().next();

    // 		if (prevEl.hasClass('sequence_li') && nextEl.hasClass('add_sequence')) {
    // 			nextEl.remove();
    // 			current_row_tr.find('.dashed_image_li').show();
    // 		}

    // 		if (nextEl.hasClass('add_sequence')) {
    // 			nextEl.remove();
    // 			current_row_tr.find('.dashed_image_li').show();
    // 		}

    // 		if ( current_row_tr.find('li').length == 4 && ui.draggable.closest('td').hasClass('gallery_new') == false ) {
    // 			current_row_tr.find('.close').trigger('click');
    // 		}

    // 		setTimeout( function() {
    // 			setScrollAndIndex($);
    // 		}, 450 );

    // 	}

    // });

    var recycle_icon =
        "<a href='link/to/recycle/script/when/we/have/js/off' title='Recycle this image' class='ui-icon ui-icon-refresh'>Recycle image</a>";

    // Image deletion function
    var trash_icon =
        "<a href='link/to/trash/script/when/we/have/js/off' title='Delete this image' class='ui-icon ui-icon-trash'>Delete image</a>";

    function recycleImage($item) {
        var seqAfter = $item.next().next();

        $item.fadeOut(function () {
            $item
                .find("a.ui-icon-refresh")
                .remove()
                .end()
                .css("width", "32%")
                .append(trash_icon)
                .find("img")
                .css("height", "90px")
                .end()
                .appendTo($(".gallery"))
                .remove();

            if (seqAfter.hasClass("sequence_li")) {
                seqAfter.remove();
            }
        });
    }

    $(".trash").sortable({
        items: "> li:not(.d-sort)",

        update: function (ev, ui) {
            $(".build-next").removeClass("button_clicked");

            add_arrow_element($);

            // Guardar estrategia
            $("#save_data").trigger("click", ["auto-save"]);
        },
    });
}

var counter = 0;

// add row dynamically
$(document).ready(function () {
    drag_and_drop_elements($, "default");

    $(document).on("click", "#addrow", function (e) {
        var li_length_for_addScenario = $(
            "table.order-list tbody:visible tr:nth-last-child(2) td .trash li"
        ).length;

        if (li_length_for_addScenario > 2) {
            var cols = "";

            var dashed_image_li_clone = $(
                "table.order-list tbody:visible tr:first-child"
            )
                .find(".dashed_image_li")
                .html();

            var append_ = $("table.order-list tbody:visible");

            var td_width = $("td.gallery_new").css("width");

            cols +=
                "<tr><td class='gallery_new" +
                counter +
                "' style='width: " +
                td_width +
                ";'><div class='ui-helper-reset gallery-rep'><ul id='trash' class='trash ui-widget-content ui-state-default' style='overflow-x: auto;'><li class='dashed_image_li d-sort'>" +
                dashed_image_li_clone +
                "</li></ul></div></td></tr>";
            var add_row_droppable = $(
                "table.order-list tbody:visible tr:last-child"
            );
            $(cols).appendTo(append_);
            $(add_row_droppable).appendTo(append_);

            set_default_lis($, $(".gallery_new" + counter));

            drag_and_drop_elements($, "append_row", counter);

            counter++;
        } else {
            alert("Please drop atleast one element...");
        }
    });

    // DELETE ROW
    $("table.order-list").on("click", ".close", function (event) {
        $(".build-next").removeClass("button_clicked");

        var $this = $(this);
        var nextRows = $this
            .closest("tr")
            .nextAll()
            .filter(":not(.add_new_stage)");
        nextRows.find("> th").remove();

        $this.closest("tr").fadeOut("fast", function () {
            $this.closest("tr").remove();
            //counter -= 1;
        });
        for (var i = 0; i < nextRows.length; i++) {
            addNumberOfRule($(nextRows[i]).find("> td"), nextRows[i].rowIndex);
        }

        // Guardar estrategia
        $("#save_data").trigger("click", ["auto-save"]);
    });

    $(document).on("click", ".arrow_pop", function (e) {
        e.stopPropagation();

        if ($.tooltipster.instances($(".build-next")).length > 0) {
            $(".build-next").tooltipster("destroy");
        }

        $(".build-next").removeClass("button_clicked");

        var $this = $(this);

        var current_li = $this.closest("li");

        var seq_arrow = $(".gallery").find('li[data-element-append="7"]');

        var seq_added = $(seq_arrow).clone().insertAfter(current_li).show();

        seq_added.find("i.fa-remove").addClass("delete_arrow");

        seq_added
            .removeClass("add_sequence door_image_li")
            .addClass("paramsmeters sequence_li disabled_arrow");

        seq_added.find("button").removeClass("d-none");

        seq_added.find("[disabled]").removeAttr("disabled");

        seq_added.removeClass("d-sort");

        current_li.next("li.sequence_li").trigger("click");

        seq_added.next("li").css({
            display: "inline-block",
        });

        add_arrow_element($);

        setScrollAndIndex($);
    });

    // HELP CODE
    $(".pop")
        .popover({ trigger: "manual", html: true, animation: false })
        .on("mouseenter", function () {
            var _this = this;
            $(this).popover("show");
            $(".popover").on("mouseleave", function () {
                $(_this).popover("hide");
            });
        })
        .on("mouseleave", function () {
            var _this = this;
            setTimeout(function () {
                if (!$(".popover:hover").length) {
                    $(_this).popover("hide");
                }
            }, 300);
        });

    var arrow_text = $(".gallery li[data-title='SEQ']").find("strong").html();

    $(document).on("mouseover", ".arrow_pop.pop", function (e) {
        $(this).popover("show");

        $(this).attr("data-content", arrow_text);
    });

    // Cancel Arrow
    $(document).on("mouseover", "li.sequence_li", function (event) {
        event.preventDefault();
        $(this).find("i").show();
    });

    $(document).on("mouseout", "li.sequence_li", function (event) {
        event.preventDefault();
        $(this).find("i").hide();
    });

    // Delete arrow
    $(document).on("click", ".delete_arrow", function (e) {
        e.stopPropagation();

        if ($.tooltipster.instances($(".build-next")).length > 0) {
            $(".build-next").tooltipster("destroy");
        }

        $(".build-next").removeClass("button_clicked");

        var current_li = $(this).closest("li");

        var prev_li = current_li.prev("li");

        // current_li.prev('li').find('img').removeClass('disabled_arrow');

        current_li.fadeOut("slow", function () {
            prev_li.find("img.arrow_pop").removeClass("disabled_arrow");

            current_li.remove();
        });

        // Dashed image
        var current_td = $(this).closest("td");

        var ul_length = current_td.find("ul.trash li").length;

        var next_row_elements = current_td
            .closest("tr")
            .next()
            .find("ul.trash li");

        if (ul_length == 3) {
            if (next_row_elements.length > 2) {
                $(current_td).closest("tr").remove();
            }
            if (
                current_td.closest("tr").prev("tr").find("ul.trash li")
                    .length >= 3
            ) {
                current_td.closest("tr").remove();
            } else {
                $(current_td)
                    .find("li.dashed_image_li")
                    .css("display", "inline-block");
            }
        }

        add_arrow_element($);
    });

    // DELETE ELEMENT

    $(document).on("click", ".delete_element", function (e) {
        $("table.order-list tbody:visible ")
            .find("ul.trash .dashed_image_li")
            .addClass("display_none");
        $("table.order-list tbody:visible ")
            .find("ul.trash_dont_accept .dashed_image_li")
            .addClass("display_none");
        $(".dashed_image_li  .dashed-div").css(
            "border",
            "3px dashed rgb(212, 211, 211)"
        );

        e.stopPropagation();

        $(".dashed_image_li  .dashed-div").css(
            "border",
            "3px dashed rgb(212, 211, 211)"
        );

        if ($.tooltipster.instances($(".build-next")).length > 0) {
            $(".build-next").tooltipster("destroy");
        }

        $(".build-next").removeClass("button_clicked");

        // Showing dashed_image after empty li
        var current_td = $(this).closest("td");

        var ulEle = current_td.find("ul.trash li");
        var ul_length = ulEle.length;

        var next_row_elements = current_td
            .closest("tr")
            .next()
            .find("ul.trash li");

        var $this = $(this);

        //INI Alba - 14/01/20 Eliminar los signos + a la vez que se eliminan los elementos
        if (ul_length > 4) {
            if ($this.parent("li").prev().hasClass("sign")) {
                $this
                    .parent("li")
                    .prev()
                    .fadeOut("slow", function () {
                        $this.parent("li").prev().remove();
                    });
            } else if (
                $this.parent("li").next().hasClass("sign") &&
                ($this.parent("li").prev().attr("data-element-append_conf") ==
                    "48" ||
                    $this
                        .parent("li")
                        .prev()
                        .attr("data-element-append_conf") == "46")
            ) {
                $this
                    .parent("li")
                    .next()
                    .fadeOut("slow", function () {
                        $this.parent("li").next().remove();
                    });
            }
        }
        //END Alba - 14/01/20

        $this.parent("li").fadeOut("slow", function () {
            $this.parent("li").remove();
            if (ul_length == 4) {
                var parent = $("table.order-list tbody:visible");
                var delete_icon = parent
                    .find(":first-child > th > i.delete-icon")
                    .clone(true, true);
                if (next_row_elements.length > 2) {
                    var nextRows = $(current_td)
                        .closest("tr")
                        .nextAll()
                        .filter(":not(.add_new_stage)");
                    $(current_td).closest("tr").remove();
                    showHideDashedImage(parent);
                    nextRows.find("> th").remove();
                    for (var i = 0; i < nextRows.length; i++) {
                        addNumberOfRule(
                            $(nextRows[i]).find("> td"),
                            nextRows[i].rowIndex + 1
                        );
                    }
                }
                if (
                    current_td.closest("tr").prev("tr").find("ul.trash li")
                        .length >= 3
                ) {
                    current_td.closest("tr").remove();
                    showHideDashedImage(parent);
                } else {
                    // $(current_td).find('li.dashed_image_li').css('display', 'inline-block');
                }
                var trList = parent.find("> tr.flex-column");
                if (trList.find("> th > i.delete-icon").length == 0) {
                    trList
                        .first()
                        .find("> th > p.rule-title")
                        .before(delete_icon);
                }
            }
            add_arrow_element($);
        });

        // Mostrar cuadrado 'Arrastrar aquí' cuando no hayan elementos en la fila
        var container = getAncestorElement($this, "ul");
        var tr = getAncestorElement(container, "tr");
        var index = tr.parent().children().index(tr);

        var li_list = container.find("li");
        if (li_list.length == 4 && index == 0) {
            var dashed_image_li = container.find("> li.dashed_image_li");
            setTimeout(function () {
                dashed_image_li.css("visibility", "hidden");
                dashed_image_li.removeClass("display_none");
                setTimeout(function () {
                    dashed_image_li.css("visibility", "visible");
                }, 250);
            }, 600);
        }

        // Guardar estrategia
        $("#save_data").trigger("click", ["auto-save"]);
    });
});

function add_arrow_element($) {
    $("#nav-tabContent2 .omc.tab-pane").each(function (index, tabs) {
        var $tabs = $(tabs);
        var RowData = "";

        $tabs.find(".trash").each(function (index, rows) {
            var $rows = $(rows);
            var elements = "";

            $rows.find("li").each(function (index, li) {
                var $li = $(li);
                var paramsData = "";

                if ($li.hasClass("sequence_li") == false) {
                    if ($li.next("li").hasClass("sequence_li") == false) {
                        $li.find("img.arrow_pop").removeClass("disabled_arrow");
                    } else {
                        $li.find("img.arrow_pop").addClass("disabled_arrow");
                    }
                }
            });
        });
    });
}

function areRowsEmpty(parent) {
    var empty = [];
    var trList = parent.find("> tr");
    trList.each(function (index) {
        var tr = $(this);
        if (!tr.hasClass("add_new_stage")) {
            var li_elements_length = tr.find("> td #trash > li").length;
            if (li_elements_length <= 3) {
                empty.push(true);
            } else {
                empty.push(false);
            }
        }
    });
    return empty;
}

function showHideDashedImage(parent) {
    var array = areRowsEmpty(parent);
    var list_dashed_image_li = parent.find("ul.trash .dashed_image_li");
    for (var i = 0; i < array.length; i++) {
        var empty = array[i];
        var dashed_image = $(list_dashed_image_li.get(i));
        if (!empty) {
            dashed_image.addClass("display_none");
        } else {
            if (dashed_image.hasClass("display_none")) {
                dashed_image.removeClass("display_none");
            }
        }
    }
}

function getAncestorElement(element, ancestorTagName) {
    var parent = element.parent();
    var parentTagName = parent.prop("tagName").toLowerCase();
    while (parentTagName != ancestorTagName) {
        var parent = element.parent();
        var parentTagName = parent.prop("tagName").toLowerCase();
        element = parent;
    }
    return parent;
}
//INI Alba 13/01/2020 Add plus sign between elements

function onLoad() {
    //Check File API support
    if (window.File && window.FileList) {
        var panel = document.getElementById("nav-tabContent2");
        var topRow = document.getElementsByClassName("top-row")[0];
        panel.addEventListener("mousedown", addPlusBetweenElements);
        changeTitle();
        topRow.addEventListener("click", checkPage);
    } else {
        console.log("Your browser does not support File API");
    }
}
//Adding Plus Sing when the previous strategy is loaded
function addPlusSignOnLoad() {
    var lists = document.getElementsByClassName("trash");
    Array.prototype.forEach.call(lists, function (list) {
        var panel =
            list.parentNode.parentNode.parentNode.parentNode.parentNode
                .parentNode;
        var panelName = panel.getAttribute("data-id-omc");
        if (panelName == "OPEN" || panelName == "CLOSE") {
            var elements = list.children;
            var eleListLen = elements.length;

            if (elements.length > 4) {
                for (var i = 1; i < elements.length - 2; i++) {
                    if (hasClass(elements[i], "sign") == false) {
                        if (
                            hasClass(
                                elements[i].nextSibling,
                                "dashed_image_li"
                            ) == false
                        ) {
                            $(
                                '<li class="d-sort sign fixHover" >  <img src="images/plus_sign.png"  > </li>'
                            ).insertAfter(elements[i]);
                        }
                        if (panelName != "CLOSE") {
                            var augmentWidth = list.offsetWidth + 100;
                            list.style.width = augmentWidth + "px";
                        } else {
                            var setWidth = eleListLen * 200;
                            list.style.width = setWidth + "px";
                        }
                    }
                }
            }
        }
    });
}

//Adding and removing pluses signs when the element is moved
function addPlusBetweenElements(event) {
    if (event.target.nodeName == "LI") {
        var elementClicked = event.target;
    } else {
        var elementClicked = event.target.parentElement;
    }
    if (elementClicked.parentNode.nodeName == "UL") {
        var drop_area = elementClicked.parentElement;
        listArray = Array.from(elementClicked.parentNode.children);
        listArrayLength = listArray.length;

        $(drop_area).mouseup(function () {
            setTimeout(function wait() {
                try {
                    for (var i = 1; i < listArray.length - 2; i++) {
                        listArray = Array.from(
                            elementClicked.parentNode.children
                        );

                        if (i == 1) {
                            if (hasClass(listArray[i], "sign")) {
                                listArray[i].remove();
                            }
                            if (hasClass(listArray[i + 1], "sign") == false) {
                                $(
                                    '<li class="d-sort sign fixHover" >  <img src="images/plus_sign.png"  > </li>'
                                ).insertAfter(listArray[i]);
                            }
                        } else if (hasClass(listArray[i], "sign")) {
                            if (hasClass(listArray[i].nextSibling, "sign")) {
                                listArray[i].nextSibling.remove();
                                i++;
                            }
                            if (
                                hasClass(listArray[i].previousSibling, "sign")
                            ) {
                                listArray[i].previousSibling.remove();
                                i++;
                            }
                        } else {
                            if (i != listArray.length - 3) {
                                if (
                                    hasClass(
                                        listArray[i].nextSibling,
                                        "sign"
                                    ) == false
                                ) {
                                    $(
                                        '<li class="d-sort sign fixHover" >  <img src="images/plus_sign.png"  > </li>'
                                    ).insertAfter(listArray[i]);
                                }
                            }
                        }
                    }
                    listArray = Array.from(elementClicked.parentNode.children);
                    if (hasClass($(drop_area).children("li").eq(2), "sign")) {
                        $(drop_area).children("li").eq(2).remove();
                    }

                    if ($(drop_area).children("li").eq(-3).hasClass("sign")) {
                        $(drop_area).children("li").eq(-3).remove();
                    }

                    $(drop_area)
                        .children("li")
                        .each(function (index) {
                            if (
                                $(this).hasClass("sign") == true &&
                                $(this).next().hasClass("sign") == true
                            ) {
                                $(this).remove();
                            }
                        });
                } catch (error) {
                    //console.log(error)
                }
            }, 10);
        });
        //var indexOfCurrentEl= Array.from(elementClicked.parentNode.children).indexOf(elementClicked);
        //if (hasClass(elementBefore,"sign") && event.target.nodeName!="I") {
        //elementBefore.remove();
        //setTimeout(createSign(drop_area), 1000);

        //}
        //if (indexOfCurrentEl==1 && hasClass(elementAfter,"sign" && event.target.nodeName!="I")){
        //elementAfter.remove();
        //setTimeout(createSign(drop_area), 1000);
        //}
        //var movedElId = $('[data-index="' + elementClicked.data('data-index') + '"]');
    }
}

function hasClass(element, className) {
    return (" " + element.className + " ").indexOf(" " + className + " ") > -1;
}
//Alba FIN
//INI Alba - 28/01/2020 - Change the title of the Elements in order to show their description
function changeTitle() {
    let listOfDesc = document.getElementById("descriptions_container").children;
    Array.prototype.forEach.call(listOfDesc, function (element) {
        let eleId = element.id;
        let titleText = element.getElementsByTagName("P")[0].textContent;
        let targetId = eleId.split("!")[0];
        document.getElementById(targetId).title = titleText;
    });
}
//END Alba - 28/01/2020
//INI Alba - 29/01/2020 - Added new button to show description of the actual page

function checkPage(event) {
    var elementClicked = event.target.parentNode;

    var currentPage = "";

    if (hasClass(elementClicked, "img-validate")) {
        currentPage = "validate";
    } else if (hasClass(elementClicked, "img-build")) {
        currentPage = "builder";
    } else if (hasClass(elementClicked, "img-download")) {
        currentPage = "download";
    }

    document
        .getElementById("desc_button")
        .setAttribute("tab-Info", currentPage);
}
//END Alba - 29/01/2020
