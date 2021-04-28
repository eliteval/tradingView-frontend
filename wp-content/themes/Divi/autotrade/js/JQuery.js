var ATF_compile_result;
var last_screen = 0;
/**
 * ID de la última estrategia guardada
 * @type {?number}
 * @global
 */
var sid;
$(function () {
  /*var snackbar = mdc.snackbar.MDCSnackbar.attachTo(
        document.querySelector(".mdc-snackbar")
    );*/
  $('[data-toggle="tooltip"]').tooltip();
  $(".lock-btn, .lock-overlay, .disabled-option, #download-premium").on(
    "click",
    function () {
      var numlock = 1;
      $.ajax({
        type: "POST",
        url: "index.php",
        data: {
          numlock: numlock,
        },
      });
    }
  );
  $(
    ".disabled_option, .ui-draggable, .ui-draggable-handle, .ui-draggable-disabled"
  ).on("click", function () {
    var numlock = 1;
    $.ajax({
      type: "POST",
      url: "index.php",
      data: {
        numlock: numlock,
      },
    });
  });
  setTdWidth($);
  allow_tab = true;
  let href = $("#right_compile > form > div:first > a").attr("href");
  $("#right_compile > form > a").attr({
    href: href,
    target: "_blank",
  });

  function updateValueOfLabel(object) {
    $(object)
      .find("a > label")
      .each(function () {
        var paramLabel = $(this).text();
        var cardHeader = $(this).closest(".card-header");
        if (cardHeader.next().find("select").length != 0) {
          var paramValue = cardHeader
            .next()
            .find("select > option[selected]")
            .html();
        } else if (cardHeader.next().find("input").attr("type") == "checkbox") {
          if (
            cardHeader
              .next()
              .find(".form-check-label")
              .prev()
              .prop("checked") == true
          ) {
            var paramValue = "✔";
          } else {
            var paramValue = "❌";
          }
        } else {
          var paramValue = cardHeader.next().find("input").val();
        }

        var paramLabelWValue = paramLabel.replace(
          /\[.*?\]/,
          "[" + paramValue + "]"
        );
        $(this).text(paramLabelWValue);
      });
  }
  const URL_user_logged = $('input[name="user_logged"]').val();
  const URL_login = $('input[name="my-account"]').val();
  $('input[name="user_logged"], input[name="my-account"]').remove();
  // Comprueba si el usuario está logeado cada 60s
  var loggedInterval = setInterval(function () {
    $.ajax({
      type: "POST",
      url: URL_user_logged,
    })
      .done(function (response) {
        if (response == "Unable to connect") {
          clearInterval(loggedInterval);
          window.onbeforeunload = null;
          window.top.location.href = URL_login;
        }
        response = JSON.parse(response);
        if (response.logged == false) {
          clearInterval(loggedInterval);
          window.onbeforeunload = null;
          window.top.location.href = response.location;
        }
      })
      .error(function (jqXHR, textStatus, errorThrown) {
        if (jqXHR.readyState == 4 && jqXHR.status == 404) {
          clearInterval(loggedInterval);
          window.onbeforeunload = null;
          window.top.location.href = URL_login;
        }
      });
  }, 600000);

  $("li.paramsmeters").hover(function () {
    $(".testing").hide();
    var $this = $(this);

    var tooltip_content = $this.find("span[data-template]");

    var existsContentDiv = tooltip_content.attr("class");

    $("#" + existsContentDiv).remove();
    var template = tooltip_content
      .clone()
      .appendTo(".tooltip_templates")
      .show();
    template.attr("id", template.attr("class"));

    if ($.tooltipster.instances($this).length == 0) {
      $this.tooltipster({
        contentCloning: true,
        trigger: "hover",
        theme: "tooltipster-light",
        delay: 200,
        interactive: true,

        functionReady: function (origin, tooltip) {
          $(".close_tooltip").on("click", function () {
            origin.hide();
          });

          $(".close_tooltip_save").on("click", function () {
            $this.find("[data-template]").remove();
            var reposDiv = $(tooltip.tooltip)
              .find("[data-template]")
              .clone()
              .appendTo($this)
              .removeAttr("id")
              .hide();
            origin.hide();
          });
        },

        functionBefore: function (instance, helper) {
          $.each($.tooltipster.instances(), function (i, instance) {
            instance.close();
          });
        },

        functionAfter: function (instance, helper) {
          $this.tooltipster("destroy");

          var body_cursor = $("body").css("cursor");

          if (
            body_cursor == "auto" &&
            !$(".left_elements_tab").is(":hover") &&
            !$(".add_elements_plus").is(":hover") &&
            !$(".tooltipster-base").is(":visible")
          ) {
            $(".left_elements_tab").removeClass("active");
            $(".add_elements_plus").removeClass("active");
          }
        },
      });

      $this.tooltipster("show");
      $("div.tooltipster-base").css("display", "none");

      setTimeout(function () {
        $("div.tooltipster-base").css("display", "block");
      }, 500);
    }
  });
  //INI ALBA - 29-01-2020 - Added new button to show description of the actual page
  $(document).on("click", "#desc_button", function () {
    var $this = $(this);
    var currentPage = "#" + $this.attr("tab-Info");
    var tooltip_content = $(currentPage + " >p").text();
    var text = tooltip_content.replace("!", "<br><br>");
    if (currentPage == "#builder") {
      var urlImg = "images/builder_info.gif";
    } else if (currentPage == "#validate") {
      var urlImg = "images/validate_info.gif";
    }

    //var template = tooltip_content.clone().appendTo('.tooltip_templates').show();
    //template.attr('id', template.attr('class'));
    var definition =
      `<div class="checking"
	style="position: relative; font-size: 13px;">
	<span class="close_tooltip" style='right: 33px !important;'><i class="fa fa-close"></i></span>
	<div style="height: 300px; width:300px; overflow-y: scroll">
	` +
      text +
      "<br><img class='info-Img' src='" +
      urlImg +
      "' >" +
      `</div></div>`;

    if ($.tooltipster.instances($this).length == 0) {
      $($this).tooltipster({
        trigger: "click",
        theme: "tooltipster-light",
        interactive: true,
        position: "top",
        fixedWidth: 450,
        contentAsHTML: "true",
        content: definition,

        functionReady: function (origin, tooltip) {
          $(".close_tooltip").on("click", function () {
            origin.hide();
          });
        },

        functionBefore: function (instance, helper) {
          $.each($.tooltipster.instances(), function (i, instance) {
            instance.close();
          });
        },

        functionAfter: function (instance, helper) {
          $this.tooltipster("destroy");
        },
      });
    }
    $this.tooltipster("show");
    $("div.tooltipster-base").css("display", "none");

    setTimeout(function () {
      $("div.tooltipster-base").css("display", "block");
    }, 500);
  });

  // INI - Alba 23/01/20 - Show tooltip on hover adjusted for li created dynamically (No hover)
  $(document).on(
    "mouseenter",
    ".trash li:not(.sign,.dashed_image_li)",
    function () {
      var $this = $(this);

      var description = $this.find(".el-desc").html();
      var title = $this.find("h6").text();
      var definition =
        `<div
		style="position: relative; font-size: 13px; width: 300px">
		<span class="close_tooltip" style='right: 33px !important;'><i class="fa fa-close"></i></span>
		<p><b>` +
        title +
        `</b></p><p>` +
        description +
        `</p>
		</div>`;

      if ($.tooltipster.instances($this).length == 0) {
        $this.tooltipster({
          contentCloning: true,
          trigger: "hover",
          theme: "tooltipster-light",
          delay: 200,
          interactive: true,
          contentAsHTML: "true",
          content: definition,

          functionReady: function (origin, tooltip) {
            $(".pop_image").hide();
            $(".close_tooltip").on("click", function () {
              origin.hide();
            });
          },
        });

        $this.tooltipster("show");
        $("div.tooltipster-base").css("display", "none");
        setTimeout(function () {
          $("div.tooltipster-base").css("display", "block");
        }, 500);
      }
    }
  );
  //FIN - Alba 23/01/20

  //INI - Alba 24/01/20 - Function modified to affect all the li (before li.paramsmeters)
  $(document).on("click", "li", function (e) {
    var $this = $(this);
    if ($.tooltipster.instances($this).length != 0) {
      $(this).tooltipster("destroy");
    }
    //FIN - Alba 24/01/20

    if ($this.hasClass("shadow")) {
      $(".box_shadow").show();

      $(this).find(".testing").show();
      $(this).find(".btn-success").show();
      $(this).find(".pop_image").show();
      $(this)
        .find(".accordion")
        .find(".collapse")
        .each(function () {
          $(this).hasClass("show");
          $(this).removeClass("show");
        });
      $(this).find(".card").first().find(".collapse").addClass("show");

      updateValueOfLabel($(this).find(".card"));
      var tooltip_content = $this.find("span[data-template]");

      var existsContentDiv = tooltip_content.attr("class");

      $("#" + existsContentDiv).remove();

      var template = tooltip_content
        .clone()
        .appendTo(".tooltip_templates")
        .show();
      template.attr("id", template.attr("class"));

      if ($.tooltipster.instances($this).length == 0) {
        $this.tooltipster({
          animation: "grow",
          theme: "tooltipster-light",
          interactive: true,
          onlyOne: true,
          trigger: "custome",
          arrow: false,
          // position: "bottom",

          functionReady: function (origin, tooltip) {
            //Alba INI - 06/02/2020 - Control de valores para inputs de tiempo
            $(".card-header").on("click", function () {
              var object = $(this);

              setTimeout(justShowOne(object), 500);
            });
            function justShowOne(object) {
              $(object).next().addClass("oneShowing");
              $(object)
                .find("a")
                .attr(
                  "rotate-icon",
                  $(object).find("a").attr("rotate-icon") === "true"
                    ? "false"
                    : "true"
                );
              $(object)
                .parent()
                .siblings()
                .find("a")
                .attr(
                  "rotate-icon",
                  $(object).find("a").attr("rotate-icon") === "true"
                    ? "false"
                    : "false"
                );
              $(object)
                .parent()
                .siblings()
                .find(".show:not(.oneShowing)")
                .removeClass("show");
              $(object).next().removeClass("oneShowing");
            }

            $('input[step="any"]')
              .keypress(function (event) {
                if (
                  (event.which != 46 ||
                    (event.which == 46 && $(this).val() == "") ||
                    $(this).val().indexOf(".") != -1) &&
                  (event.which < 48 || event.which > 57)
                ) {
                  event.preventDefault();
                }
              })
              .on("paste", function (event) {
                event.preventDefault();
              });
            $(".time-input")
              .keypress(function (event) {
                if (event.which < 48 || event.which > 57) {
                  event.preventDefault();
                }
              })
              .on("paste", function (event) {
                event.preventDefault();
              });

            var requiredText = ":";
            $(".time-input").on("input", function () {
              if (String($(this).val()).indexOf(requiredText) == -1) {
                $(this).val(requiredText);
              }
            });
            //Alba END - 06/02/2020
            $(".close_tooltip").on("click", function () {
              // var elementos = $(this).next();
              // // console.log(elementos.find(".collapse").find(".show"))
              // elementos.find(".card-body").each(function(){
              // 		//$(this).parent().parent().find("a").trigger( "click" );
              // 		//
              // 		if ($(this).parent().hasClass("show")) {
              // 			console.log($(this).parent());
              // 			$(this).parent().removeClass("show");
              // 			$(this).parent().prev().find("a").attr("aria-expanded","false");
              //
              // 		}
              // });

              setTimeout(function () {
                origin.hide();
                original_tooltipster_css($);
              }, 10);
            });

            $(".close_tooltip_save").on("click", function () {
              /*var elementos = $(this).parent().prev();
						elementos.find(".card-body").each(function(){
								$(this).parent().parent().find("a").trigger( "click" );
						});*/
              updateValueOfLabel($(this).parent().prev());

              var oldParams = $this.find("span > .design_1 > div.parameters");
              var newParams = $(tooltip.tooltip);
              newParams = newParams.find(
                ".tooltipster-content > span > .design_1 > div.parameters"
              );
              var equal = checkParams(oldParams, newParams);
              if (!equal) {
                // Guardar estrategia
                $("#save_data").trigger("click", ["auto-save"]);
              }

              $this.find("[data-template]").remove();
              var reposDiv = $(tooltip.tooltip)
                .find("[data-template]")
                .clone()
                .appendTo($this)
                .removeAttr("id")
                .hide();
              origin.hide();
            });

            var parameters_containers = $(tooltip.tooltip).find(
              "span > div.tooltip_content_container > div.testing.parameters"
            );
            relationalParameters(parameters_containers);
          },

          functionAfter: function (instance, helper) {
            $this.tooltipster("destroy");

            if (
              !$(".left_elements_tab").is(":hover") &&
              !$(".tooltipster-base").is(":visible")
            ) {
              if (!$(".left_elements_tab").is(":hover")) {
                $(".left_elements_tab").removeClass("active");
                $(".add_elements_plus").removeClass("active");
              }
            }
            // setTimeout(function(){
            // 	if(!($(".left_elements_tab").is(':hover')) && !($('.tooltipster-base').is(':visible'))){
            // 		$(".left_elements_tab").animate({
            // 				left: "-999px",
            // 			}, 'slow',  function(){
            // 			});
            // 	}
            // }, 100);
            original_tooltipster_css($);
          },
        });

        $this.tooltipster("show");
        // console.log($('div.tooltipster-base').next());

        var div_length = $("[id*=tooltipster-]")
          .find(".testing")
          .find("div.card:visible").length;
        if (div_length <= 5) {
          $("[id*=tooltipster-]").find(".testing").css({
            height: "auto",
            overflowY: "visible",
          });
        } else {
          $("[id*=tooltipster-]").find(".testing").css({
            height: "362px",
            overflowY: "scroll",
          });
        }

        $(".tooltipster-sidetip.tooltipster-light .tooltipster-box").css({
          borderRadius: "7%",
        });
        $(".pop_image").show();
        $(".tooltip_content_container").addClass("design_1");
        $(".tooltip_content_container").removeClass(
          "tooltip_content_container"
        );

        $(".tooltip_content_container").next("h6").addClass("element_head");
        $('<hr class="element_line_break">').insertAfter("p.el-desc");
        $(".testing").addClass("parameters");

        $(".parameters").next("div").find("button").addClass("element_btn");

        $(".design_1").find("h6").addClass("element_head");

        $("div.tooltipster-base").removeAttr("style");

        $("div.tooltipster-base").attr(
          "style",
          "  pointer-events: auto;z-index: 9999999;left: 33%;top: 15%;right: 0px;width: 450px;animation-duration: 350ms;transition-duration: 350ms;"
        );

        $(".tooltipster-sidetip .tooltipster-content").css({
          overflow: "unset",
          // padding: '18px'
          padding: "18px 0 0 0",
        });

        $(".close_tooltip").css({
          fontSize: "17px",
          top: "10px",
          right: "24px",
          color: "white",
        });

        $(".tooltipster-box .form-control").css("width", "auto");

        $(".main_head").css({
          textAlign: "center",
          color: "white",
        });

        $(".tooltipster-sidetip.tooltipster-light .tooltipster-box").css(
          "background",
          "#27a5df"
        );

        $(".get_design").addClass("element_form");

        var testing_div = $(".tooltipster-content")
          .find("span:first")
          .find("div:nth-child(2)")
          .find(".testing");
        var tooltip = $("div.tooltipster-base");

        if (tooltip.length == 2) {
          // $(tooltip[1]).tooltipster('close');
          // console.log("if");
          if ($(tooltip[1]).css("height") > "610px") {
            $(testing_div[1]).addClass("parameter_height");
          } else {
            $(testing_div[1]).removeClass("parameter_height");
          }
        } else {
          // console.log("else");
          // console.log($(tooltip).css('height'));
          if ($(tooltip).css("height") > "610px") {
            $(testing_div).addClass("parameter_height");
          } else {
            $(testing_div).removeClass("parameter_height");
          }
        }

        // Previous

        // if($('div.tooltipster-base').css('height') > '620px'){
        // 	$('.tooltipster-content').find('span:first').find('div:nth-child(2)').find('.testing').addClass('parameter_height');
        // }else{
        // 	$('.tooltipster-content').find('span:first').find('div:nth-child(2)').find('.testing').removeClass('parameter_height');
        // }
      }
    }
  });

  var pExceptions = $("#params_exceptions");
  const paramsExceptions = JSON.parse(pExceptions.text());
  pExceptions.remove();

  var pConvExcpTexts = $("#conv_exception_texts");
  const exceptionTexts = JSON.parse(pConvExcpTexts.text());
  pConvExcpTexts.remove();

  $(document).on(
    "input",
    'span[data-template] input[type="number"], input[step="any"]',
    function (e) {
      $(this).attr("value", $(this).val());
      if (
        $(this).attr("value") == "" ||
        (!$(this).hasClass("negative-per-default") && $(this).attr("value") < 0)
      ) {
        $(this).parent().parent().prev().addClass("empty-field-div");
        $(this).addClass("empty-field");
        if ($(this).attr("value") < 0) {
          $(this).next().text(exceptionTexts[0]);
        } else {
          $(this).next().text(exceptionTexts[1]);
        }
      } else {
        checkParamException($(this));
      }
      var card = $(this).closest(".card");
      updateValueOfLabel(card);
    }
  );

  $(document).on(
    "input",
    'span[data-template] div[data-field-type="string"] input:not(.time-input)',
    function (e) {
      var input = $(this);
      input.attr("value", input.val());
      checkParamException(input);
      var card = input.closest(".card");
      updateValueOfLabel(card);
    }
  );

  function checkParamException(input) {
    var divParams = input.closest("div.parameters");
    var paramCard = input.closest("div.card");
    var pid = paramCard.attr("pid");
    var exception = paramsExceptions.find((excp) => excp.pid == pid);
    if (exception !== undefined) {
      var type = paramCard.attr("data-field-type");
      if (exception.type == "PARAM2") {
        var input2 = divParams.find('div[pid="' + exception.val + '"] input');
        var exception2 = paramsExceptions.find(
          (excp) => excp.pid == exception.val
        );
        var value1 = parseNumber(input.val(), type);
        var value2 = parseNumber(input2.val(), type);
        numericException(value1, value2, input, exception);
      } else if (exception.type == "NUMBER") {
        var value1 = parseNumber(input.val(), type);
        var value2 = parseNumber(exception.val, type);
        numericException(value1, value2, input, exception);
      } else if (exception.type == "STRLEN") {
        var strLength = input.val().length;
        var maxLength = parseNumber(exception.val, "integer");
        numericException(strLength, maxLength, input, exception);
      }
    } else {
      clearError(input);
    }

    function parseNumber(value, type) {
      if (type == "integer") {
        var parsed = parseInt(value);
      } else if (type == "double") {
        var parsed = parseFloat(value);
      } else {
        return false;
      }
      return parsed;
    }

    function clearError(input) {
      input.removeClass("empty-field");
      input.parent().parent().prev().removeClass("empty-field-div");
      input.next().text("");
    }

    function showError(input, errorText) {
      var header = input.parent().parent().prev();
      header.addClass("empty-field-div");
      input.addClass("empty-field");
      input.next().text(errorText);
    }

    function numericException(val1, val2, input, exception) {
      var error = false;
      if (exception.cond == ">") {
        if (val1 > val2) {
          error = true;
        }
      } else if (exception.cond == ">=") {
        if (val1 >= val2) {
          error = true;
        }
      } else if (exception.cond == "<") {
        if (val1 < val2) {
          error = true;
        }
      } else if (exception.cond == "<=") {
        if (val1 <= val2) {
          error = true;
        }
      } else if (exception.cond == "==") {
        if (val1 == val2) {
          error = true;
        }
      } else if (exception.cond == "!=") {
        if (val1 != val2) {
          error = true;
        }
      }

      if (error) {
        showError(input, exception.txt);
        if (exception.type == "PARAM2") {
          showError(input2, exception2.txt);
        }
      } else {
        clearError(input);
        if (exception.type == "PARAM2") {
          clearError(input2);
        }
      }
    }
  }

  //Checking if time inputs are not in the 24:00 range
  function validateTimeRange(string) {
    var isValid;
    var first = string.substring(0, 1);
    var second = string.substring(1, 2);
    var fourth = string.substring(3, 4);
    if (first < 3) {
      if (first == 2 && second > 3) {
        isValid = false;
      } else {
        isValid = true;
      }
    } else {
      isValid = false;
    }
    if (fourth > 5) {
      isValid = false;
    }
    return isValid;
  }

  $(document).on("input", "input.time-input", function (e) {
    $(this).attr("value", $(this).val());
    if (
      $(this).attr("value") == "" ||
      this.value == ":" ||
      ($(this).hasClass("time-input") && this.value.length != 5)
    ) {
      $(this).next().text(exceptionTexts[2]);
      $(this).addClass("empty-field");
      $(this).parent().parent().prev().addClass("empty-field-div");
    } else if (
      $(this).hasClass("time-input") &&
      this.value.length == 5 &&
      this.value.substring(2, 3) != ":"
    ) {
      $(this).addClass("empty-field");
      $(this).parent().parent().prev().addClass("empty-field-div");
      $(this).next().text(exceptionTexts[2]);
    } else if (validateTimeRange(this.value) == false) {
      $(this).addClass("empty-field");
      $(this).parent().parent().prev().addClass("empty-field-div");
      $(this)
        .next()
        .text(exceptionTexts[2] + " (00:00 - 23:59)");
    } else {
      $(this).removeClass("empty-field");
      $(this).parent().parent().prev().removeClass("empty-field-div");
      $(this).next().text("");
    }
    var card = $(this).closest(".card");
    updateValueOfLabel(card);
  });

  $(document).on("input", "span[data-template] input", function (e) {
    var empty = 0;
    $(this)
      .closest(".testing")
      .children()
      .find("input")
      .each(function () {
        if ($(this).hasClass("empty-field")) {
          empty = empty + 1;
        }
      });
    if (empty != 0) {
      $(".close_tooltip_save").attr("disabled", true);
    } else {
      $(".close_tooltip_save").attr("disabled", false);
    }
    $(this.closest(".testing").previousElementSibling).click(function () {
      $(".close_tooltip_save").attr("disabled", false);
    });
  });

  $(document).on("change", "span[data-template] select", function (e) {
    var $this = $(this);
    $this.find("option:selected").attr("selected", true);
    $this.find("option:not(:selected)").removeAttr("selected");
    var card = $this.closest(".card");
    updateValueOfLabel(card);
  });

  $(document).on("click", ".form-check-label", function (e) {
    $(this).prev().trigger("click");
  });

  $(".collapse").on("shown.bs.collapse", function (e) {
    var $card = $(this).closest(".card");
    $(".accordion").animate(
      {
        scrollBottom: $card.offset().bottom,
      },
      500
    );
  });

  $(document).on(
    "change",
    'span[data-template] input[type="checkbox"]',
    function (e) {
      if (this.checked) {
        $(this).attr("checked", true);
      } else {
        $(this).removeAttr("checked", true);
      }
      var card = $(this).closest(".card");
      updateValueOfLabel(card);
    }
  );

  set_default_lis($);

  // Mover el cuadrado 'Arrastrar aquí' después del elemento Señal Inversa
  var ul = $("#CLOSE ul#trash");
  ul.find("> li.dashed_image_li").insertBefore(ul.find("li:last"));
  // Hacer visible el cuadrado 'Arrastrar aquí'
  var dashed_image_li = $("#OPEN #trash > li.dashed_image_li");
  dashed_image_li.removeClass("display_none");

  let values = $("#default_config_params");
  const default_config_params = JSON.parse(values.text());
  values.remove();

  // Función papelera
  $("i.delete-icon").click(function () {
    var tbody = getAncestorElement($(this), "tbody");
    var trList = tbody.find("> tr.flex-column:not(:first-child)");
    var elementsFirstRow = tbody.find(
      "> tr.flex-column:first-child ul#trash > li:not(:first-child, :last-child, .dashed_image_li)"
    );
    if (!(trList.length == 0 && elementsFirstRow.length == 0)) {
      trList.remove();
      elementsFirstRow.remove();

      let conf_elements = tbody.find("tr ul > li:not(.dashed_image_li)");
      conf_elements.each(function (i, elem) {
        let el = $(elem);
        let attrName = "data-element-append_conf";
        let id = el.attr(attrName);
        if (id == null) {
          attrName = "data-element-append";
          id = el.attr(attrName);
        }
        let params = default_config_params.find(
          (elem) => elem.element_id == id
        );
        setElementParameters($, el, params);
      });
      // Guardar estrategia
      $("#save_data").trigger("click", ["auto-save"]);
    }
    showHideDashedImage($("table.order-list tbody:visible"));
  });

  $("#modalSaveStrategy .modal-footer > .confirm-btn").on("click", function () {
    let modalBody = $(this).closest(".modal-content").find("> .modal-body");
    let name = modalBody.find("input").val();
    let asset = modalBody.find("#strat-asset").val();
    let period = {
      start_date: modalBody.find("#save-start-date").val(),
      end_date: modalBody.find("#save-end-date").val(),
    };
    let timeframe = modalBody.find("#strat-timeframe").val();
    let description = modalBody.find("textarea").val();
    // let image = modalBody.find("#strat_img_base64").attr("src");

    if (name != "" && description != "") {
      let strategy_data = generateStrategyString();
      let url = $(this).attr("data-action");
      let data = {
        name: name,
        description: description,
        // image: image,
        asset: asset,
        timeframe: timeframe,
        period: JSON.stringify(period),
        open: strategy_data.OPEN,
        close: strategy_data.CLOSE,
        date: moment().format("YYYY-MM-DD HH:mm:ss"),
      };
      $.ajax({
        url: url,
        type: "POST",
        data: data,
        dataType: "JSON",
      }).done(function (res) {
        //let sb = $(".mdc-snackbar");
        //let successIcon = sb.find('.material-icons[success="true"]');
        //let errorIcon = sb.find('.material-icons[success="false"]');
        if (res.success == false) {
          console.error(res);
          swal("Error", res.msg, "error");
          //successIcon.hide();
          //errorIcon.show();
        } else {
          if (sid == null && res.sid != null) {
            sid = res.sid;
          }
          $("#strategyName").text(name);
          swal("Good job!", res.msg, "success");
          // errorIcon.hide();
          // successIcon.show();
        }
        //snackbar.labelText = res.msg;
        //snackbar.open();
      });

      $("#modalSaveStrategy").modal("hide");
    } else {
      if (name == "") {
        modalBody.find("input").addClass("is-invalid");
      }
      if (description == "") {
        modalBody.find("textarea").addClass("is-invalid");
      }
    }
  });

  $("#modalSaveStrategy").on("hidden.bs.modal", function (e) {
    let inputs = $(this)
      .find(".modal-content > .modal-body")
      .find("input, textarea");
    inputs.removeClass("is-invalid");
  });

  $("#modalSaveStrategy .modal-body")
    .find("input, textarea")
    .on("input", function () {
      $(this).removeClass("is-invalid");
    });
  /**
   * Evento click botón guardar estrategia.
   *
   */
  $("#show_modalSaveStrategy").on("click", function () {
    if (sid != null) {
      $("#show_confirmSaveStrategy").trigger("click");
    } else {
      $("#modalSaveStrategy").modal("show");
    }
  });

  $("#modalConfirmSaveStrategy .modal-footer > .confirm-btn").click(
    function () {
      $("#modalConfirmSaveStrategy").modal("hide");
      let modalBody = $("#modalSaveStrategy .modal-body");
      modalBody.find("input, textarea").val("");
      modalBody.find("textarea").text("");
      sid = null;
      $("#modalSaveStrategy").modal("show");
    }
  );

  $("#validate-strategy").on("click", function () {
    $.ajax({
      url:
        "https://test.tradeasy.tech/wp-content/themes/Divi/autotrade/strategy_validation.php",
      type: "POST",
    }).done(function (response) {
      var response_data = response.trim();
      customAlert("Comprobador de estrategia", response_data);
    });
  });

  $("#modalConfirmSaveStrategy .modal-footer > .save-btn").click(function () {
    // AJAX for overwrite strategy
    let strategy_data = generateStrategyString();
    let url = $(this).attr("data-action");
    let data = {
      sid: sid,
      open: strategy_data.OPEN,
      close: strategy_data.CLOSE,
    };
    $.ajax({
      url: url,
      type: "POST",
      data: data,
      dataType: "JSON",
    }).done(function (res) {
      //let sb = $(".mdc-snackbar");
      //let successIcon = sb.find('.material-icons[success="true"]');
      //let errorIcon = sb.find('.material-icons[success="false"]');
      if (res.success == false) {
        swal("Error", res.msg, "error");
        //successIcon.hide();
        //errorIcon.show();
      } else {
        swal("Good job!", res.msg, "success");
        //errorIcon.hide();
        //successIcon.show();
      }
      //snackbar.labelText = res.msg;
      //snackbar.open();
    });
  });

  function updateCalendarEndDate(ticket) {
    let input = $('input[name="end-date"]');
    let name = input.attr("name");
    input.attr("value", ticket.attr(name));
    input.datepicker(
      "setStartDate",
      stringToDate(ticket.attr("start-date"), "/")
    );
    input.datepicker("setEndDate", stringToDate(ticket.attr("end-date"), "/"));
    input.attr("data-date-start-date", ticket.attr("start-date"));
    input.attr("data-date-end-date", ticket.attr("end-date"));
    input.datepicker("setDate", stringToDate(ticket.attr(name), "/"));
    input.val(ticket.attr(name));
  }

  function updateCalendarStartDate(ticket) {
    let input = $('input[name="start-date"]');
    let name = input.attr("name");
    input.datepicker("setEndDate", stringToDate(ticket.attr("end-date"), "/"));
    input.attr("data-date-start-date", ticket.attr("start-date"));
    input.attr("data-date-end-date", ticket.attr("end-date"));
  }

  $("#ticket").on("change", function () {
    var option = $(this).find("> option:selected");
    updateCalendarStartDate(option);
    updateCalendarEndDate(option);
  });

  receiveLastStrategy($);
  inactivityListener(7200);

  var cloned_addrow_button = $("#addrow").clone();
  cloned_addrow_button.attr("id", "addrule");
  cloned_addrow_button.addClass("btn");
  cloned_addrow_button.css({
    "max-height": "38px",
    "min-height": "38px",
    "max-width": "232px",
    "margin-left": "24px",
  });
  cloned_addrow_button.click(function () {
    var rowHasElements = [];
    $("div.active > table > tbody > tr").each(function (index) {
      var tr = $(this);
      if (!tr.hasClass("add_new_stage")) {
        tr.find("> td").each(function (index) {
          var td = $(this);
          var liList = td.find("> div > ul.trash > li");
          if (liList.length <= 3) {
            rowHasElements.push(false);
          } else {
            rowHasElements.push(true);
          }
        });
      }
    });
    var createRow = false;
    for (var i = 0; i < rowHasElements.length; i++) {
      if (!rowHasElements[i]) {
        createRow = false;
        break;
      } else {
        createRow = true;
      }
    }
    if (createRow) {
      $("#nav-tabContent2 > div.active #addrow").trigger("click");
      var newRow = $(
        "div.active > table.order-list > tbody > tr > td.gallery_new" +
          (counter - 1)
      );
      addNumberOfRule(newRow);
      // Move Configuration Elements
      newRow.find(".configuration").insertBefore(newRow.find("li:first"));
      newRow.find(".door_image_li").insertAfter(newRow.find("li:last"));
    } else {
      customAlert("Error", $("#errorsModal").attr("error-1"));
    }
  });
  $("tr.add_new_stage > td:nth-child(1)").append(cloned_addrow_button);

  var deleteContent = $(".delete-div").html();
  // var savingContent = '<div style="z-index: 100;"><p><strong>Saving...</strong><span class="saving"><i class="fa fa-spinner fa-spin" style="font-size:70px; color:#4b4d4e;"></i></span></p></div>';

  onLoad();

  // Cargar datos activos
  let div_data = $("#ticket").siblings(".d-none:eq(0)");
  let tickers = JSON.parse(div_data.text());
  $("#ticket > option").each(function () {
    let option = $(this);
    let ticker_data = tickers.find(
      (ticker) => ticker.ticker_id == option.val()
    );
    option.data("more_info", ticker_data);
  });
  div_data.remove();

  div_data = $("#ticker-year-data");
  var tickerYear = JSON.parse(div_data.text());
  div_data.remove();

  $(document).on("click", "#save_data", function (e, auto_save) {
    e.preventDefault();

    var $this = $(this);

    // $(".delete-div").html(savingContent);

    setTimeout(function () {
      var button = $this;

      button.find("img").show();
      button.find("span").text("saving");

      var strategy_data = generateStrategyString();

      var url = button.attr("data-action");

      $(".loader").show();

      $.ajax({
        url: url,
        type: "POST",
        data: strategy_data,
      }).done(function (response) {
        // console.log(response);
        button.find("img").hide();
        button.find("span").text("Saved");

        if (response.length > 20) {
          if (auto_save !== "auto-save") {
            var new_data = $("input.element_data_new").val();

            if (new_data == "") {
              $("input.element_data_new").val(response);
            } else {
              $("input.element_data_old").val(new_data);
              $("input.element_data_new").val(response);
            }
            $("div.img-validate, .build-next").addClass("scnd_step");
            $("div.img-validate img, .build-next, div.right-arrow img").css(
              "cursor",
              "pointer"
            );
            // $(".delete-div").html(deleteContent);
          }
        } else {
          // $("div.img-validate, .build-next").removeClass('scnd_step');
        }
      });
    }, 1000);

    if ($.tooltipster.instances($(".build-next")).length > 0) {
      $(".build-next").tooltipster("destroy");
    }

    var old_value = $("input.element_data_old").val();
    var new_value = $("input.element_data_new").val();
    if (old_value != new_value) {
      $(".build-next").removeClass("button_clicked");
    }
  });

  $("#reset_strategy").on("click", function () {
    console.log("Reset strategy");
    let url = $(this).attr("data-action");
    console.log(url);
    $.ajax({
      url: url,
      type: "POST",
    }).done(function (response) {
      location.reload();
    });
  });

  // Whizard OPEN

  var activeWhizard = $("div.top-row").find("div.active");

  activeWhizard.find("p").css("font-weight", "bold");

  $("div.img-validate, .build-next").addClass("scnd_step");
  $("div.img-download").addClass("thrd_step");

  // Build Whizerd
  $("div.img-build").on("click", function () {
    $("div.whizerd:eq(1)").find("p").css("font-weight", "bold");
    $("div.whizerd:eq(1)").addClass("active");
    $("div.whizerd:eq(1)").find("img").attr("src", "images/build-active.png");

    $("div.whizerd:eq(3)").removeClass("active");
    $("div.whizerd:eq(3)").find("img").attr("src", "images/validate.png");
    $("div.whizerd:eq(3)").find("p").css("font-weight", "normal");
    $("p.active-tab-right").html($("div.whizerd:eq(3)").find("p").html());

    $("div.whizerd:eq(5)").removeClass("active");
    $("div.whizerd:eq(5)").find("img").attr("src", "images/download.png");
    $("div.whizerd:eq(5)").find("p").css("font-weight", "normal");

    // Arrows
    $(".left-arrow").css("visibility", "hidden");
    $(".right-arrow").css("visibility", "visible");

    // Bullets
    $(".animate_bullets_left").animate(
      {
        backgroundPosition: "0",
      },
      200
    );
    $(".animate_bullets_right").animate(
      {
        backgroundPosition: "0",
      },
      200
    );

    $("div.build-tab")
      .css({
        display: "block",
        position: "relative",
      })
      .animate(
        {
          right: "0",
        },
        "slow"
      );

    $("div.validate-tab").css({
      display: "none",
      position: "absolute",
      left: "9999px",
      right: "0px",
    });

    $("div.download-tab").css({
      display: "none",
      position: "absolute",
      left: "9999px",
    });
    $(".system_defination_btn").show(1);
  });

  $(document).on("click", ".scnd_step", function () {
    $("#close_tooltipseter_").prop("disabled", true); // Deshabilitar boton de generar estrategia.
    if (!checkStrategy()) {
      $("#close_tooltipseter_").prop("disabled", false); // Habilita boton si la estrategia es la misma que habia antes
      return;
    }
    $this = $(this);
    var activeClass;

    if ($("div.whizerd:eq(1)").hasClass("active")) {
      activeClass = 1;
      last_screen = 1;
      // Precompilación
      let url = $("input[name='session_compiled']").val();
      $.ajax({
        url: url,
        type: "POST",
        data: { tipo: "V" },
      }).done(function (res) {
        //console.log(res);
      });
    } else {
      activeClass = 5;
      last_screen = 3;
    }

    if ($("input.validate_visisted").val() != "visited") {
      prev_value = $("input.element_data_old").val();
      next_value = $("input.element_data_new").val();
    }

    $("#save_data").trigger("click");

    $("input.element_data_old").val(prev_value);
    $("input.element_data_new").val(next_value);

    var ajaxIndex = 0;
    var requestcount = 0;

    $(document).ajaxComplete(function (event, xhr, settings) {
      if (
        settings.url.includes("user_logged") ||
        settings.url.includes("session_compiled") ||
        settings.url.includes("link_status")
      ) {
        return;
      }

      var error_txt = $("input.error_code_data").attr("data-error");

      var error_0 =
        error_txt + " " + $("input.error_code_data").attr("data-error-0");

      ajaxIndex++;
      requestcount++;

      if (ajaxIndex == 1) {
        // console.log(xhr.responseText.length);
        if (
          xhr.responseText.length > 200 ||
          settings.url.includes("system_defination")
        ) {
          if (validate_strategy($) == true) {
            // Display validation screen
            if (!$this.hasClass("build-next")) {
              $("#close_tooltipseter_").prop("disabled", false);
              $("input.validate_visisted").val("visited");
              $("inupt.validate_visisted").val("visited");
              $(".summary-btn, div.img-download").addClass("thrd_step");

              $("p.active-tab-left").html(
                $("div.whizerd:eq(1)").find("p").html()
              );
              $("div.whizerd:eq(1)").find("p").css("font-weight", "normal");
              $("div.whizerd:eq(1)").removeClass("active");
              $("div.whizerd:eq(1)")
                .find("img")
                .attr("src", "images/build.png");

              $("div.whizerd:eq(3)").addClass("active");
              $("div.whizerd:eq(3)")
                .find("img")
                .attr("src", "images/validate-active.png");
              $("div.whizerd:eq(3)").find("p").css("font-weight", "bold");

              $("div.whizerd:eq(5)").removeClass("active");
              $("div.whizerd:eq(5)")
                .find("img")
                .attr("src", "images/download.png");
              $("div.whizerd:eq(5)").find("p").css("font-weight", "normal");
              $("p.active-tab-right").html(
                $("div.whizerd:eq(5)").find("p").html()
              );

              // Arrows
              $(".left-arrow").css("visibility", "visible");
              $(".right-arrow").css("visibility", "visible");

              $("div.build-tab").css({
                display: "none",
                position: "absolute",
                right: "9999px",
              });

              if (activeClass == 1) {
                // Bullets
                $(".animate_bullets_left").animate(
                  {
                    backgroundPosition: "128px",
                  },
                  200
                );
                // Tab
                $("div.validate-tab")
                  .css({
                    display: "block",
                    position: "relative",
                  })
                  .animate(
                    {
                      left: "0",
                    },
                    "slow"
                  );
              } else {
                $(".animate_bullets_right").animate(
                  {
                    backgroundPosition: "0px",
                  },
                  200
                );
                $("div.validate-tab")
                  .css({
                    display: "block",
                    position: "relative",
                  })
                  .animate(
                    {
                      right: "0",
                    },
                    "slow"
                  );
              }

              $("div.download-tab")
                .css({
                  display: "none",
                  position: "absolute",
                })
                .animate(
                  {
                    left: "9999px",
                  },
                  "slow"
                );
              // $(".build-next").removeClass('button_clicked');
            } else {
              var old_value = $("input.element_data_old").val();
              var new_value = $("input.element_data_new").val();
              if (old_value == new_value) {
                var old_defintion = $(".system-defination").html();

                if ($.tooltipster.instances($(".build-next")).length > 0) {
                  $(".build-next").tooltipster("destroy");
                }
                var definition =
                  `<div class="checking"
									style="position: relative; font-size: 13px;">
									<span class="close_tooltip" style='right: 33px !important;'><i class="fa fa-close"></i></span>
									<div style="height: 450px; overflow-y: scroll">
									` +
                  old_defintion +
                  `</div></div>`;

                $("span.append_response").html(definition);

                $(".build-next").tooltipster({
                  contentCloning: true,
                  trigger: "click",
                  theme: "tooltipster-light",
                  interactive: true,
                  position: "top",
                  size: {
                    height: 450,
                    width: 450,
                  },
                  functionReady: function (origin, tooltip) {
                    $(".close_tooltip").on("click", function () {
                      origin.hide();
                    });
                  },
                });
                $(".build-next").tooltipster("show");
                $("div.tooltipster-base").css("width", "400px");
                $("#close_tooltipseter_").prop("disabled", false);
              } else {
                $("input.validate_visisted").val("");
                if ($.tooltipster.instances($(".build-next")).length > 0) {
                  $(".build-next").tooltipster("destroy");
                }
                $this.addClass("button_clicked");
                $(".system-defination").html(
                  '<div style="position: relative;top: 50%;"><img src="images/ajax-loader-green.gif" style="margin-top: -2%;"></div>'
                );

                $this.attr("disabled", true);
                var session_id = $("input[name=session_strategy_id]").val();
                var url = $this.attr("data-action");

                $.ajax({
                  url: url,
                  type: "POST",
                  data: {
                    insert_data: "insertion",
                    session_id: session_id,
                  },
                }).done(function (response) {
                  var btn_text = $(".system_defination_btn")
                    .find("div button.build-next span")
                    .html();
                  var refresh_icon =
                    btn_text +
                    '<i class="fa fa-refresh fa-spin refresh_definition" style="font-size:18px; margin-left: 10px;"></i>';
                  $(".system_defination_btn")
                    .find("div button.build-next span")
                    .html(refresh_icon);

                  var definition_id = response.trim();
                  var close_interval;
                  close_interval = setInterval(function () {
                    $.ajax({
                      url: url,
                      type: "POST",
                      data: {
                        insert_data: "",
                        strategy_definition_id: definition_id,
                      },
                    })
                      .done(function (response) {
                        if (response.trim().length > 8) {
                          $("i.refresh_definition").hide();

                          if (
                            $.tooltipster.instances($(".build-next")).length > 0
                          ) {
                            $(".build-next").tooltipster("destroy");
                          }

                          var definition =
                            `<div style="position: relative; font-size:13px;">
													<span class="close_tooltip" style='right: 33px !important;'><i class="fa fa-close"></i></span>
													<div style="height: 450px; overflow-y: scroll">
													` +
                            response.trim() +
                            `</div></div>`;
                          $("span.append_response").html(definition);

                          $(".build-next").tooltipster({
                            contentCloning: true,
                            trigger: "click",
                            theme: "tooltipster-light",
                            interactive: true,
                            position: "top",
                            functionReady: function (origin, tooltip) {
                              $(".close_tooltip").on("click", function () {
                                origin.hide();
                              });
                            },
                          });

                          $(".build-next").tooltipster("show");
                          $("div.tooltipster-base").css("width", "400px");

                          clearInterval(close_interval);
                          $(".build-next").attr("disabled", false);

                          $(".system-defination").html(response.trim()).show();
                          $("#close_tooltipseter_").prop("disabled", false);
                        } else {
                          timeOut_seconds =
                            parseInt($("#timeOut_seconds").val()) / 3;
                          if (requestcount > timeOut_seconds) {
                            clearInterval(close_interval);
                            $(".payment_loader").hide();
                            $("#server_not_responding").trigger("click");
                            $this.find("span").html(btn_text);
                          }
                        }
                      })
                      .fail(function () {
                        timeOut_seconds =
                          parseInt($("#timeOut_seconds").val()) / 3;
                        if (requestcount > timeOut_seconds) {
                          clearInterval(close_interval);
                          $(".payment_loader").hide();
                          $("#server_not_responding").trigger("click");
                          $this.find("span").html(btn_text);
                        }
                      });
                  }, 3000);
                  // }, 1000);
                });
              }
            }
          }
        } else {
          if (settings.url.includes("save_data")) {
            customAlert("Error", error_0);
          }
        }
      }
    });
    // console.log("validate");
    setTimeout(function () {
      $(".system_defination_btn").show();
    }, 450);
  });

  // Download Whizerd

  $(document).on("click", ".thrd_step", function () {
    if (!checkStrategy()) return;
    var ajaxIndex = 0;
    var counttCheck = 0;

    if ($("input.validate_visisted").val() != "visited") {
      prev_value = $("input.element_data_old").val();
      next_value = $("input.element_data_new").val();
    }

    $("#save_data").trigger("click");

    $("input.element_data_old").val(prev_value);
    $("input.element_data_new").val(next_value);

    if ($("div.whizerd:eq(1)").hasClass("active")) {
      last_screen = 1;
      // Precompilación Descarga
      let url = $("input[name='session_compiled']").val();
      $.ajax({
        url: url,
        type: "POST",
        data: { tipo: "D" },
      }).done(function (res) {
        //console.log(res);
      });
    } else {
      last_screen = 2;
    }

    $(document).ajaxComplete(function (event, xhr, settings) {
      if (
        settings.url.includes("user_logged") ||
        settings.url.includes("session_compiled") ||
        settings.url.includes("link_status")
      ) {
        return;
      }

      ajaxIndex++;
      counttCheck++;

      if (ajaxIndex == 1) {
        if (
          xhr.responseText.length > 200 ||
          settings.url.includes("system_defination")
        ) {
          // Desactivar check Descarga
          $("#right_download").removeAttr("style");
          $('input[name="read_accept"]').removeAttr("checked");
          $('input[name="read_accept"]').prop("checked", false);

          if (validate_strategy($) == true) {
            $(".system_defination_btn").hide();
            $("input.validate_visisted").val("");

            $("p.active-tab-left").html(
              $("div.whizerd:eq(3)").find("p").html()
            );
            $("div.whizerd:eq(1)").removeClass("active");
            $("div.whizerd:eq(1)").find("p").css("font-weight", "normal");

            $("div.whizerd:eq(3)").removeClass("active");
            $("div.whizerd:eq(3)")
              .find("img")
              .attr("src", "images/newValidate.png");
            $("div.whizerd:eq(3)").find("p").css("font-weight", "normal");

            $("div.whizerd:eq(5)").addClass("active");
            $("div.whizerd:eq(5)")
              .find("img")
              .attr("src", "images/download-active.png");
            $("div.whizerd:eq(5)").find("p").css("font-weight", "bold");

            // Arrows
            $(".right-arrow").css("visibility", "hidden");
            $(".left-arrow").css("visibility", "visible");

            $("div.build-tab").css({
              display: "none",
              position: "absolute",
              right: "9999px",
            });
            $("div.validate-tab").css({
              display: "none",
              position: "absolute",
              right: "9999px",
              left: "unset",
            });
            $(".animate_bullets_right").animate(
              {
                backgroundPosition: "128px",
              },
              200
            );
            $(".animate_bullets_left").animate(
              {
                backgroundPosition: "128px",
              },
              200
            );

            $("div.download-tab")
              .css({
                display: "block",
                position: "relative",
              })
              .animate(
                {
                  left: "0",
                },
                "slow"
              );

            var old_value = $("input.element_data_old").val();
            var new_value = $("input.element_data_new").val();
            //if (old_value != new_value) {
            if (!$("button.build-next").hasClass("button_clicked")) {
              $("#right_compile").removeAttr("style");
              $('input[name="read_accept"]').attr("disabled", true);
              $(
                ".left-arrow, .img-build, .img-validate, .right-arrow"
              ).addClass("disabled_events");
              $(".payment_loader").show();

              $(".system-defination").html(
                '<div style="position: relative;top: 50%;"><img src="images/ajax-loader-green.gif" style="margin-top: -2%;"></div>'
              );
              $(".build-next").addClass("button_clicked");

              $(".build-next").attr("disabled", true);

              var session_id = $("input[name=session_strategy_id]").val();
              var url = $(".build-next").attr("data-action");
              $.ajax({
                url: url,
                type: "POST",
                data: {
                  insert_data: "insertion",
                  session_id: session_id,
                },
              }).done(function (response) {
                var definition_id = response.trim();
                var close_interval;
                close_interval = setInterval(function () {
                  $.ajax({
                    url: url,
                    type: "POST",
                    data: {
                      insert_data: "",
                      strategy_definition_id: definition_id,
                    },
                  })
                    .done(function (response) {
                      response = response.trim();
                      if (response.length > 8) {
                        if (
                          $.tooltipster.instances($(".build-next")).length > 0
                        ) {
                          $(".build-next").tooltipster("destroy");
                        }
                        var definition =
                          '<div style="width: 320px; position: relative; font-size:13px;"><span class="close_tooltip"><i class="fa fa-close"></i></span>' +
                          response +
                          "</div>";
                        $("span.append_response").html(definition);

                        $(".build-next").tooltipster({
                          contentCloning: true,
                          trigger: "click",
                          theme: "tooltipster-light",
                          position: "top",
                          interactive: true,
                          functionReady: function (origin, tooltip) {
                            $(".close_tooltip").on("click", function () {
                              origin.hide();
                            });
                          },
                        });

                        $(".build-next").attr("disabled", false);
                        $(".system-defination").html(response).show();
                        $(".build-next").addClass("button_clicked");
                        $(".payment_loader").hide();
                        $("#right_compile").css({
                          opacity: 1,
                        });
                        $("#right_compile, .download_pay").css(
                          "pointer-events",
                          "unset"
                        );
                        $('input[name="read_accept"]').attr("disabled", false);
                        $(
                          ".left-arrow, .img-build, .img-validate, .right-arrow"
                        ).removeClass("disabled_events");

                        //$('#server_not_responding').trigger('click');
                        clearInterval(close_interval);
                      } else {
                        timeOut_seconds =
                          parseInt($("#timeOut_seconds").val()) / 3;
                        if (counttCheck > timeOut_seconds) {
                          // if (counttCheck > 1) {
                          clearInterval(close_interval);
                          console.log("Server not responding in 1");
                          $(".payment_loader").hide();
                          $("#right_compile, #right_download").removeAttr(
                            "style"
                          );
                          $("#right_compile, #right_download").css({
                            opacity: "1",
                            "pointer-events": "unset",
                          });
                          $(".right-options2 img").attr("disabled", false);
                          $(".system-defination").html("");
                          $("#server_not_responding").trigger("click");
                        }
                      }
                    })
                    .fail(function () {
                      timeOut_seconds =
                        parseInt($("#timeOut_seconds").val()) / 3;
                      if (counttCheck > timeOut_seconds) {
                        console.log("Server not responding Fail");
                        // if (counttCheck > 1) {
                        clearInterval(close_interval);
                        $(".payment_loader").hide();
                        $("#right_compile, #right_download").css({
                          opacity: "1",
                          "pointer-events": "unset",
                        });
                        $(".system-defination").html("");
                        $(".right-options2 img").attr("disabled", false);
                        $("#server_not_responding").trigger("click");
                      }
                    });

                  // }, 1000);
                }, 3000);
              });
            }
            //}
          }
        } else {
          if (settings.url.includes("save_data")) {
            var error_txt = $("input.error_code_data").attr("data-error");
            var error_0 =
              error_txt + " " + $("input.error_code_data").attr("data-error-0");
            customAlert("Error", error_0);
          }
        }
      }
    });
  });

  // Previous Arrow
  $("div.left-arrow").on("click", function () {
    var active_div = $(this).nextUntil("div.active");
    var current_tab = active_div.prev().hasClass("img-validate");

    if (current_tab) {
      $("div.img-validate").trigger("click");
    } else {
      $("div.img-build").trigger("click");
    }
  });

  // Continue Arrow
  $("div.right-arrow").on("click", function () {
    var build_li_length = $("ul#trash li").length;

    var active_div = $(this).prevUntil("div.active");
    var current_tab = active_div.prev().hasClass("img-build");

    if (current_tab) {
      $("div.img-validate").trigger("click");
    } else {
      $("div.img-download").trigger("click");
    }
  });

  var waiting_text = $(".validatingGraph div > p").text();

  $("#cancel-validattion").on("click", function () {
    var url = $(this).attr("data-action");
    var session_validation_id = $(this).attr("data-id");

    $.ajax({
      url: url,
      type: "POST",
      data: { session_validation_id: session_validation_id },
    }).done(function () {
      $(".left-arrow, .img-build, .img-download, .right-arrow").removeClass(
        "disabled_events"
      );

      $("#chartContainer").html("");
      $(".validatingGraph div").html("<p>" + waiting_text + "</p>");
      $(".validatingScreen, #cancel-validattion, #infoOperations").hide();
      $(".validatingGraph, .validateScreen").show();
      $("#infoOperations table > tbody").children().remove();
      $(".validate-tab .toggle-buttons").addClass("d-none");

      $("#validate-next").removeAttr("disabled");
      $("#validate-next").css("cursor", "pointer");

      var calendar = $("form.validate-form .calendar");
      var inputs = calendar.find("> input");
      inputs.attr("disabled", false);
      inputs.css("cursor", "inherit");
      var icons = calendar.find("> i.fa-calendar");
      icons.attr("disabled", false);
      icons.css("cursor", "pointer");

      clearInterval(intervalFunction);
    });
  });

  let div_texts = $("#infoOperations > div.tp-texts");
  var pointToolTipTexts = JSON.parse(div_texts.text());
  div_texts.remove();

  var chart = null;
  var n = 0;
  $("#validate-next").on("click", function () {
    var text = $(".validatingGraph").html();
    var countt = 0;
    const validation_data = $(".validate-form").serializeArray();
    console.log(validation_data);
    // $("#tradingView-content").load(`https://tradingview.tradeasy.tech/${encodeURIComponent(validation_data[0].value)}`+
    // `/${encodeURIComponent(validation_data[1].value)}/${encodeURIComponent(validation_data[2].value)}/${encodeURIComponent(validation_data[3].value)}/${encodeURIComponent(validation_data[4].value)}/${encodeURIComponent(validation_data[5].value)}/${encodeURIComponent(validation_data[6].value)}/${encodeURIComponent(validation_data[7].value)}`);
    //Variable que sirve para saber si ya hemos detectado datos para no seguir contando
    var alreadyIn = false;

    $("#chartContainer, .validateScreen, #infoOperations").hide();
    $(".validatingGraph")
      .html(
        '<div style="position: relative;top: 50%;"><img src="images/ajax-loader-green.gif" style="margin-top: -2%;"><span>Data Insertion...</span></div>'
      )
      .show();
    $("#tradingView").hide();
    $(".validatedScreen").hide();
    $(".validatingScreen").show();
    $("#infoOperations table > tbody").children().remove();
    $(".validate-tab .toggle-buttons").addClass("d-none");
    let graphCheckBox = $('.toggle-buttons > label:first input[name="toggle"]');
    graphCheckBox.prop("checked", true);
    let spanGraph = graphCheckBox.siblings("span.btn");
    let spanDetail = graphCheckBox.parent().siblings().find("span.btn");
    spanGraph.addClass("btn-outline-primary");
    spanGraph.removeClass("btn-outline-secondary");
    spanDetail.addClass("btn-outline-secondary");
    spanDetail.removeClass("btn-outline-primary");

    $(".left-arrow, .img-build, .img-download, .right-arrow").addClass(
      "disabled_events"
    );

    intervalFunction = null;
    if (last_screen == 1) {
      var done = false;
      new Promise(function (resolve, reject) {
        var functionCount = 0;
        let url = $("input[name='check_link_status']").val();
        var close_interval = setInterval(function () {
          $.ajax({
            url: url,
            type: "POST",
          })
            .done(function (res) {
              var response_data = res.trim();
              if (response_data != "") {
                if (response_data.includes("complie_error")) {
                  ATF_compile_result = "ERROR";
                  var error = response_data.split("complie_error")[0];
                  reject(Error(error));
                } else {
                  ATF_compile_result = response_data;
                  ATF_compile_result_tipo = "V";
                  resolve(response_data);
                }
                clearInterval(close_interval);
              } else {
                let timeOut_seconds = parseInt($("#timeOut_seconds").val()) / 3;
                if (functionCount > timeOut_seconds) {
                  console.log("Server not responding");
                  clearInterval(close_interval);
                  reject(Error("not responding"));
                }
              }
            })
            .fail(function () {
              console.log("fail");
              let timeOut_seconds = parseInt($("#timeOut_seconds").val()) / 3;
              if (functionCount > timeOut_seconds) {
                clearInterval(close_interval);
                reject(Error("not responding"));
              }
            });
        }, 3000);
        functionCount++;
      }).then(
        function () {
          if (
            ATF_compile_result != null &&
            ATF_compile_result != "ERROR" &&
            done == false
          ) {
            done = true;
            startChart();
          }
        },
        function (err) {
          if (err == "not responding" && done == false) {
            done = true;
            $("#server_not_responding").trigger("click");
          } else if (err != "not responding" && done == false) {
            done = true;
            // Mostrar alerta de error
            customAlert("Error", err);
          }
          $(".validatingGraph").html(text);
          $(".validatingScreen").hide();
          $(".validatingGraph, .validateScreen").show();
          $("#validate-next").attr("disabled", false);
          $("#validate-next").css("cursor", "pointer");
          $(".left-arrow, .img-build, .img-download, .right-arrow").removeClass(
            "disabled_events"
          );
        }
      );
    } else {
      startChart();
    }

    function startChart() {
      let selected = $("#validation_range > option:selected");
      var validation_data = $(".validate-form").serializeArray();
      var url = $("#validate-next").attr("data-action");
      console.log(url);
      $.ajax({
        url: url,
        type: "POST",
        data: validation_data,
      }).done(function (response) {
        $("#cancel-validattion")
          .attr("data-id", response.trim())
          .css("display", "block");
        $("#validate-next").attr("disabled", true);
        $("#validate-next").css("cursor", "not-allowed");

        if (response.trim() == "no permitido") {
          window.onbeforeunload = null;
          location.assign("/premium");
        }

        var url = $("input[name='siteLink']").val();
        var validation_id = response.trim();
        var dps = []; // dataPoints
        var y_axis_points = []; // dataPoints
        chart = new CanvasJS.Chart("chartContainer", {
          toolTip: {
            contentFormatter: function (e) {
              var content = "";
              for (var i = 0; i < e.entries.length; i++) {
                let { num, date, hour, profit, y } = e.entries[i].dataPoint;
                if (num != 0) {
                  content +=
                    '<span class="datapoint-name">' +
                    pointToolTipTexts.num +
                    ":</span> " +
                    num +
                    '<br/><span class="datapoint-name">' +
                    pointToolTipTexts.date +
                    ":</span> " +
                    date +
                    '</br><span class="datapoint-name">' +
                    pointToolTipTexts.hour +
                    ":</span> " +
                    hour +
                    '</br><span class="datapoint-name">' +
                    pointToolTipTexts.profit +
                    ":</span> " +
                    profit +
                    '</br><span class="datapoint-name">' +
                    pointToolTipTexts.balance +
                    ":</span> " +
                    y;
                } else {
                  content +=
                    '<span class="datapoint-name">' +
                    pointToolTipTexts.num +
                    ":</span> " +
                    num +
                    '<br/><span class="datapoint-name">' +
                    pointToolTipTexts.balance +
                    ":</span> " +
                    y;
                }
              }
              return content;
            },
          },
          axisY: {},
          axisX: {
            minimum: 0,
            interval: 50,
          },
          data: [
            {
              type: "line",
              color: "#2a4b9d", //**Change the color here
              lineColor: "#2a4b9d", //**Change the color here
              dataPoints: dps,
            },
          ],
          backgroundColor: "#ecf9ff",
        });

        // var count = 0;
        let keys = [];
        $("#infoOperations table > thead th[name]").each(function () {
          let name = $(this).attr("name");
          keys.push(name);
        });
        let showChart = true;
        let typeToTradingView;
        if (document.location.hostname == "test.tradeasy.tech") {
            typeToTradingView='test';
          var url =
            "https://test.tradeasy.tech/wp-content/themes/Divi/autotrade/validation_points.php?lang=" +
            lang;
        } else if (document.location.hostname == "tradeasy.tech") {
            typeToTradingView='prod';
          var url =
            "https://tradeasy.tech/wp-content/themes/Divi/autotrade/validation_points.php?lang" +
            lang;
        } else {
          console.log("No se reconoce el host:" + document.location.hostname);
        }

        var updateInterval = 3000;
        var updateChart = function (validateId, currency) {
          timeOut_seconds = parseInt($("#timeOut_seconds").val()) / 3;

          if (countt > timeOut_seconds) {
            clearInterval(intervalFunction);
            $("#cancel-validattion").trigger("click");
            $("#server_not_responding").trigger("click");
            $(".validateScreen").show();
          }
          $.ajax({
            url: url,
            type: "POST",
            data: {
              sesion_val_id: validateId,
              session_val_currency: currency,
            },
          }).done(function (response) {
            console.log("Recibido punto de validación!");
            var responseData = jQuery.parseJSON(response.trim());
            var y_axix = responseData.y_axix;
            n = 0;

            if (responseData.status == "N") {
              $(".validatingGraph").html(
                '<div style="position: relative;top: 50%;"><img src="images/ajax-loader-green.gif" style="margin-top: -2%;"><span>Loading Data...</span></div>'
              );
              countt++;
            } else if (responseData.status == "P") {
              if (y_axix.length > 0) {
                if (showChart) {
                  $(".validatingGraph").hide();
                  $("#chartContainer").show();
                  $(".validate-tab .toggle-buttons").removeClass("d-none");
                  showChart = false;
                }

                n = 0;
                alreadyIn = true;
                // count++;
                // if(count == 1){
                // 	dps.push({y: '', x: 0});
                // }

                for (var i = 0; i < y_axix.length; i++) {
                  let dataPoint = {
                    y: parseFloat(y_axix[i]),
                    num: y_axis_points.length,
                  };
                  let { operationsDetail } = responseData;
                  if (operationsDetail[i].tipoOP != -1) {
                    let row = $("<tr></tr>");
                    $("#infoOperations table > tbody").append(row);
                    row.append("<td>" + y_axis_points.length + "</td>");
                    for (let key of keys) {
                      row.append("<td>" + operationsDetail[i][key] + "</td>");
                    }
                    let splittedDate = operationsDetail[i].fechaFin.split(" ");
                    dataPoint.date = splittedDate[0];
                    dataPoint.hour = splittedDate[1];
                    dataPoint.profit = operationsDetail[i].OrderProf;
                  }

                  dps.push(dataPoint);

                  y_axis_points.push(parseFloat(y_axix[i]));
                }
                // console.log(dps);

                var x_interval = y_axis_points.length / 4;
                chart.options.axisY.minimum = Math.min.apply(
                  Math,
                  y_axis_points
                );
                chart.options.axisX.interval = Math.round(x_interval);
                chart.render();
              } else if (alreadyIn == false) {
                console.log("Estoy contando una vez empezado");
                countt++;
              }
            } else if (responseData.status == "F") {
              if (y_axix != "") {
                // JFS - 01/07/2020 - bug no se muestra la ultima operacion
                //if (y_axis_points.length < responseData.validation_points && y_axix.length > 0) {
                if (y_axix.length > 0) {
                  for (var i = 0; i < y_axix.length; i++) {
                    let dataPoint = {
                      y: parseFloat(y_axix[i]),
                      num: y_axis_points.length,
                    };
                    let { operationsDetail } = responseData;
                    if (operationsDetail[i].tipoOP != -1) {
                      let row = $("<tr></tr>");
                      $("#infoOperations table > tbody").append(row);
                      row.append("<td>" + y_axis_points.length + "</td>");
                      for (let key of keys) {
                        row.append("<td>" + operationsDetail[i][key] + "</td>");
                      }
                      let splittedDate = operationsDetail[i].fechaFin.split(
                        " "
                      );
                      dataPoint.date = splittedDate[0];
                      dataPoint.hour = splittedDate[1];
                      dataPoint.profit = operationsDetail[i].OrderProf;
                    }

                    dps.push(dataPoint);

                    y_axis_points.push(parseFloat(y_axix[i]));
                  }

                  var x_interval = y_axis_points.length / 4;
                  chart.options.axisY.minimum = Math.min.apply(
                    Math,
                    y_axis_points
                  );
                  chart.options.axisX.interval = Math.round(x_interval);
                  chart.render();
                }
                responseData.y_axix = "";
              }

              var report = responseData.report;
              // console.log(report);
              if (responseData.y_axix == "") {
                $(".validatingGraph").html(text);
              }
              if (responseData.report != null) {
                var resultReport = "<p style='margin-top: 25px;'>";
                var arrReport = report.split(";");
                $.each(arrReport, function (index, value) {
                  resultReport += value + "<br>";
                });
                resultReport += "</p>";
              } else {
                resultReport =
                  "<div style='position: relative;top: 50%;'><p style='margin-left: 3%;'>No record found...</p></div>";
                countt++;
              }

              $(".validatingScreen").hide();

              $(".validatedScreen").html(resultReport).show();

              $("#validate-next").attr("disabled", false);
              $("#validate-next").css("cursor", "pointer");

              $(
                ".left-arrow, .img-build, .img-download, .right-arrow"
              ).removeClass("disabled_events");

              clearInterval(intervalFunction);
            } else if (responseData.status == "E") {
              var report = responseData.report;
              clearInterval(intervalFunction);
              // Ocultar Validating Screen
              $(".validatingGraph").html(text);
              $(".validatingScreen").hide();
              $(".validatingGraph, .validateScreen").show();
              $("#validate-next").attr("disabled", false);
              $("#validate-next").css("cursor", "pointer");
              $(
                ".left-arrow, .img-build, .img-download, .right-arrow"
              ).removeClass("disabled_events");

              // Mostrar alerta
              customAlert("default", report);
            }
          });
        };

        updateChart(validation_id, validation_data[1].value);
        $("#tradingView-content").attr(
          "src",
          `https://tradingview.tradeasy.tech/${encodeURIComponent(
            validation_data[0].value
          )}` +
            `/${encodeURIComponent(
              validation_data[1].value
            )}/${encodeURIComponent(
              validation_data[2].value
            )}/${encodeURIComponent(
              validation_data[3].value
            )}/${encodeURIComponent(
              validation_data[4].value
            )}/${encodeURIComponent(
              validation_data[5].value
            )}/${encodeURIComponent(
              validation_data[6].value
            )}/${encodeURIComponent(validation_data[7].value)}/${parseInt(
              $("#timeOut_seconds").val()
            )}/${validation_id}/${typeToTradingView}/${lang}`
        );

        intervalFunction = setInterval(function () {
          updateChart(validation_id, validation_data[1].value);
        }, updateInterval);
      });
    }
  });

  $("#more-info").click(function () {
    let selected = $("#ticket > option:selected");
    let ticker_name = selected.text();
    let title = $("#modalTicker .outer-title > p");
    title.text(title.attr("prefix") + " " + ticker_name);
    let fields = $(
      '#modalTicker .modal-body ul > li > span.more_info_value[name!="quality"]'
    );
    fields.each(function () {
      let field = $(this);
      let name = field.attr("name");
      if (name == "start_date" || name == "end_date") {
        if (name == "start_date") {
          var data =
            "01/01/" +
            $(
              "form.validate-form > div.d-flex > div.form-group.option-2 div.calendar > input[name='start-date']"
            )
              .val()
              .split("/")[2];
        } else {
          var data = $("#ticket > option:selected").attr("end-date");
        }
      } else {
        if (name == "balance") {
          var data =
            $("#balance").val() + " " + $("select[name=currency]").val();
        } else {
          var data = selected.data("more_info")[name];
        }
      }
      field.text(" " + data);
    });
    let id = parseInt(selected.val());
    let timeframe = parseInt($("#timeframe > option:selected").val());
    let year = parseInt(
      $(
        "form.validate-form > div.d-flex > div.form-group.option-2 div.calendar > input[name='start-date']"
      )
        .val()
        .split("/")[2]
    );
    let tickerYearData = getTickerYearData(id, timeframe, year);
    $("#num-ticks").text(tickerYearData.ticks);
    $("#num-candles").text(tickerYearData.candles);
    $("#show_modalTicker").trigger("click");

    function getTickerYearData(id, timeframe, year) {
      let found = tickerYear.find(
        (ticker) => ticker.ticker_id == id && ticker.year == year
      );
      let data = { ticks: found.ticks, candles: 0 };
      switch (timeframe) {
        case 1:
          data.candles = found.candles_M1;
          break;

        case 2:
          data.candles = found.candles_M5;
          break;

        case 3:
          data.candles = found.candles_M15;
          break;

        case 4:
          data.candles = found.candles_M30;
          break;

        case 5:
          data.candles = found.candles_H1;
          break;

        case 6:
          data.candles = found.candles_H4;
          break;

        case 7:
          data.candles = found.candles_D1;
          break;

        default:
          break;
      }
      // Máscara
      data.ticks = data.ticks.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1.");
      data.candles = data.candles.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1.");
      return data;
    }
  });

  $('.toggle-buttons > label input[name="toggle"]').change(function () {
    let span = $(this).siblings("span.btn");
    let label = span.closest("label");
    let otherSpan = label.siblings().find("span.btn");
    if ($(this).is(":checked")) {
      span.addClass("btn-outline-primary");
      span.removeClass("btn-outline-secondary");
      otherSpan.addClass("btn-outline-secondary");
      otherSpan.removeClass("btn-outline-primary");
    } else {
      span.addClass("btn-outline-secondary");
      span.removeClass("btn-outline-primary");
      otherSpan.addClass("btn-outline-primary");
      otherSpan.removeClass("btn-outline-secondary");
    }

    if (label.is(".toggle-buttons > label:eq(0)")) {
      $("#infoOperations").hide();
      $("#tradingView").hide();
      $("#chartContainer").show();
      chart.render();
    } else if (label.is(".toggle-buttons > label:eq(1)")) {
      $("#chartContainer").hide();
      $("#tradingView").hide();
      $("#infoOperations").show();
    } else {
      $("#chartContainer").hide();
      $("#infoOperations").hide();
      $("#tradingView").show();
    }
  });

  // New designing

  $(".add_elements_plus").hover(function (e) {
    var body_cursor = $("body").css("cursor");
    console.log("add_elements_plus");
    if (body_cursor == "auto") {
      if ($(".right_side").css("z-index")) {
        if ($(".add_elements_plus").is(":hover")) {
          // add class
          $(".left_elements_tab").removeClass("element_visibility");
          $(".add_elements_plus").addClass("active");
          $(".left_elements_tab").addClass("active");
          // $('.left_elements_tab').addClass('element_visibility');
        }
      }
    }
  });

  $(document).on("click", "a.left_panel", function (e) {
    e.preventDefault();
    $(".left_elements_tab").css("visibility", "visible");
    // add class
  });

  $(document).on(
    "mouseleave",
    ".add_elements_plus, .left_elements_tab",
    function (e) {
      e.preventDefault();
      var body_cursor = $("body").css("cursor");
      if (
        body_cursor == "auto" &&
        !$(".left_elements_tab").is(":hover") &&
        !$(".add_elements_plus").is(":hover") &&
        !$(".tooltipster-base").is(":visible")
      ) {
        // remove class
        $(".add_elements_plus").removeClass("active");
        $(".left_elements_tab").removeClass("active");
      }
    }
  );
  setTimeout(function () {
    $(".card-header")
      .find("a>label")
      .each(function () {
        var paramLabel = $(this).text();
        if (
          $(this).parent().parent().parent().next().find("select").length != 0
        ) {
          var paramValue = $(this)
            .parent()
            .parent()
            .parent()
            .next()
            .find("select option:selected")
            .html();
        } else {
          var paramValue = $(this)
            .parent()
            .parent()
            .parent()
            .next()
            .find("input")
            .val();
        }
        var paramLabelWValue = paramLabel.replace("value", paramValue);
        $(this).text(paramLabelWValue);
      });
  }, 500);

  var survey = null;
  let surveyModal = $("#surveyModal");
  let div = surveyModal.find(".modal-body > .survey-second-step > div.d-none");
  if (div.text() != null && div.text() != "") {
    survey = JSON.parse(div.text());
    let placeholder = div.attr("input-placeholder");
    div.remove();
    surveyModal
      .find(".modal-header > h5.survey-second-step")
      .text(survey.title);
    let surveyContainer = surveyModal.find(".modal-body > .survey-second-step");
    if (survey.description != null && survey.description != "") {
      surveyContainer.find("> p").text(survey.description);
    } else {
      surveyContainer.find("> p").addClass("d-none");
    }

    let index = 0;
    survey.questions.forEach((question) => {
      let questionDiv = $(
        '<div class="question"><p>' +
          question.text +
          '</p><div class="answers"></div></div>'
      );
      questionDiv.data("id", question.id);
      if (index > 0) {
        questionDiv.addClass("d-none");
      }
      let ansIndex = 0;
      question.answers.forEach((answer) => {
        let answerDiv = $('<div class="custom-control custom-radio">');
        answerDiv.append(
          '<input class="custom-control-input" type="radio" name="answer' +
            (index + 1) +
            '" id="survAnswer' +
            answer.id +
            '" value="' +
            answer.id +
            '">'
        );
        answerDiv.append(
          '<label class="custom-control-label" for="survAnswer' +
            answer.id +
            '"></label>'
        );
        answerDiv.find("> label").text(answer.text);
        if (ansIndex == 0) {
          answerDiv.find('> input[type="radio"]').prop("checked", true);
        }
        if (answer.type == "O") {
          answerDiv.append(
            '<input class="form-control" type="text" name="otherAns' +
              answer.id +
              '" placeholder="' +
              placeholder +
              '">'
          );
        }

        answerDiv
          .find("> .custom-control-input")
          .attr("answer-type", answer.type);
        questionDiv.find("> .answers").append(answerDiv);
        ansIndex++;
      });
      surveyContainer.append(questionDiv);
      index++;
    });
  } else {
    div.remove();
  }

  $("#surveyModal .modal-footer > .survey-first-step > .confirm-btn").click(
    function () {
      let modal = $("#surveyModal");
      modal.find(".survey-first-step").addClass("d-none");
      modal.find(".survey-second-step").removeClass("d-none");
    }
  );

  var userSurvey = { answers: [] };
  $("#nextQuestionSurvey").click(function () {
    let activeQuestion = $(this)
      .closest(".modal-content")
      .find(".modal-body .survey-second-step > .question:not(.d-none)");
    activeQuestion.addClass("d-none");
    let selectedOption = activeQuestion.find(".answers input:checked");
    if (selectedOption.attr("answer-type") == "O") {
      let answer = selectedOption
        .parent()
        .find('> input[name^="otherAns"]')
        .val();
      userSurvey.answers.push({
        qid: activeQuestion.data("id"),
        answer: selectedOption.val(),
        other: answer,
      });
    } else {
      userSurvey.answers.push({
        qid: activeQuestion.data("id"),
        answer: selectedOption.val(),
      });
    }
    if (activeQuestion.next().is(":last-child")) {
      $(this).addClass("d-none");
      $(this)
        .closest(".survey-second-step")
        .find(".confirm-btn")
        .removeClass("d-none");
    }
    activeQuestion.next().removeClass("d-none");
  });

  $("#surveyModal .modal-footer .survey-second-step > .confirm-btn").click(
    function () {
      let modalContent = $(this).closest(".modal-content");
      let activeQuestion = modalContent.find(
        ".modal-body .survey-second-step > .question:not(.d-none)"
      );
      let selectedOption = activeQuestion.find(".answers input:checked");
      if (selectedOption.attr("answer-type") == "O") {
        let answer = selectedOption
          .parent()
          .find('> input[name^="otherAns"]')
          .val();
        userSurvey.answers.push({
          qid: activeQuestion.data("id"),
          answer: selectedOption.val(),
          other: answer,
        });
      } else {
        userSurvey.answers.push({
          qid: activeQuestion.data("id"),
          answer: selectedOption.val(),
        });
      }
      userSurvey.sid = survey.sid;

      modalContent
        .find(
          ".modal-body > .survey-second-step, .modal-footer > .survey-second-step"
        )
        .addClass("d-none");
      modalContent
        .find(".survey-third-step, .modal-header > button")
        .removeClass("d-none");

      let url = $(this).attr("data-action");
      $.ajax({
        type: "POST",
        url: url,
        data: userSurvey,
        dataType: "json",
      });
      $("#show_surveyModal").remove();
    }
  );
});

