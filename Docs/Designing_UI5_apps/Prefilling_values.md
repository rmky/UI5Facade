# Prefilling values: filters with defaults, etc.

## Prefilling filters of data widgets (tables, etc.)

To prefill a filter on the landing/root page and get filtered data initially, the filter parameters need to be added to the URL.

For example, if the URL for the root page looks like this: 

```
.../vendor.app_alias.page_alias.html
```

the following will add prefill data to the URL:

```
.../vendor.app_alias.page_alias.html#/url_encoded_prefill_data
```

**IMPORTANT:** The `#` is indispensable!

**IMPORTANT:** The `url_encoded_prefill_data` is the JSON described below encoded via URL-encode **twice**!

`url_encoded_prefill_data` is a URL-encoded JSON with so-called route-parameters. These parameters are used internally in the UI5 router, so they are not easy to read. However, it is possible to add them manually to a URL, thus getting a lot of control about the initial content of the page. 

For the sake of prefill we are only going to need the `prefill` parameter. It's best explained by an example: the below JSON will prefill the filters over `APP` and `NAME` in the default object editor `Administration > Metamodel > Objects`.

```
{
	"prefill": 
	{
		"oId": "0x31350000000000000000000000000000",
		"filters": {
			"conditions": [
				{
					"expression": "APP",
					"value": "0x31000000000000000000000000000000"
				},
									   {
					"expression": "NAME",
					"value": "object"
				}
			]
		}
	}
}
```

The `oId` (object Id) is the UID of the object of the widget being filtered. In our example it's the UID of `exface.Core.OBJECT`. In other words it needs to be the UID of the object that is given in the `object_alias` property in the UXON definition of the widget. The UID of the object can be founde in `Administration > Metamodel > Objects`.

The `expression` of each filter condition needs to match an `attribute_alias` in the UXON filter defition of the widget. In the above example, the table has filters over the attributes `APP` and `NAME`.

You can easily apply the above example data to the page `exface.core.objects` to see how it works. Temporarily switch the template of the page to any UI5 template and paste the following URL in your browser:

```
http://yourdomain/exface.core.objects.html#/%257B%2522prefill%2522%253A%2520%257B%250A%2520%2520%2520%2520%2522oId%2522%253A%2520%25220x31350000000000000000000000000000%2522%252C%250A%2520%2520%2520%2520%2522filters%2522%253A%2520%257B%250A%2520%2520%2520%2520%2520%2520%2520%2520%2522conditions%2522%253A%2520%255B%250A%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%257B%250A%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2522expression%2522%253A%2520%2522APP%2522%252C%250A%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2522value%2522%253A%2520%25220x31000000000000000000000000000000%2522%250A%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%257D%252C%250A%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%257B%250A%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2522expression%2522%253A%2520%2522NAME%2522%252C%250A%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2522value%2522%253A%2520%2522object%2522%250A%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%2520%257D%250A%2520%2520%2520%2520%2520%2520%2520%2520%255D%250A%2520%2520%2520%2520%257D%250A%257D%257D
```