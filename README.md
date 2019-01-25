Challenger platform API class and examples for PHP
===

## Event tracking example

This code prepares a call to Challenger server on event happened to a client identified by {client_id}:

```php
include_once 'challenger.client.php';

$chall = new Challenger('{your.challenger.domain}');
$chall -> setKey('{secret_key}');
$chall -> setOwnerId({owner_id}); // Optional
$chall -> setClientId({client_id});
$chall -> addParam('multiple', '{multiple}'); // Optional

if($chall -> trackEvent({event_id}) === false){
    // Error happened. Check if servers are not down.
}
```

N.B. If ownerId is used, clientId is one way hashed internally to increase protection of personal client data.

## Delete client example

This code prepares a call to Challenger to delete particular client {client_id}:

```php
include_once 'challenger.client.php';

$chall = new Challenger('{your.challenger.domain}');
$chall -> setKey('{secret_key}');
$chall -> setClientId({client_id});
$resp = $chall -> deleteClient();

if($chall -> trackEvent({event_id}) === false){
    // Error happened. Check if servers are not down.
}
```

N.B. This function is accessible for in-house deployments only.

# Performance widgets
## Web version

Using the PHP helper functions provided with Challenger to get widget HTML is as easy as that:

```php
include_once 'challenger.client.php';

$chall = new Challenger('{your.challenger.domain}');
$chall -> setClientId({client_id});
$chall -> setKey('{secret_key}');
$chall -> addParam('expiration', '0000-00-00 00:00:00'); // Required
$chall -> addParam('name', 'John'); // Optional
$chall -> addParam('surname', 'Smith'); // Optional
$chall -> addParam('{param1}', '{value1}'); // Optional
$chall -> addParam('{param2}', '{value2}'); // Optional

$resp = $chall -> getWidgetHtml();

if($resp === false){
    // Error happened. Check if servers are not down.
}else{
    echo $resp; // Draw HTML snippet
}
```

N.B. This function is accessible for in-house deployments only.

## Mobile app version

This code creates an encrypted URL for mobile ready widget. It should be passed to mobile app and opened in WebView.

```php
include_once 'challenger.client.php';

$chall = new Challenger('{your.challenger.domain}');
$chall -> setClientId({client_id});
$chall -> setKey('{secret_key}');
$chall -> addParam('expiration', '0000-00-00 00:00:00'); // Required
$chall -> addParam('{param1}', '{value1}'); // Optional
$chall -> addParam('{param2}', '{value2}'); // Optional
$chall -> addParam('mobile', true); // Pass it to get mobile version of the widget

$widgetUrl = $chall -> getWidgetUrl();

if($widgetUrl === false){
    // Error happened. Check if servers are not down.
}else{
    echo $widgetUrl; // Return widget URL
}
```

N.B. This function is accessible for in-house deployments only.