async function checkSurveys() {
  let modal = $("#surveyModal");
  if (modal.length > 0) {
    modal.modal("show");
  }
}

function set_default_lis($, row_L = false) {
  var icon = $("#nav-tab2 .active").attr("data-icon-id");

  $("#nav-tabContent .gallery")
    .find("[data-element-append]")
    .each(function (index, el) {
      var $this = $(el);

      if ($this.attr("data-title") != "SEQ") {
        var element_id = $this.attr("data-element-append");

        if (row_L != false) {
          if (element_id == icon) {
            var first_row = row_L.find(".trash");
            var appendedEl = $this.clone().appendTo(first_row).show();
            appendedEl.find("[disabled]").removeAttr("disabled");
            appendedEl.find(".d-none").removeClass("d-none");

            var alreadyClass = appendedEl.find("span").attr("class");
            var rowID = row_L.attr("class").split("gallery_new")[1];

            appendedEl
              .find("span:first")
              .attr("class", alreadyClass + "-" + rowID);

            appendedEl.attr(
              "data-tooltip-content",
              "#" + alreadyClass + "-" + rowID
            );

            // $("<button type='button' class='close' aria-label='Close'><span aria-hidden='true'>&times;</span></button>").appendTo(appendedEl);
            $(
              "<button type='button' class='close' aria-label='Close'>x</button>"
            ).prependTo(".gallery_new" + rowID);
          }
        } else {
          if (element_id == 49) {
            var first_row = $(
              '.tab-pane[data-tab_id="10"] table tr:first td .trash'
            );
            var appendedEl = $this
              .clone()
              .insertBefore(first_row.find("li:last"))
              .show();
          } else {
            var first_row = $(
              '.tab-pane[data-tab_id="' +
                element_id +
                '"] table tr:first td .trash'
            );
            var appendedEl = $this.clone().appendTo(first_row).show();
          }
          appendedEl.find("[disabled]").removeAttr("disabled");
          appendedEl.find(".d-none").removeClass("d-none");
          $(
            "<button type='button' class='close' aria-label='Close'>x</button>"
          ).prependTo(".gallery_new");

          // $("<button type='button' class='close' aria-label='Close'><span aria-hidden='true'>&times;</span></button>").appendTo(appendedEl).hide();
        }
      }
    });

  $("#nav-tabContent .gallery")
    .find("[data-element-append_conf]")
    .each(function (index, el) {
      var $this = $(el);

      var element_id = $this.attr("element_tab_index");

      if (row_L != false) {
        if (element_id == icon) {
          var first_row = row_L.find(".trash");

          var appendedEl = $this.clone().appendTo(first_row).show();
          appendedEl.find("[disabled]").removeAttr("disabled");
          appendedEl.find(".d-none").removeClass("d-none");

          var alreadyClass = appendedEl.find("span").attr("class");
          var rowID = row_L.attr("class").split("gallery_new")[1];

          appendedEl
            .find("span:first")
            .attr("class", alreadyClass + "-" + rowID);

          appendedEl.attr(
            "data-tooltip-content",
            "#" + alreadyClass + "-" + rowID
          );

          // $("<button type='button' class='close' aria-label='Close'>x</button>").prependTo('.gallery_new'+rowID);
        }
      } else {
        var first_row = $(
          '.tab-pane[data-tab_id="' + element_id + '"] table tr:first td .trash'
        );

        var appendConf = $this
          .clone()
          .insertBefore(first_row.find(".dashed_image_li"))
          .show();

        appendConf.find("[disabled]").removeAttr("disabled");
        appendConf.find(".d-none").removeClass("d-none");
        // $("<button type='button' class='close' aria-label='Close'>x</button>").prependTo('.gallery_new'+rowID);
      }
    });
}

