Challenger platform API class and examples for PHP (version 2)
===

In example below:

 - `http://your.challenger.domain` - is the URL of your Challenger implementation
 - `secret_key` - a unique key provided by Challenger to encrypt data exchange
 - `owner_id` - a unique identifier provided by Challenger. Normally used to identify coalition partners. (optional)
 - `client_id` - the identifier of the client performing action
 - `event_id` - the identifier of the corresponding event in Challenger platform.
 - `multiple` - for quantifiable challenges (ex. get 1 point for every 1 euro spent). Provide value to multiple points with.

## Event tracking example

This code prepares a call to Challenger server on event happened to a client identified by {client_id}:

```php
include_once 'challenger.client-v2.php';

$chall = new Challenger('{http://your.challenger.domain}', '{secret_key}');
$chall -> setOwnerId('{owner_id}'); // Optional

// addEvent() can be called multiple times to send information in bulk
$chall -> addEvent('{client_id}', '{event_id}', [
	'multiple' => '{multiple}',
	'context' => '{context}'
]);

try{
	$res = $chall -> send();
} catch (Exception $e){
	// Error happened. Check if servers are not down.
	// ...
}

```

N.B. If ownerId is used, clientId is one way hashed internally to increase protection of personal client data.

## Delete client example

This code prepares a call to Challenger to delete particular client {client_id}:

```php
include_once 'challenger.client-v2.php';

$chall = new Challenger('{http://your.challenger.domain}', '{secret_key}');

try{
	$res = $chall -> deleteClient('{client_id}');
} catch (Exception $e){
	// Error happened. Check if servers are not down.
	// ...
}
```

N.B. This function is not accessible for coalitional partners.

# Performance widgets

In examples below:
 - `your.challenger.domain` - is the domain of your Challenger implementation (alternatively could be provided as URL).
 - `client_id` - the identifier of the client performing action
 - `secret_key` - a unique key provided by Challenger to encrypt data exchange
 - `param1`, `param2`, ... - optional parameters to pass to the widget (For example name of the client). List of parameters Challenger can map:
   - `expiration` (in format 0000-00-00 00:00:00) - required param
   - `name`
   - `surname`
   - `email`
   - `phone`
   - `lang` (2-digit language code. I.e. "en", "es", "lt", "hr")
   - `birthday` (in format 0000-00-00)
 - `value1`, `value2`,  ... - values of optional parameters.

Using the PHP helper functions provided with Challenger to get widget HTML is as easy as that:

```php
include_once 'challenger.client-v2.php';

$chall = new Challenger('{http://your.challenger.domain}', '{secret_key}');

// Option A: Get a widget HTML generated on server
try{
	$html = $chall -> getWidgetHtml('{client_id}', '{expiration}', [
		'name' => 'John', // Optional
		'surname' => 'Smith', // Optional
		'lang' => 'en', // Optional.
		'{param1}' => '{value1}', // Optional
		'{param2}' => '{value2}', // Optional
	]);
} catch (Exception $e){
	// Error happened. Check if servers are not down.
	// ...
}

// Option B: Get a widget URL generated on server
try{
	$url = $chall -> getWidgetUrl('{client_id}', '{expiration}', [
		'name' => 'John', // Optional
		'surname' => 'Smith', // Optional
		'lang' => 'en', // Optional.
		'{param1}' => '{value1}', // Optional
		'{param2}' => '{value2}', // Optional
	]);
} catch (Exception $e){
	// Error happened. Check if servers are not down.
	// ...
}

// Option C: Get and encrypted token to authorize the user and draw the widget on client-side
// For locally drawn widgets `getEncryptedData()` method could be used instead of `getWidgetHtml()`. Please refer:
// https://github.com/challenger-platform/challenger-widget#get-apiwidgetauthenticateuser for more information
try{
	$encrypted_data = $chall -> getEncryptedData('{client_id}', '{expiration}', [
		'name' => 'John', // Optional
		'surname' => 'Smith', // Optional
		'lang' => 'en', // Optional.
		'{param1}' => '{value1}', // Optional
		'{param2}' => '{value2}', // Optional
	]);
} catch (Exception $e){
	// Error happened. Check if servers are not down.
	// ...
}

```

N.B. This function is not accessible for coalitional partners.