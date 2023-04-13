# Extension:SimpleSAMLphp

## Configuration (since 5.0)

Add to the plugin to `$wgPluggableAuth_Config`:

```php
$wgPluggableAuth_Config['Log in using my SAML'] = [
	'plugin' => 'SimpleSAMLphp',
	'data' => [
		'authSourceId' => 'default-sp',
		'usernameAttribute' => 'username',
		'realNameAttribute' => 'name',
		'emailAttribute' => 'email'
	]
];
```

### Fields for `data`
| Field name                                    | Default       | Description                      |
| --------------------------------------------- | ------------- | ---------------------------------|
| `authSourceId`                                | (mandatory)   | 
| `usernameAttribute`                           | (mandatory)   |
| `realNameAttribute`                           | (mandatory)   |
| `emailAttribute`                              | (mandatory)   |
| `userinfoProviders`                           | <code>[<br>&nbsp;&nbsp;'username' => 'username',<br>&nbsp;&nbsp;'realname' => 'realname',<br>&nbsp;&nbsp;'email' => 'email'<br>]</code> |

## User info providers

### Example: "Case sensitive username"

By default the extension will normalize the value for `username` to lowercase. If this is not desired, one can simply use the `rawusername` provider. E.g.

```php
$wgPluggableAuth_Config['Log in using my SAML'] = [
	'plugin' => 'SimpleSAMLphp',
	'data' => [
		...
		'userinfoProviders' => [
			'username' => 'rawusername'
		],
		...
	]
];
```

### Define custom user info provider

If you want to modify any of the fields `username`, `realname` or `email` before login, you can
configure a custom callback for `$wgSimpleSAMLphp_MandatoryUserInfoProviders`. The factory
method has the following signature:

```php
    factoryCallback(): MediaWiki\Extension\SimpleSAMLphp\IUserInfoProvider
```

For simple usecases one can use `MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\GenericCallback`:

```php
    $wgSimpleSAMLphp_MandatoryUserInfoProviders['username'] = function() {
        return new MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\GenericCallback( function( $attributes, $config ) {
            if ( !isset( $attributes['mail'] ) ) {
                throw new Exception( 'missing email address' );
            }
            $parts = explode( '@', $attributes['mail'][0] );
            return strtolower( $parts[0] );
        } );
    };
```