function setScrollAndIndex($) {
  $("table .trash:visible").each(function (index, el) {
    var row = $(el);
    var allListWidth = 0;

    row.find("li").each(function (index2, el2) {
      var li = $(el2);
      li.attr("data-index", index2);
      allListWidth += li.outerWidth() + 5;
    });

    row.width(allListWidth);
  });
}

// TODO: Revisar función
function validate_strategy($) {
  var error_txt = $("input.error_code_data").attr("data-error");

  // var error_0 = error_txt+" "+$("input.error_code_data").attr('data-error-0');
  var error_1 =
    error_txt + " " + $("input.error_code_data").attr("data-error-1");
  var error_2 =
    error_txt + " " + $("input.error_code_data").attr("data-error-2");
  var error_3 =
    error_txt + " " + $("input.error_code_data").attr("data-error-3");

  var check_li_first = [];
  var check_li_last = [];
  var chack_two_arrow = [];

  // Validation elements arrangement
  $("#nav-tabContent2 .omc.tab-pane").each(function (index, tabs) {
    var $tabs = $(tabs);
    var RowData = "";

    $tabs.find(".trash").each(function (index, rows) {
      var $rows = $(rows);

      var li_length = $rows.find("li").length;

      var third_last_li = li_length - 2;

      var first_li = $rows.find("li:first-child");

      var scnd_li = $rows.find("li:nth-child(" + third_last_li + ")");

      check_li_first.push(first_li.hasClass("sequence_li"));
      check_li_last.push(scnd_li.hasClass("sequence_li"));

      $rows.find("li").each(function (index, li) {
        var $li = $(li);

        var boolean_ =
          $li.hasClass("sequence_li") && $li.next().hasClass("sequence_li");
        chack_two_arrow.push(boolean_);
      });
    });
  });

  // console.log(jQuery.inArray(true, check_li_last));

  // Disply error popup
  if (
    jQuery.inArray(true, check_li_last) != "-1" ||
    jQuery.inArray(true, check_li_first) != "-1" ||
    jQuery.inArray(true, chack_two_arrow) != "-1"
  ) {
    if (jQuery.inArray(true, check_li_first) != "-1") {
      alert(error_1);
    }
    if (jQuery.inArray(true, check_li_last) != "-1") {
      alert(error_2);
    }
    if (jQuery.inArray(true, chack_two_arrow) != "-1") {
      alert(error_3);
    }
  } else {
    return true;
  }
}

