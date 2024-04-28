/* global jQuery, wpc_postboxes, ajaxurl, wdp_data */
jQuery(document).ready(function ($) {


  function disableControls() {
    $("#rules-action-controls button").attr("disabled", "disabled");
  }

  function enableControls() {
    $("#rules-action-controls button").removeAttr("disabled");
  }

  $('.wdp-rules-recalculate-cache-action select').select2({
    minimumResultsForSearch: -1,
	dropdownCssClass: 'wdp-rules-recalculate-select'
  });

  $('.wdp-rules-recalculate-cache-action select').on('change', function () {

      if (!$('select[name=recalculace_selector]').val()) {
          return true;
      }

    progressParentBlock.start();
    progress.start();

    $('.wdp-rules-recalculate-cache-action .wdp-row').hide();

    $("#progressBarBlock").show();
    disableControls();
    $("#rules-action-controls button").attr("disabled", "disabled");

    let props = {
      action: 'wdp_ajax',
      method: 'start_partial_' + $('select[name=recalculace_selector]').val(),
    };
    props[wdp_cache_recalculation_data.security_query_arg] = wdp_cache_recalculation_data.security;

    let requestPromise = $.post({
      url: ajaxurl,
      data: props,
      dataType: 'json'
    });

    async function runPartial(data, textStatus, jqXHR) {
      let totalCount = typeof data.data !== 'undefined' && typeof data.data.count !== 'undefined' ? data.data.count : null;

      if (!totalCount) {
        progress.setProgress(100);
        setTimeout(function () {
            $("#progressBarBlock").hide();
            enableControls();
            $('.wdp-rules-recalculate-cache-action .wdp-row').show();
            $('select[name=recalculace_selector]').val('').trigger('change')
        }, 1000);
        return;
      }

      const PAGE_SIZE = 100;

      for (let i = 0; i < totalCount; i += PAGE_SIZE) {
        let props = {
          action: 'wdp_ajax',
          method: 'partial_' + $('select[name=recalculace_selector]').val(),
          from: i,
          count: PAGE_SIZE
        };
        props[wdp_cache_recalculation_data.security_query_arg] = wdp_cache_recalculation_data.security;

        let responseData = await $.post({
          url: ajaxurl,
          data: props,
          dataType: 'json',
        });

        if ( typeof responseData.success === 'undefined' || !responseData.success) {
          let msg = "The operation is failed";
          if ( typeof responseData.data !== 'undefined' && responseData.data ) {
            msg += ' : ' + responseData.data;
          }
          progressParentBlock.setNotification(msg, true, 3.5);


          setTimeout(function () {
            $("#progressBarBlock").hide();
            enableControls();
            $('.wdp-rules-recalculate-cache-action .wdp-row').show();
            $('select[name=recalculace_selector]').val('').trigger('change')
          }, 4500)
          return;
        }

        let percent = ((i + PAGE_SIZE) < totalCount) ? ((i + PAGE_SIZE) / totalCount) * 100 : 100;
        percent = Math.round(percent);
        progress.setProgress(percent);
      }
      progressParentBlock.setNotification('The operation is completed', false, 2.5);

      setTimeout(function () {
        $("#progressBarBlock").hide();
        enableControls();
        $('.wdp-rules-recalculate-cache-action .wdp-row').show();
        $('select[name=recalculace_selector]').val('').trigger('change')
      }, 3500)
    }

    requestPromise.then(runPartial);
  });

  let ProgressBar = (function () {
    function ProgressBar(element) {
      this.el = element;
      this.subscribers = [];
      this.init();
    }

    ProgressBar.prototype.init = function () {
      this.el.css("width", "100%")
        .css("background", "#292929")
        .css("border", "1px solid #111")
        .css("border-radius", "5px")
        .css("overflow", "hidden")
        .css("box-shadow", "0 0 5px #333")
        .css("float", "right");

      let subElement = $("<div></div>");
      subElement.css("height", "100%")
        .css("color", "#fff")
        .css("text-align", "right")
        .css("font-size", "12px")
        .css("line-height", "22px")
        .css("text-align", "right")
        .css("background-color", "#1a82f7")
        .css("background", "-webkit-gradient(linear, 0% 0%, 0% 100%, from(#0099FF), to(#1a82f7))")
        .css("background", "-webkit-linear-gradient(top, #0099FF, #1a82f7)")
        .css("background", "-moz-linear-gradient(top, #0099FF, #1a82f7)")
        .css("background", "-ms-linear-gradient(top, #0099FF, #1a82f7)")
        .css("background", "-o-linear-gradient(top, #0099FF, #1a82f7)");

      this.el.append(subElement);
      this.setProgress(0);
    }

    ProgressBar.prototype.start = function () {
      this.setProgress(0);
    }

    ProgressBar.prototype.finish = function () {
      this.setProgress(100);
    }

    ProgressBar.prototype.setProgress = function (percent) {
      if (percent === 0) {
        this.el.find('div').html(percent + "%&nbsp;").animate({width: 0}, 0);
      } else {
        var progressBarWidth = percent * this.el.width() / 100;
        this.el.find('div').html(percent + "%&nbsp;").animate({width: progressBarWidth}, 200);
      }

      this.__notify(percent);
    }

    ProgressBar.prototype.addSubscriber = function (sub) {
      this.subscribers.push(sub);
    }

    ProgressBar.prototype.__notify = function (percent) {
      for (let i in this.subscribers) {
        let subscriber = this.subscribers[i];
        subscriber.react(percent);
      }
    }

    return ProgressBar;
  })();

  let ProgressBarBlock = (function () {
    function ProgressBarBlock(element) {
      this.el = element;
      this.el.hide();
      this.hideTimer = null;
    }

    ProgressBarBlock.prototype.react = function (percent) {
      if (this.hideTimer) {
        clearTimeout(this.hideTimer);
        this.hideTimer = null;
      }

      if (percent === 100) {
        let that = this;
        this.hideTimer = setTimeout(function () {
          that.el.hide();
        }, 2000);
      }
    }

    ProgressBarBlock.prototype.start = function () {
      this.el.show();
    }

    ProgressBarBlock.prototype.setError = function (msg) {
      let errorEl = $(`<div id="progress-error-msg" style="color: red;">${msg}</div>`);
      this.el.append(errorEl);

      let that = this;
      setTimeout(function () {
        that.el.hide();
        errorEl.remove();
      }, 2000);
    }

    ProgressBarBlock.prototype.setNotification = function (msg, isErr, durationSec) {
      let notificationEl = $(`<div id="progress-notification-msg" style="color: ${isErr ? 'red' : 'green'};">${msg}</div>`);
      this.el.append(notificationEl);

      let that = this;
      setTimeout(function () {
        that.el.hide();
        notificationEl.remove();
      }, durationSec * 1000);
    }

    return ProgressBarBlock;
  })();

  let progress = new ProgressBar($("#progressBar"));
  let progressParentBlock = new ProgressBarBlock($("#progressBarBlock"));
  progress.addSubscriber(progressParentBlock);

});
