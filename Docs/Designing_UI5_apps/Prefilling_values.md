# Prefilling values: filters with defaults, etc.

## Prefilling filters

### URL structure

To prefill a filter on the landing/root page and get filtered data initially, the filter parameters need to be added to the URL.

When the URL for the root page looks like this: 

```
.../vendor.app_alias.page_alias.html
```

add the filter parameters like that:

```
.../vendor.app_alias.page_alias.html#/prefill_parameters
```

**IMPORTANT:** The `#` is indispensable!

**IMPORTANT:** It is necessary to **twice** URL encode the prefill parameters and add the result of the encoding to the URL like explained above.

### Prefill parameters structure

The prefill parameters structure to prefill filters need to have the following structure:

```
{
	"oId":"0x11ea498b9ab6d5feb0a1005056a124d1",
	"filters":{
		"conditions":[
			{
				"expression":"DeliveryId",
				"value":"123456"
			},
			{
				"expression":"PackageNumber",
				"value":"12345678"
			}
		]
	}
}
```

The `oId` needs to be the `UID` of the base object of the widget the filter is defined for. In other words it needs to be the `UID` of the object that is given in the `object_alias` property in the UXON definition of the widget.
The `expression` of a filter condition needs to match an `attribute_alias` in the UXON filter defition of the widget.

So with the example of filter parameters from above added to the URL example from above the URL to prefill filters would look like this (with removed whitespace and line breaks):

```
.../vendor.app_alias.page_alias.html#/%257B%2522oId%2522%253A%25220x11ea498b9ab6d5feb0a1005056a124d1%2522%252C%2522filters%2522%253A%257B%2522conditions%2522%253A%255B%257B%2522expression%2522%253A%2522DeliveryId%2522%252C%2522value%2522%253A%2522123456%2522%257D%252C%257B%2522expression%2522%253A%2522PackageNumber%2522%252C%2522value%2522%253A%252212345678%2522%257D%255D%257D%257D
```