function setTdWidth($) {
  $("table tr td").width($("#nav-tab2").outerWidth() - 24);
}

$(document).on("click", 'input[name="read_accept"]', function (event) {
  if ($(this).attr("checked")) {
    $(this).removeAttr("checked");
  } else {
    $(this).attr("checked", true);
  }
  var first_option = $('input[name="read_accept"]').attr("checked");

  // console.log(first_option, scnd_option);
  // JFS - 15/06/2020 - quitamos el check de condiciones generales
  //if(first_option == 'checked' && scnd_option == 'checked'){
  if (first_option == "checked") {
    $("#right_download").css({ opacity: 1 });
    $("#right_download").css("pointer-events", "unset");
    $("#download-premium").css({ opacity: 1 });
    $("#download-premium").removeAttr("disabled");
    $("#download-premium").prop("disabled", false);
  } else {
    $("#right_download").css({ opacity: 0.4 });
    $("#right_download").css("pointer-events", "none");
    $("#download-premium").attr("disabled", true);
    $("#download-premium").prop("disabled", true);
  }
});

function Compilar(tipo, callbackAcabado) {
  // console.log("compilamos tipo " + tipo);

  var functionCount = 0;
  $(".payment_loader").show();
  $(".download_pay").attr("disabled", true);
  $("#right_compile").css({ opacity: 0.4 });
  $("#right_compile").css("pointer-events", "none");
  var session_id = $("input[name=session_strategy_id]").val();
  var url = $("input[name='session_compiled']").val();
  /*
   * Ajax
   * ->
   * */
  return $.ajax({
    url: url,
    type: "POST",
    data: {
      session_id: session_id,
      tipo: tipo,
    },
  }).done(function (response) {
    // console.log("compilacion ha devuelto" + response);
    var compile_id = response.trim();
    var close_interval;
    var url = $("input[name='check_link_status']").val();
    close_interval = setInterval(function () {
      functionCount++;
      $.ajax({
        url: url,
        type: "POST",
        data: {
          compile_id: compile_id,
        },
      })
        .done(function (response) {
          var response_data = response.trim();
          if (response_data != "") {
            if (response_data.includes("complie_error")) {
              ATF_compile_result = "ERROR";
              var error = response_data.split("complie_error")[0];
              $(".download_pay").attr("disabled", true);
              $(".download_pay").css({ opacity: 0.4 });
              $(".download_pay").css("pointer-events", "none");
              $("#right_compile").css({ opacity: 0.4 });
              $("#right_compile").css("pointer-events", "none");
              $('input[name="read_accept"]').removeAttr("checked");
              $('input[name="agree_condition"]').removeAttr("checked");
              $("#right_download").css({ opacity: 0.4 });
              $("#right_download").css("pointer-events", "none");
              $("#right_compile").css("pointer-events", "unset");
              $(".payment_loader").hide();
              // Mostrar alerta de error
              customAlert("Error", error);
            } else {
              ATF_compile_result = response_data;
              ATF_compile_result_tipo = tipo;
              // console.log("compilacion acabada: " + response_data);
              $('input[name="read_accept"]').attr("disabled", false);
              $('input[name="agree_condition"]').attr("disabled", false);

              $("#right_compile").css({ opacity: 1 });
              $("#right_compile").css("pointer-events", "unset");
              $(".payment_loader").hide();
              if (callbackAcabado) callbackAcabado();
            }
            clearInterval(close_interval);
          } else {
            timeOut_seconds = parseInt($("#timeOut_seconds").val()) / 3;
            if (functionCount > timeOut_seconds) {
              console.log("Server not responding in function");
              clearInterval(close_interval);
              $(".payment_loader").hide();
              $("#right_compile, #right_download").removeAttr("style");
              $("#right_compile, #right_download").css({
                opacity: "1",
                "pointer-events": "unset",
              });
              $(".right-options2 img").attr("disabled", false);
              $("#server_not_responding").trigger("click");
            }
          }
        })
        .fail(function () {
          console.log("fail");
          timeOut_seconds = parseInt($("#timeOut_seconds").val()) / 3;
          if (functionCount > timeOut_seconds) {
            clearInterval(close_interval);
            $(".payment_loader").hide();
            $("#right_compile, #right_download").removeAttr("style");
            $("#right_compile, #right_download").css({
              opacity: "1",
              "pointer-events": "unset",
            });
            $(".right-options2 img").attr("disabled", false);
            $("#server_not_responding").trigger("click");
          }
        });
    }, 3000);
    // }, 1000);
  });
}

