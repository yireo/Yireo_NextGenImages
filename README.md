# Magento 2 module for NextGenImages

<!-- badges.specs.start -->
![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-next-gen-images)
<!-- badges.specs.end -->

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

## Current status

<!-- badges.test.start -->
![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_NextGenImages/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_NextGenImages/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_NextGenImages/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_NextGenImages/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_NextGenImages/compile.yml?label=compile)
<!-- badges.test.end -->
