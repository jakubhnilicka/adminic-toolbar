(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.adminicToolbar = {
    attach: function (context) {
      var $toolbarSecondary = $('.toolbar__secondary');
      var $body = $('body');
      var compactBreakpoint = window.matchMedia("only screen and (min-width: 60em)");
      compactBreakpoint.addListener(setBodyPadding);

      $('.nano').nanoScroller();


      var activeTab = drupalSettings.adminic_toolbar.active_tab;
      if (activeTab) {
        $('#tab--' + activeTab).addClass('active');
        $('#toolbar-' + activeTab).addClass('active');
      }
      setBodyPadding(compactBreakpoint);

      var activeLink = drupalSettings.adminic_toolbar.active_link;
      if (activeLink) {
        $('.toolbar_links a[href="' + activeLink + '"]').addClass('active');
      }


      $('.tab').on('click', function (e) {
        // If cmd + click go directly to the href.
        if (e.metaKey) {
          e.preventDefault();
          window.location.replace($(this).attr('href'));
          return true;
        }

        var $tab = $(this);
        var tabId = $tab.attr('id');
        var tabKey = tabId.substring(5);
        var $sectionWrapper = $('#toolbar-' + tabKey);

        $('.tab').removeClass('active');
        $tab.addClass('active');
        $('.wrapper.active').css('z-index', 999).removeClass('active');

        if ($sectionWrapper[0] !== undefined) {
          e.preventDefault();
          $sectionWrapper.css('z-index', 999).addClass('active');
          showSecondaryToolbar();
        }
        else {
          hideSecondaryToolbar()
        }

      });

      $('.toolbar__header .close').on('click', function (e) {
        hideSecondaryToolbar();
      });

      function showSecondaryToolbar() {
        var $tabActive = $('.tab.active');

        if ($tabActive.length > 0) {
          var tabId = $tabActive.attr('id');
          var tabKey = tabId.substring(5);
          var $sectionWrapper = $('#toolbar-' + tabKey);
          if ($sectionWrapper[0] !== undefined) {
            $sectionWrapper.addClass('active');
            $toolbarSecondary.show();
          }
          else {
            hideSecondaryToolbar();
          }
        }
        setBodyPadding(compactBreakpoint);
      }

      function hideSecondaryToolbar() {
        $toolbarSecondary.hide();
        $('.wrapper').removeClass('active');
        $body.removeClass('adminic-toolbar-secondary');
      }

      function setBodyPadding(compactBreakpoint) {
        if (compactBreakpoint.matches) {
          var $tabActive = $('.tab.active');
          if ($tabActive.length > 0) {
            var tabId = $tabActive.attr('id');
            var tabKey = tabId.substring(5);
            var $sectionWrapper = $('#toolbar-' + tabKey);
            if ($sectionWrapper[0] !== undefined && $sectionWrapper.hasClass) {
              $sectionWrapper.addClass('active');
              $body.addClass('adminic-toolbar-secondary');
            }
          }
        }
        else {
          $body.removeClass('adminic-toolbar-secondary');
        }
      }
      $('.toolbar-info').on('click', function(e) {
        e.preventDefault();
        var output = [];
        var routeName = drupalSettings.adminic_toolbar.route_name;
        var routeParameters = drupalSettings.adminic_toolbar.route_parameters;

        output.push("secondary_section_id: 'CHANGE'");
        output.push("route_name: '" +  routeName + '"');
        if (routeParameters.length > 0) {
          var routeParametersFormated = routeParameters.map(outputRouteParameters);
          output.push("route_parameters: {" + routeParametersFormated.join(', ') + "}");
        }

        var dialogOutput = '- {' + output.join(', ') + '}';

        var $myRouteConfiguration = $('<div><p id="link_definition_info">Paste this definition in your *.toolbyar.yml file onto "secondary_sections_links"</p><div id="link_definition">' +  dialogOutput + '</div></div>');
        Drupal.dialog($myRouteConfiguration, {
          buttons: [{
            title: Drupal.t('Links output'),
            text: Drupal.t('Close'),
            click: function() {
              $(this).dialog('close');
            }
          }]
        }).showModal();

        function outputRouteParameters(parameter, index) {
          return parameter + ": 'CHANGE'";
        }
      })

    }
  };

}(jQuery, Drupal, drupalSettings));