function waitForCompilation(type, callback) {
  var functionCount = 0;
  $(".payment_loader").show();
  $(".download_pay").attr("disabled", true);
  $("#right_compile").css({ opacity: 0.4 });
  $("#right_compile").css("pointer-events", "none");

  var close_interval;
  var url = $("input[name='check_link_status']").val();
  close_interval = setInterval(function () {
    functionCount++;
    $.ajax({
      url: url,
      type: "POST",
    })
      .done(function (response) {
        var response_data = response.trim();
        if (response_data != "") {
          if (response_data.includes("complie_error")) {
            ATF_compile_result = "ERROR";
            var error = response_data.split("complie_error")[0];
            $(".download_pay").attr("disabled", true);
            $(".download_pay").css({ opacity: 0.4 });
            $(".download_pay").css("pointer-events", "none");
            $("#right_compile").css({ opacity: 0.4 });
            $("#right_compile").css("pointer-events", "none");
            $('input[name="read_accept"]').removeAttr("checked");
            $("#right_download").css({ opacity: 0.4 });
            $("#right_download").css("pointer-events", "none");
            $("#right_compile").css("pointer-events", "unset");
            $(".payment_loader").hide();
            // Mostrar alerta de error
            customAlert("Error", error);
          } else {
            ATF_compile_result = response_data;
            ATF_compile_result_tipo = type;
            if (callback) callback();
          }
          clearInterval(close_interval);
        } else {
          timeOut_seconds = parseInt($("#timeOut_seconds").val()) / 3;
          if (functionCount > timeOut_seconds) {
            console.log("Server not responding in function");
            clearInterval(close_interval);
            $(".payment_loader").hide();
            $("#right_compile, #right_download").removeAttr("style");
            $("#right_compile, #right_download").css({
              opacity: "1",
              "pointer-events": "unset",
            });
            $(".right-options2 img").attr("disabled", false);
            $(
              ".left-arrow, .img-build, .img-validate, .right-arrow"
            ).removeClass("disabled_events");
            $("#server_not_responding").trigger("click");
          }
        }
      })
      .fail(function () {
        console.log("fail");
        timeOut_seconds = parseInt($("#timeOut_seconds").val()) / 3;
        if (functionCount > timeOut_seconds) {
          clearInterval(close_interval);
          $(".payment_loader").hide();
          $("#right_compile, #right_download").removeAttr("style");
          $("#right_compile, #right_download").css({
            opacity: "1",
            "pointer-events": "unset",
          });
          $(".right-options2 img").attr("disabled", false);
          $(".left-arrow, .img-build, .img-validate, .right-arrow").removeClass(
            "disabled_events"
          );
          $("#server_not_responding").trigger("click");
        }
      });
  }, 3000);
}

