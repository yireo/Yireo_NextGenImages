# Magento 2 module for NextGenImages
This module adds next-gen image support to Magento 2. Please note that this is a base extension for other extensions to use. See [Yireo_Webp2](https://github.com/yireo/Yireo_Webp2) for details.

**WARNING: If you are using Hyva and want to use the latest version of this module, remove  `Hyva_YireoNextGenImages`.**

## Development
This module features some settings and info panels in the Magento Store Configuration. But the major feature is a plugin on the `Layout` that scans for HTML `<img/>` tags to convert them into `<picture/>` tags with sources for alternative image formats.

A module `Foo_Bar` could add a `etc/di.xml` file to add a new convertor (a class implementing `\Yireo\NextGenImages\Convertor\ConvertorInterface`) to the convertor listing:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <type name="Yireo\NextGenImages\Convertor\ConvertorListing">
        <arguments>
            <argument name="convertors" xsi:type="array">
                <item name="foobar" xsi:type="object">Foo\Bar\Convertor</item>
            </argument>
        </arguments>
    </type>
</config>
```

# FAQ
### Can I skip lazy loading of images?
Yes, just add `fetchpriority="high"` to the image HTML of your choice.

### Can I call upon the convertor directly?
Yes, you can. You can inject the class `Yireo\NextGenImages\Image\ImageCollector` as a block argument via the XML layout (or alternatively use the LokiComponents `$viewModelFactory` or the Hyva `$viewModels` to instantiate it) and then call upon it as follows:

```php
$images = $this->imageCollector->collect($imageUrl);
```

# Roadmap
- Move CLI into separate module
- Move frontend into separate module
- Create GraphQL support
- Add more next gen image formats
    - JPEG 2000
    - HEIC
    - AVIF
    - JPEG XL
    - WebP2
