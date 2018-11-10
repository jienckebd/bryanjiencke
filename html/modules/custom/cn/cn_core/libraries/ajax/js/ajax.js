(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.cn_core_dialog = {
    attach: function (context) {
      $(window).on("dialog:aftercreate", function(e, formId, formDomId, newWrapper) {
        $('body').addClass('ui-dialog--active');
      });

      $(window).on("dialog:beforeclose", function(e, formId, formDomId, newWrapper) {
        $('body').removeClass('ui-dialog--active');
      });
    }
  };

  /**
   * Add new command for form submission success.
   *
   * @param ajax
   * @param response
   * @param status
   */
  Drupal.AjaxCommands.prototype.formSubmitPass = function(ajax, response, status) {
    var newWrapper = response.newWrapper;
    var formId = response.formId;
    var formDomId = response.formDomId;
    var newWrapperElement = $('#' + newWrapper);
    console.log(response);
    newWrapperElement.unbind().trigger('formSubmitPass', [formId, formDomId, newWrapper]);
  };

  /**
   * Add new command for form submission validation failure.
   *
   * @param ajax
   * @param response
   * @param status
   */
  Drupal.AjaxCommands.prototype.formSubmitFail = function(ajax, response, status) {
    var newWrapper = response.newWrapper;
    var formId = response.formId;
    var formDomId = response.formDomId;
    var newWrapperElement = $('#' + newWrapper);
    newWrapperElement.trigger('formSubmitFail', [formId, formDomId, newWrapper]);
  };

  /**
   * Extend core's beforeSubmit.
   *
   * @param formValues
   * @param element
   * @param options
   */
  Drupal.Ajax.prototype.beforeSubmit = function (formValues, element, options) {
    element.closest('.ajax--wrapper').addClass('ajax--processing');
  };

  /**
   * Sets the throbber progress indicator.
   */
  Drupal.Ajax.prototype.setProgressIndicatorThrobber = function () {
    var $element = $(this.element);
    $element.append(this.progress.element);
    $element.closest('.ajax--wrapper').addClass('ajax--processing');
  };

  /**
   * Extend core's success.
   *
   * @param response
   * @param status
   */
  Drupal.Ajax.prototype.success = function (response, status) {
    $('.ajax--processing').removeClass('ajax--processing');
    var _this = this;

    if (this.progress.element) {
      $(this.progress.element).remove();
    }
    if (this.progress.object) {
      this.progress.object.stopMonitoring();
    }
    $(this.element).prop('disabled', false);

    var elementParents = $(this.element).parents('[data-drupal-selector]').addBack().toArray();

    var focusChanged = false;
    Object.keys(response || {}).forEach(function (i) {
      if (response[i].command && _this.commands[response[i].command]) {
        _this.commands[response[i].command](_this, response[i], status);
        if (response[i].command === 'invoke' && response[i].method === 'focus') {
          focusChanged = true;
        }
      }
    });

    if (!focusChanged && this.element && !$(this.element).data('disable-refocus')) {
      var target = false;

      for (var n = elementParents.length - 1; !target && n > 0; n--) {
        target = document.querySelector('[data-drupal-selector="' + elementParents[n].getAttribute('data-drupal-selector') + '"]');
      }

      if (target) {
        $(target).trigger('focus');
      }
    }

    if (this.$form) {
      var settings = this.settings || drupalSettings;
      Drupal.attachBehaviors(this.$form.get(0), settings);
    }

    this.settings = null;
  };

  /**
   * Extend core's eventResponse.
   *
   * @param element
   * @param event
   */
  Drupal.Ajax.prototype.eventResponse = function (element, event) {
    event.preventDefault();
    event.stopPropagation();

    var ajax = this;

    if (ajax.ajaxing) {
      return;
    }

    try {
      if (ajax.$form) {
        if (ajax.setClick) {
          element.form.clk = element;
        }

        ajax.$form.ajaxSubmit(ajax.options);
      } else {
        ajax.beforeSerialize(ajax.element, ajax.options);
        $.ajax(ajax.options);
      }
    } catch (e) {
      ajax.ajaxing = false;
      window.alert('An error occurred while attempting to process ' + ajax.options.url + ': ' + e.message);
    }
  };

})(jQuery, Drupal, drupalSettings);