function ATFWebHook(tipo) {
  //Compilar().done(ATFWebHook)
  var user_id = $('input[name="user_id"]').val();

  //INIT Alba 15/11 cambio de server dependiendo del host
  var ip_test = $("#ip_test").html();
  var ip_live = $("#ip_live").html();
  var host_name = document.location.hostname;

  if (host_name == "test.tradeasy.tech") {
    var ip_server = ip_test;
  } else if (host_name == "tradeasy.tech") {
    var ip_server = ip_live;
  } else {
    console.log("No se reconoce el host:" + host_name);
  }
  // Alba 15/11 cambio de server dependiendo del host

  //var url = 'https://hook.integromat.com/9mkkeuehgtal8s23ukvh8pdnid0evkq5?nombre=' + ATF_compile_result + '&id_user=' + user_id + '&tipo_comp=' + tipo;
  var url =
    "https://hook.integromat.com/piqvu65i236vej3g3a96vam9l3tqrx6h?nombre=" +
    ATF_compile_result +
    "&id_user=" +
    user_id;
  console.log("Webhook tipo " + tipo);
  $(".payment_loader").show();
  if (ATF_compile_result.endsWith(".mq4") || tipo == "D" || tipo == "P") {
    //no compilado, no llamar webhook, ponemos URL directamente y devolvemos un promise vacio
    $("#right_compile").css({ opacity: 1 });
    $("#right_compile").css("pointer-events", "unset");
    $(".payment_loader").hide();
    alert("entrega Asesor Experto para MetaTrader4");

    peticion = Promise.resolve(ip_server + ATF_compile_result);
  } else peticion = $.ajax({ url: url, type: "GET" });

  return peticion.then(function (response) {
    console.log("Webhook ha devuelto " + response);
    // Remove Disabled checkbox when compiling
    $(".payment_loader").hide();
    $('input[name="read_accept"], .download_pay').attr("disabled", false);
    $("#myanchor").attr("href", response);
    $(".payment_loader").hide();
    $("#right_download, #right_compile, .download_pay").css({
      opacity: 1,
      pointerEvents: "unset",
    });
    $(".left-arrow, .img-build, .img-validate, .right-arrow").removeClass(
      "disabled_events"
    );
  });
}

