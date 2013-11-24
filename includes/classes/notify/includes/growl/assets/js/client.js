/**
 *
 * @package Notifications
 * @subpackage Growl
 * @since 0.2
 */
jQuery( document ).ready( function ( $ ) {
    var notification = $.parseJSON( ibd_notify.notification );

    setTimeout( function () {
        add_gritter( notification );
    }, 2000 );

    function add_gritter( notification ) {
        $.gritter.add( {
            // (string | mandatory) the heading of the notification
            title: notification.title,
            // (string | mandatory) the text inside the notification
            text: notification.message,
            // (bool | optional) if you want it to fade out on its own or just sit there
            sticky: notification.sticky,
            // (int | optional) the time you want it to be alive for before fading out (milliseconds)
            time: notification.time,
            // (string | optional) the class name you want to apply directly to the notification for custom styling
            class_name: notification.class,
            // (function | optional) function called after it closes
            after_close: function () {
                ajax_complete();
            }
        } );
    }

    function ajax_complete() {
        var data = {
            action: notification.ajax,
            complete: true,
            notification: notification
        };

        $.post( ibd_notify.ajax_url, data, function ( response ) {
            response = $.parseJSON( response );

            if ( response.complete == false )
                add_gritter( response.notification );
        } );
    }
} );