# IBD Notify
IBD Notify is a PHP package that can be used to easily add notifications to WordPress, or any other PHP project.

## Implementation

### Example Client Usage

```PHP
$factory = new IBD_Notify_Admin_Factory(); // instantiate a factory for admin notifications
$notification = $factory->make( $user_id, $title, $message, $args ); // create a new admin notification
$notification->save(); // save it
```

On the init hook, IBD Notify will check if there are any current notifications in the queue, and if so it sends them.

### Creating new Notifications
Developers should create two new classes.

1. A notification class that extends `IBD_Notify_Notification`, and implements all the necessary methods.
2. A notification factory that implements `IBD_Notify_Factory`, and implements all the necessary methods.

### Non WordPress usage
Out of the box, IBD Notify uses WordPress to save and retrieve notifications using `get_option()`, `update_option()`, and `delete_option()`.

All of this code is found in the `IBD_Notify_Database_WordPress` class. Outside of this class, there is no code that is specific to WordPress, besides the specific WordPress implementations.

Therefore to use this library in another environment, simply implement the `IBD_Notify_Database` interface as fit for your environment, and write a wrapper class to send the notifications.

## Future Development

### Notifications Type
List of notification types we support, and plan to support in the future.

#### Supported
- WordPress `admin_notices` admin notifications
- Growl Notifications
- Email Notifications using WordPress' `wp_mail()`

#### Planned
- WP Heartbeat implementation

### Changelog

#### 0.2
- Growl Notifications

#### 0.1
- Initial Release


## Copyright and License
Copyright © 2013. Iron Bound Designs. Licensed under the GPL 2 license.