$(".download_pay").click(function (event) {
  var tipo;
  if (this.id == "download-full") tipo = "F";
  else if (this.id == "download-demo") tipo = "D";
  else if (this.id == "download-premium") tipo = "P";
  else alert("Error interno");

  $(".payment_loader").show();
  $(".download_pay").attr("disabled", true);
  $(".download_pay, #right_compile, #right_download").css({ opacity: 0.4 });
  $(".download_pay, #right_compile, #right_download").css(
    "pointer-events",
    "none"
  );
  $("#right_compile").css("pointer-events", "unset");

  if (last_screen == 2) {
    if (ATF_compile_result != null && ATF_compile_result != "ERROR") {
      downloadRequest();
    } else {
      $(".left-arrow, .img-build, .img-validate, .right-arrow").addClass(
        "disabled_events"
      );
      waitForCompilation("V", callbackCompilation);
    }
  } else {
    if (ATF_compile_result != null && ATF_compile_result != "ERROR") {
      downloadRequest();
    } else {
      $(".left-arrow, .img-build, .img-validate, .right-arrow").addClass(
        "disabled_events"
      );
      waitForCompilation("D", callbackCompilation);
    }
  }

  // Se ejecuta después de la compilación
  function callbackCompilation() {
    console.log("after compile");
    if (ATF_compile_result != null && ATF_compile_result != "ERROR") {
      downloadRequest();
    }
  }

  // Petición de descarga
  function downloadRequest() {
    //event.preventDefault();
    $('input[name="read_accept"]').attr("disabled", false);
    $("#right_compile").css({ opacity: 1 });
    $("#right_compile, .download_pay").css("pointer-events", "unset");

    if (ATF_compile_result == "ERROR") {
      $(".payment_loader").hide();
      $(".left-arrow, .img-build, .img-validate, .right-arrow").removeClass(
        "disabled_events"
      );
      alert("Error de compilacion, no se puede descargar");
      return;
    }

    if (ATF_compile_result && ATF_compile_result_tipo == tipo) {
      ATFWebHook(tipo).then(function () {
        var url = $("#myanchor").attr("href");
        window.open(url, "_blank");
        //$(".download_pay").attr('disabled', true);
        // window.location = $('#myanchor').attr('href');
        //ATF_compile_result = undefined;
        //disableDownload();
      });
    } else {
      ATFWebHook(tipo).then(function () {
        var url = $("#myanchor").attr("href");
        window.open(url, "_blank");
        //$(".download_pay").attr('disabled', true);
        // window.location = $('#myanchor').attr('href');
        //ATF_compile_result = undefined;
        //disableDownload();
      });
    }
  }
});

function disableDownload() {
  $(".download_pay").attr("disabled", true);
  $(".download_pay, #right_compile, #right_download").css({ opacity: 0.4 });
  $(".download_pay, #right_compile, #right_download").css(
    "pointer-events",
    "none"
  );
  $('input[name="read_accept"]').removeAttr("checked");
  $('input[name="read_accept"]').prop("disabled", true);
  $("#right_compile").css("pointer-events", "unset");
}

function original_tooltipster_css($) {
  $(".box_shadow").hide();
  $(".design_1").addClass("tooltip_content_container");
  $(".tooltip_content_container").removeClass("design_1");
  $(".main_head").removeAttr("style");
  $(".main_head").find("img").css("display", "none");
  $("p.el-desc").next("hr").remove();
  $(".pop_image").hide();
  $(".close_tooltip").removeAttr("style");
}

function customAlert(title, msg) {
  if (title == "default") {
    title = $("#customAlert h5.modal-title").attr("default-title");
  }
  $("#customAlert h5.modal-title").text(title);
  $("#customAlert div.modal-body > p").text(msg);
  $("button#show_customAlert").trigger("click");
}

function generateStrategyString() {
  var strategy_data = new Object();

  $("#nav-tabContent2 .omc.tab-pane").each(function (index, tabs) {
    var $tabs = $(tabs);
    var RowData = "";

    $tabs.find(".trash").each(function (index, rows) {
      var $rows = $(rows);
      var elements = "";

      if ($rows.find("li").length > 2) {
        $rows.find("li:not(.sign)").each(function (index, li) {
          var $li = $(li);
          var paramsData = "";

          if (
            $li.hasClass("add_sequence") == false &&
            $li.hasClass("dashed_image_li") == false
          ) {
            var element_id = $li.find("img").attr("data-elementid");

            $li
              .find('[data-template="true"] [data-field-type]')
              .each(function (index, params) {
                var $param = $(params);

                if (
                  $param.attr("data-field-type") == "integer" ||
                  $param.attr("data-field-type") == "string" ||
                  $param.attr("data-field-type") == "double"
                ) {
                  paramsData += $param.find("input").attr("value") + ",";
                } else if ($param.attr("data-field-type") == "bool") {
                  if ($param.find("input").is(":checked")) {
                    paramsData += "1" + ",";
                  } else {
                    paramsData += "0" + ",";
                  }
                } else {
                  paramsData +=
                    $param.find("select > option[selected]").index() + ",";
                }
              });

            elements += element_id + "," + paramsData.slice(0, -1) + ";";
          }
        });
      }

      RowData += "@" + elements;
    });

    strategy_data[$tabs.attr("id")] = RowData.substr(1);
  });
  return strategy_data;
}

function checkStrategy() {
  var valid = true;
  var tabs = $("#nav-tabContent2 .omc.tab-pane:not(#MODIFY) .trash");
  for (var i = 0; i < tabs.length; i++) {
    var ul_length = $(tabs[i]).find("> li:not(.sign)").length;
    if (ul_length < 4) {
      valid = false;
      var error_txt = $("input.error_code_data").attr("data-error");
      var error_0 =
        error_txt + " " + $("input.error_code_data").attr("data-error-0");
      customAlert("Error", error_0);
      break;
    }
  }
  return valid;
}

