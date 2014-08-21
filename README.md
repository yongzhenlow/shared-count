Shared Count
=======

> A PHP class that looks up the number of times a given URL has been shared on major social networks.

Requires PHP > 4 with curl extension.

Installation
-------

Download and include the PHP class file.

Usage
-------

To instantiate, you may use the class constructor
```php
$SharedCount = new SharedCount('http://blogger.com/my-blog/my-article');
```

Constructor
-----

The SharedCount class accepts a single parameter:

- $url _(string)_ - The URL to look up

Public Methods
-------

**set_url ( string $url )**
*Updates the current URL in the instance*

```php
$SharedCount->set_url('http://blogger.com/my-blog/my-article-2');
```
&nbsp;
**get_count ( string $social_network )**
*Get the shared count of the URL for a social_network*

Available values are 'all', 'pinterest', 'twitter', 'facebook_share', 'facebook_like', 'linkedin', 'googleplus'*

```php
echo $SharedCount->get_count('facebook_share');
```
&nbsp;
**get_sum_of ( array $social_network_arr )**
*Get the total shared counts of the URL for a given array of social networks*

Available values are 'pinterest', 'twitter', 'facebook_share', 'facebook_like', 'linkedin', 'googleplus'. If you want to get ALL available social networks, use `get_count('all')` instead.

```php
echo $SharedCount->get_sum_of(array('facebook_share', 'facebook_like'));
```
Examples
-------

To output the shared counts of all provided social networks
```php
echo $SharedCount->get_count('all');
```

Author
-------

- [Low Yong Zhen](mailto:yz@stargate.io)

License
-------

Copyright (c) 2014

Licensed under the [MIT License](http://yzlow.mit-license.org/).