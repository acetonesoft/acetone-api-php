# PHP Client for API of AceTone.ai

**English** | [Русский](README.md)

You can programmatically remove backgrounds from your images using the API of AceTone.ai.
You can also fill the background with a solid color, a linear or radial gradient, blur or
grayscale it, or replace it with another image. In addition the client covers object removal
by a mask and image enhance/upscale.

**IMPORTANT:** You need to register an account and obtain your API key at https://acetone.ai/

![cover](cover.jpg)

## Installation

Install via composer

```
composer require acetonesoft/acetone-api-php
```

Requires PHP >= 7.4 and depends on `guzzlehttp/guzzle`.

## Quick Start

```php
use AcetoneSoft\Acetone\AcetoneApi;

$acetone = new AcetoneApi($apiKey);
$acetone->fromFile($sourceImageFile)->save($targetImageFile);
```

## Two API Styles

The client exposes two interchangeable styles:

1. **Fluent builder** — set the source with `fromFile()` / `fromUrl()` / `fromString()` /
   `fromBase64()`, chain modifiers, then call a terminal getter (`get()` / `getObject()` /
   `getEnhanced()`) or saver (`save()` / `saveObject()` / `saveEnhanced()`).
2. **One-shot methods** — take the source image as a **binary string** and return the result
   as a binary string (see [One-shot Methods](#one-shot-methods)).

No HTTP request is made until a terminal getter/saver is called.

## Advanced Usage

### Sources

The image can be obtained from a link, from a file, from a binary string, or from a base64-string.

```php
// Get source image from URL
$acetone->fromUrl($imageUrl)->save($outFile);

// Get source image from file
$acetone->fromFile($imageFile)->save($outFile);

// Get image from binary string
$acetone->fromString($imageString)->save($outFile);

// Get image as a base64-string
$acetone->fromBase64($base64)->save($outFile);
```

### Manipulations With Background

```php
// Just remove background
$acetone->fromFile($imageFile)->bgRemove()->save($outFile);

// Set background color (hex with or without "#", short hex, or an RGB array)
$acetone->fromFile($imageFile)->bgColor('f00')->save($outFile);
$acetone->fromFile($imageFile)->bgColor('#f00')->save($outFile);
$acetone->fromFile($imageFile)->bgColor([255, 0, 0])->save($outFile);

// Fill background with a linear gradient (second arg is the gradient vector angle)
$colors = ['f00', '33c'];
$vector = -30;
$acetone->fromFile($imageFile)->bgGradient($colors, $vector)->save($outFile);

// Fill background with a radial gradient (second arg is the center [x, y])
$colors = ['f00', '33c'];
$center = [120, 240];
$acetone->fromFile($imageFile)->bgRadialGradient($colors, $center)->save($outFile);

// Blur the background (blur factor)
$acetone->fromFile($imageFile)->bgBlur(20)->save($outFile);

// Set grayscale mode of background
$acetone->fromFile($imageFile)->bgGrayscale()->save($outFile);

// Set background image from a binary string
$bgImage = file_get_contents('path/to/new/background');
$acetone->fromFile($imageFile)->bgImage($bgImage)->save($outFile);

// Or set background image from a file
$acetone->fromFile($imageFile)->bgImageFile('path/to/new/background')->save($outFile);
```

### Resize Result Image

`size(width, height, $fgFit, $bgFit)`. Fit constants: `IMG_FIT_NONE`, `IMG_FIT_COVER`,
`IMG_FIT_CONTAIN`, `IMG_FIT_STRETCH`.

```php
$acetone->fromFile($imageFile)
    ->size(800, 600, AcetoneApi::IMG_FIT_COVER, AcetoneApi::IMG_FIT_COVER)
    ->bgImageFile($imageFileBg)
    ->save($outFile);
```

### Crop Result Image to Foreground Fit

```php
$acetone->fromFile($imageFile)
    ->crop()
    ->get();
```

### Output Quality and Exact Cutout

```php
// Set output quality (1-100) and toggle the exact-cutout mode
$acetone->fromFile($imageFile)
    ->quality(90)
    ->exact(true)
    ->save($outFile);
```

### Drop Shadow

```php
// Add a drop shadow under the foreground
// shadow(power, offsetX, offsetY, color) — color must be a hex value
$acetone->fromFile($imageFile)
    ->shadow(50, 15, 15, '#999999')
    ->save($outFile);
```

### Logo Overlay

```php
// Overlay a logo from a file, with raw logo_* options
$acetone->fromFile($imageFile)
    ->logoImageFile($logoFile, ['logo_position' => 0, 'logo_size' => 10, 'logo_opacity' => 1])
    ->save($outFile);

// Logo from a binary string
$acetone->fromFile($imageFile)
    ->logoImage($logoBinary)
    ->save($outFile);
```

Supported raw logo options: `logo_angle`, `logo_position`, `logo_opacity`, `logo_size`,
`logo_padding`, `logo_correct`.

### Object Removal

Remove an object described by a mask (white = area to remove).

```php
// Fluent style — the fill color must be a hex value
$acetone->fromFile($imageFile)
    ->maskFile($maskFile)
    ->objectBgColor('#ffffff')
    ->saveObject($outFile);

// Mask can also be passed as a binary string
$acetone->fromFile($imageFile)
    ->mask(file_get_contents($maskFile))
    ->getObject('png');

// One-shot style (takes binary strings)
$result = $acetone->objectRemove(
    file_get_contents($imageFile),
    file_get_contents($maskFile)
);
```

### Image Enhance

Enhance/upscale an image. An optional target image can be supplied to enhance towards.

```php
// Fluent style
$acetone->fromFile($imageFile)
    ->enhanceMode('solo')
    ->saveEnhanced($outFile);

// With a target image (from a file or a binary string)
$acetone->fromFile($imageFile)
    ->targetImageFile($targetFile)
    ->saveEnhanced($outFile);

// One-shot style
$result = $acetone->enhanceImage(file_get_contents($imageFile));

// Get result as a binary string
$imageStr = $acetone->fromFile($imageFile)->getEnhanced('png');
```

### Get Result Image as a Binary String

```php
// You can define the output format — png, jpg or webp (png is default)
$imageStr = $acetone->fromFile($imageFile)->get('webp');
$im = imagecreatefromstring($imageStr);
// Some manipulations
imagejpeg($im, 'image.jpg');
```

### Arbitrary API Options

Use `options()` as an escape hatch to pass any raw API parameters that do not have a dedicated
method. They are merged into the request query string.

```php
$acetone->fromFile($imageFile)
    ->options(['some_api_param' => 'value'])
    ->save($outFile);
```

### Request Time

`getTime()` returns the elapsed time (in seconds) of the last API call.

```php
$acetone->fromFile($imageFile)->save($outFile);
echo $acetone->getTime(); // e.g. 1.234
```

## One-shot Methods

These take the source image (and, where relevant, the extra image/mask) as **binary strings**
and return the result as a binary string:

```php
$src = file_get_contents($imageFile);

$acetone->backgroundRemove($src);
$acetone->backgroundColor($src, '#ff0000');
$acetone->backgroundGradient($src, ['f00', '33c'], -30);        // linear, vector angle
$acetone->backgroundRadialGradient($src, ['f00', '33c'], [120, 240]); // radial, center
$acetone->backgroundBlur($src, 20);
$acetone->backgroundGrayscale($src);
$acetone->backgroundImage($src, file_get_contents($bgFile));
$acetone->backgroundReplace($src, file_get_contents($bgFile));  // alias of backgroundImage
$acetone->objectRemove($src, file_get_contents($maskFile));
$acetone->enhanceImage($src);
```

Each one-shot method also accepts a trailing `$options` array forwarded to `options()`.