function receiveLastStrategy($) {
  var removeDefaultElem = true;
  var row_index = -1;

  let loadedStrategy = window.top.location.search.split("loadStrategy=")[1];
  if (loadedStrategy == "true") {
    // Eliminar parámetro GET de la URL
    var newURL = window.top.location.href.split("?")[0];
    window.top.history.replaceState(
      "object",
      window.top.document.title,
      newURL
    );

    let div_myStrat = $("#my-strategy");
    let myStrategy = JSON.parse(div_myStrat.text());
    div_myStrat.remove();

    let input = $('#modalSaveStrategy input[name="sid"]');
    sid = input.val();
    input.remove();
    var rows_open = myStrategy.rows_open;
    var rows_close = myStrategy.rows_close;

    if (rows_open.length > 0) {
      if (rows_open[0].length > 2) {
        // Ocultar el cuadrado 'Arrastrar aquí'
        $("#OPEN #trash > li.dashed_image_li").addClass("display_none");
      }
      // Reglas entrada
      iterateRows($, rows_open, "OPEN", row_index);
    }
    showHideDashedImage($("table.order-list tbody:visible"));

    // Reglas salida
    iterateRows($, rows_close, "CLOSE", row_index);
    if (removeDefaultElem) {
      // Elimina el elemento Señal Inversa que está por defecto
      removeDefaultElement($);
    }
    showHideDashedImage($("#CLOSE > table.order-list > tbody"));

    // Añadir los +
    addPlusSignOnLoad();
    setTimeout(checkSurveys, 10000);
  } else {
    // Cargar estrategia (con alerta)
    var url = $("[name='lastStrategy']").val();
    $.ajax({
      type: "POST",
      url: url,
    }).done(function (response) {
      //console.log(response);
      if (response != "error") {
        var responseData = JSON.parse(response);
        sid = responseData.id;
        var rows_open = responseData.rows_open;
        var rows_close = responseData.rows_close;
        let strategy_name = responseData.strategy_name;

        var defaultStrategy = false;
        if (rows_open.length == 0 && rows_close.length == 1) {
          if (rows_close[0].length == 3) {
            var elem = rows_close[0][1];
            if (elem.element_id == 49) {
              defaultStrategy = true;
            }
          }
        }

        var modal = $("#modalLS");
        var btn_showModal = $("#show_modalLS");
        if (!defaultStrategy && rows_open.length > 0 && rows_close.length > 0) {
          // Mostrar alerta (cargar última estrategia)
          btn_showModal.trigger("click");

          modal.on("hidden.bs.modal", function (e) {
            modal.remove();
            btn_showModal.remove();
          });

          $("#modalLS button#confirmButton").click(function () {
            modal.modal("hide");
            $("#strategyName").text(strategy_name);
            if (rows_open.length > 0) {
              // Ocultar el cuadrado 'Arrastrar aquí'
              if (rows_open[0].length > 2) {
                $("#OPEN #trash > li.dashed_image_li").addClass("display_none");
              }
              iterateRows($, rows_open, "OPEN", row_index);
              showHideDashedImage($("table.order-list tbody:visible"));
            }
            if (rows_close.length > 0) {
              iterateRows($, rows_close, "CLOSE", row_index);
              if (removeDefaultElem) {
                // Elimina el elemento Señal Inversa que está por defecto
                removeDefaultElement($);
              }
              showHideDashedImage($("#CLOSE > table.order-list > tbody"));
            }
            // Añadir los +
            addPlusSignOnLoad();
            setTimeout(checkSurveys, 10000);
          });

          $('#modalLS button[data-dismiss="modal"]').click(function () {
            // Guardamos la estrategia como esté
            $("#save_data").trigger("click", ["auto-save"]);
            setTimeout(checkSurveys, 10000);
          });
        } else {
          modal.remove();
          btn_showModal.remove();
          setTimeout(checkSurveys, 10000);
        }
      } else {
        // Error
        //console.log("Error");
        modal.remove();
        btn_showModal.remove();
        setTimeout(checkSurveys, 10000);
      }
    });
  }

  function iterateRows($, elements_list, type, row_index) {
    for (var index in elements_list) {
      if (index == 0 && type == "CLOSE") {
        var gallery_new = getGalleryNew($, -1);
        row_index = window.counter - 1;
      } else {
        var gallery_new = getGalleryNew($, row_index);
      }
      if (row_index >= 0 && index > 0) {
        addRow(type);
      }
      addElementsFromTab($, elements_list[index], type, gallery_new);
      row_index++;
    }
  }

  function getGalleryNew($, row_index) {
    var gallery_new = "";
    if (row_index == -1) {
      gallery_new = "gallery_new";
    } else {
      gallery_new = "gallery_new" + row_index;
    }
    return gallery_new;
  }

  function addElementsFromTab($, elements_list, type, gallery_new) {
    var hasDefaultElement = false;
    var container = $("#" + type + " td." + gallery_new).find("ul.trash");
    for (var key in elements_list) {
      var element_obj = elements_list[key];
      var id = parseInt(element_obj.element_id);
      // Configuración de entrada, abrir orden, Configuración de salida y cerrar orden
      if (id == 46 || id == 8 || id == 48 || id == 10) {
        var config_element_li = container
          .find("img[data-elementid=" + id + "]")
          .parent();
        setElementParameters($, config_element_li, element_obj);
      } else if (id == 49) {
        // Señal inversa
        if (
          hasDefaultElement ||
          type == "OPEN" ||
          gallery_new != "gallery_new"
        ) {
          var element_li = $("#EVENT_ORDER ul.gallery > li")
            .find("img[data-elementid=" + id + "]")
            .parent();
          var addedElement = addElement($, element_li, container);
          setElementParameters($, addedElement, element_obj);
        }
        if (type == "CLOSE" && gallery_new == "gallery_new") {
          removeDefaultElem = false;
          hasDefaultElement = true;
        }
      } else {
        var element_li = $("ul.gallery > li")
          .find("img[data-elementid=" + id + "]")
          .parent();
        var addedElement = addElement($, element_li, container);
        setElementParameters($, addedElement, element_obj);
      }
    }
  }
}

function addRow(type) {
  var li_length_for_addScenario = $(
    "#" + type + " table.order-list tbody tr:nth-last-child(2) td .trash li"
  ).length;
  if (li_length_for_addScenario > 2) {
    var cols = "";
    var dashed_image_li_clone = $(
      "#" + type + " table.order-list tbody tr:first-child"
    )
      .find(".dashed_image_li")
      .html();
    var append_ = $("#" + type + " table.order-list > tbody");

    var td_width = $("td.gallery_new").css("width");
    cols +=
      "<tr><td class='gallery_new" +
      window.counter +
      "' style='width: " +
      td_width +
      ";'><div class='ui-helper-reset gallery-rep'><ul id='trash' class='trash ui-widget-content ui-state-default' style='overflow-x: auto;'><li class='dashed_image_li d-sort display_none'>" +
      dashed_image_li_clone +
      "</li></ul></div></td></tr>";

    var tr = $(cols).insertBefore(append_.find("tr.add_new_stage"));
    if (type == "OPEN") {
      set_default_lis($, $(".gallery_new" + window.counter));
      drag_and_drop_elements($, "append_row", window.counter);
    } else if (type == "CLOSE") {
      var closeOrder = 10,
        closeConfig = 48;
      var rowID = addConfigElement($, "data-element-append", closeOrder);
      $(
        "<button type='button' class='close' aria-label='Close'>x</button>"
      ).prependTo(".gallery_new" + rowID);
      addConfigElement($, "data-element-append_conf", closeConfig);
    }
    var row = $(tr).find("td");

    // Configuration
    row.find(".configuration").insertBefore(row.find("li:first"));
    row.find(".door_image_li").insertAfter(row.find("li:last"));

    addNumberOfRule(row);
    setScrollAndIndex($);
    window.counter++;
  }
}

function removeDefaultElement($) {
  var reverseSignal_ID = 49;
  var container = $("#CLOSE").find("td.gallery_new ul.trash");
  var defaultElement = container
    .find("img[data-elementid=" + reverseSignal_ID + "]")
    .parent();
  defaultElement.remove();
}

function addConfigElement($, attr, attr_value) {
  var row_L = $(".gallery_new" + window.counter);
  var config_elem = $("#nav-tabContent .gallery").find(
    "[" + attr + "=" + attr_value + "]"
  );

  var first_row = row_L.find(".trash");
  var appendedEl = config_elem.clone().appendTo(first_row).show();
  appendedEl.find("[disabled]").removeAttr("disabled");
  appendedEl.find(".d-none").removeClass("d-none");

  var alreadyClass = appendedEl.find("span").attr("class");
  var rowID = row_L.attr("class").split("gallery_new")[1];

  appendedEl.find("span:first").attr("class", alreadyClass + "-" + rowID);
  appendedEl.attr("data-tooltip-content", "#" + alreadyClass + "-" + rowID);

  drag_and_drop_elements($, "append_row", window.counter);
  return rowID;
}

function addElement($, element_li, container) {
  var element = element_li
    .clone()
    .insertBefore(container.find(".dashed_image_li"));
  var row = element.closest("td");
  var current_element_id = element_li.find("img").attr("data-elementid");
  var tooltip_content = element.find("span[data-template]");
  tooltip_content.find("[disabled]").removeAttr("disabled");
  tooltip_content.find("button").removeClass("d-none");
  var div_params = tooltip_content.find("div.testing");
  div_params.addClass("parameters");
  div_params.addClass("parameter_height");
  div_params.css({
    height: "auto",
    overflowY: "visible",
    display: "none",
  });

  var index_of_element = tooltip_content.attr("class").split("-");
  var tooltipClass =
    row.attr("class") +
    current_element_id +
    "tooltip-content" +
    index_of_element[1];
  tooltip_content.attr("class", tooltipClass);
  element.attr("data-tooltip-content", "#" + tooltipClass);

  setScrollAndIndex($);
  element.addClass("shadow");
  element.removeClass("tooltipstered");
  element.removeAttr("style");
  element.css({
    display: "list-item",
  });

  return element;
}

function setElementParameters($, element, element_obj) {
  element.find("div.testing > div").each(function (index, parameter) {
    var parameter = $(parameter);
    var param_type = element_obj.param_type[index];
    var value = element_obj.param_value[index];
    if (param_type == "INTEGER") {
      var input = parameter.find("input");
      input.attr("value", value);
      input.val(value);
    } else if (param_type == "BOOL") {
      var input = parameter.find("input");
      input.find("[checked]").prop("checked", false);
      if (value === "1") {
        input.prop("checked", true);
      } else {
        input.prop("checked", false);
      }
    } else if (param_type == "DOUBLE") {
      var input = parameter.find("input");
      input.attr("value", value);
      input.val(value);
    } else if (param_type == "STRING") {
      var input = parameter.find("input");
      input.attr("value", value);
      input.val(value);
    } else if (param_type == "DESPLEGABLE") {
      parameter.find("select > option[selected]").removeAttr("selected");
      var options = parameter.find("select > option");
      options.each(function (index, option) {
        var option = $(option);
        if (index == value) {
          option.attr("selected", true);
          option.parent().attr("value", value);
          return;
        }
      });
    }
  });
}

function checkParams(oldParams, newParams) {
  var oldParamsValues = [];
  oldParams.find("> div").each(function (index, param) {
    var parameter = $(param);
    var type = parameter.attr("data-field-type");
    if (type == "dropdown") {
      var selected = parameter.find("select > option[selected]").index();
      oldParamsValues.push(selected);
    } else if (type == "bool") {
      var selected = parameter.find("input[checked]");
      oldParamsValues.push(selected.length);
    } else {
      var input = parameter.find("input");
      oldParamsValues.push(input.val());
    }
  });

  var newParamsValues = [];
  newParams.find("> div").each(function (index, param) {
    var parameter = $(param);
    var type = parameter.attr("data-field-type");
    if (type == "dropdown") {
      var selected = parameter.find("select > option[selected]").index();
      newParamsValues.push(selected);
    } else if (type == "bool") {
      var selected = parameter.find("input[checked]");
      newParamsValues.push(selected.length);
    } else {
      var input = parameter.find("input");
      newParamsValues.push(input.val());
    }
  });

  var equal = arraysEqual(newParamsValues, oldParamsValues);
  return equal;
}

function arraysEqual(a, b) {
  if (a instanceof Array && b instanceof Array) {
    if (a.length != b.length) return false;
    for (var i = 0; i < a.length; i++)
      if (!arraysEqual(a[i], b[i])) return false;
    return true;
  } else {
    return a == b;
  }
}

function addNumberOfRule(td, num) {
  var tr = td.parent();
  tr.addClass("flex-column");
  if (num === undefined) {
    num = tr.parent().find("tr:not(.add_new_stage)").length;
  }
  var text = $("button#addrow").attr("rule-text");
  var ruleTitle = $(
    '<th class="p-0"><p class="rule-title">' + text + " " + num + "</p></th>"
  );

  td.find("> button.close").css({
    width: "25.33px",
    height: "27.39px",
  });
  tr.find("> td").before(ruleTitle);
}

function relationalParameters(container) {
  var childs = container.find("> div.card[parent-id], div.card[parent-id]");
  if (childs.length > 0) {
    var ids = childs
      .map(function () {
        return $(this).attr("parent-id");
      })
      .get();
    ids = new Set(ids);
    ids.forEach((id, i) => {
      var parent = container.find('div[pid="' + id + '"]');
      var field = parent.find("input, select");
      field.on("change input", function () {
        var type = parent.attr("data-field-type");
        if (type == "dropdown") {
          var opt_selected = parent.find("select > option:selected");
          var childs = container.find('div[parent-id="' + id + '"]');
          childs.each(function (index, child) {
            child = $(child);
            var parent_value = child.attr("parent-value");
            if (parent_value != opt_selected.index()) {
              child.hide();
            } else {
              child.show();
            }
          });
        } else if (type == "bool") {
          var input = parent.find("input");
          var childs = container.find('div[parent-id="' + id + '"]');
          childs.each(function (index, child) {
            child = $(child);
            var checked = input.is(":checked");
            var parent_value = child.attr("parent-value");
            if (parent_value != input.is(":checked")) {
              child.hide();
            } else {
              child.show();
            }
          });
        } else {
          var input = parent.find("input");
          var childs = container.find('div[parent-id="' + id + '"]');
          childs.each(function (index, child) {
            child = $(child);
            var parent_value = child.attr("parent-value");
            if (parent_value != input.val()) {
              child.hide();
            } else {
              child.show();
            }
          });
        }
      });
    });

    childs.each(function (index, child) {
      child = $(child);
      var parent_id = child.attr("parent-id");
      var parent_value = child.attr("parent-value");
      var parent = container.find('> div[pid="' + parent_id + '"]');
      var parent_type = parent.attr("data-field-type");
      if (parent_type == "dropdown") {
        var opt_selected = parent.find("select > option:selected");
        if (parent_value != opt_selected.index()) {
          child.hide();
        }
      } else if (parent_type == "bool") {
        var input = parent.find("input");
        var checked = input.is(":checked");
        if (parent_value != input.is(":checked")) {
          child.hide();
        }
      } else {
        var input = parent.find("input");
        if (parent_value != input.val()) {
          child.hide();
        }
      }
    });
  }
}

function inactivityListener(maxTime) {
  var time;
  const URL_logout = $('input[name="logout_url"]').val();

  $('input[name="logout_url"]').remove();

  window.onload = resetTimer;
  var events = ["mousedown", "mousemove", "keypress", "scroll", "touchstart"];
  events.forEach(function (name) {
    document.addEventListener(name, resetTimer, true);
  });

  function resetTimer() {
    clearTimeout(time);
    time = setTimeout(logout, maxTime * 1000);
  }

  function logout() {
    window.onbeforeunload = null;
    window.top.location.href = URL_logout;
  }
}
