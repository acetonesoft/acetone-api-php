<?php

namespace AcetoneSoft\Acetone;

use GuzzleHttp\Exception\GuzzleException;

class AcetoneApi
{
    const IMG_FIT_NONE = 'none';
    const IMG_FIT_COVER = 'cover';
    const IMG_FIT_CONTAIN = 'contain';
    const IMG_FIT_STRETCH = 'stretch';

    private \GuzzleHttp\Client $client;
    private float $time;

    private string $baseUrl = 'https://api.acetone.ai/api/v1/';
    private string $apiKey;


    protected string $imageBin;
    protected string $imageType;
    protected ?string $bgImageBin = null;
    protected ?string $logoImageBin = null;
    protected ?string $maskBin = null;
    protected ?string $targetImageBin = null;
    protected array $options = [];


    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->options['bg_mode'] = 'none';
    }

    protected function getClient()
    {
        if (empty($this->client)) {
            $this->client = new \GuzzleHttp\Client(['base_uri' => $this->baseUrl]);
        }

        return $this->client;
    }

    /**
     * @param string $action
     * @param array $fields
     * @param array|null $options
     *
     * @return string|null
     *
     * @throws GuzzleException
     */
    protected function action(string $action, array $fields, ?array $options = []): ?string
    {
        $client = $this->getClient();
        $requestParams = [
            'multipart' => $fields,
            'headers' => [
                'token' => $this->apiKey,
            ]
        ];
        if ($options) {
            // param=1&param=2
            //$requestParams['query'] = preg_replace('/%5B(\d+)%5D=/', '=', http_build_query($options));
            $requestParams['query'] = http_build_query($options);
        }

        $time = microtime(true);
        $response = $client->request('POST', $action, $requestParams);
        $this->time = microtime(true) - $time;

        return (string)$response->getBody();
    }

    /**
     * Generate a temporary filename for a multipart part
     *
     * @param string $name
     *
     * @return string
     */
    protected static function _tmpName(string $name): string
    {
        return time() . '-' . uniqid('') . '-' . $name . '.tmp';
    }

    /**
     * @param string $file
     * @param mixed $context
     * @param bool|null $isUrl
     * @param bool|null $bgImage
     *
     * @return string
     */
    protected static function _readFile(string $file, $context = null, ?bool $isUrl = false, ?bool $bgImage = false): string
    {
        $type = ($bgImage ? 'background ' : 'image ') . ($isUrl ? 'url' : 'file');
        if (!is_file($file)) {
            throw new AcetoneException(sprintf('Error: %s "%s" does not exist', $type, $file));
        }
        $image = file_get_contents($file, false, $context);
        if ($image === false) {
            throw new AcetoneException(sprintf('Error reading %s "%s"', $type, $file));
        }
        if (!$image) {
            throw new AcetoneException(sprintf('Error: %s "%s" is empty', $type, $file));
        }

        return $image;
    }

    /**
     * Color format are
     *  [255,255,255] - array of int
     *  "[255,255,255]" - array as string
     *  "#ffffff" - hex color value
     *  "#fff" - short hex ("#09f" equals to "#0099ff")
     *  "ffffff" - hex color value without #
     *  "fff" - short hex ("09f" equals to "0099ff")
     *
     * @param array|string $color
     *
     * @return string
     */
    protected static function _color($color): string
    {
        if (is_array($color)) {
            $color = array_values($color);
            $rgb = [$color[0], $color[1] ?? $color[0], $color[2] ?? $color[0]];
            $result = $rgb[0] . ',' . $rgb[1] . ',' . $rgb[2];
        }
        elseif (preg_match('/(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $color, $m)) {
            $result = $m[1] . ',' . $m[2] . ',' . $m[3];
        }
        elseif (preg_match('/^#?([0-9-a-f]+)$/i', $color, $m)) {
            if (strlen($m[1]) >= 6) {
                $hex = substr($m[1], 0, 6);
            }
            elseif (strlen($m[1]) < 3) {
                $hex = str_repeat($m[1][0], 6);
            }
            else {
                $hex = $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
            }
            $result = hexdec(substr($hex, 0, 2))
                . ',' . hexdec(substr($hex, 2, 2))
                . ',' . hexdec(substr($hex, 4, 2));
        }
        else {
            throw new AcetoneException('Incorrect color value');
        }

        return $result;
    }

    /**
     * Same input formats as _color(), but normalized to a hex string "#rrggbb".
     * The API expects hex for shadow_colour and the object-removal bg_color,
     * unlike bg_colors which takes "r,g,b".
     *
     * @param array|string $color
     *
     * @return string
     */
    protected static function _colorHex($color): string
    {
        [$r, $g, $b] = explode(',', self::_color($color));

        return sprintf('#%02x%02x%02x', (int)$r, (int)$g, (int)$b);
    }

    /**
     * @param string $image
     * @param array|null $options
     *
     * @return string|null
     *
     * @throws GuzzleException
     */
    public function backgroundRemove(string $image, ?array $options = []): ?string
    {
        return $this->fromString($image)
            ->options($options)
            ->get();
    }

    /**
     * @param string $image
     * @param string $color
     * @param array|null $options
     *
     * @return string|null
     *
     * @throws GuzzleException
     */
    public function backgroundColor(string $image, string $color, ?array $options = []): ?string
    {
        return $this->fromString($image)
            ->options($options)
            ->bgColor($color)
            ->get();
    }

    /**
     * @param string $image
     * @param array $colors
     * @param int $vector
     * @param array|null $options
     *
     * @return string|null
     *
     * @throws GuzzleException
     */
    public function backgroundGradient(string $image, array $colors, int $vector = 0, ?array $options = []): ?string
    {
        return $this->fromString($image)
            ->options($options)
            ->bgGradient($colors, $vector)
            ->get();
    }

    /**
     * @param string $image
     * @param array $colors
     * @param array $centre
     * @param array|null $options
     *
     * @return string|null
     *
     * @throws GuzzleException
     */
    public function backgroundRadialGradient(string $image, array $colors, array $center = [-1, -1], ?array $options = []): ?string
    {
        return $this->fromString($image)
            ->options($options)
            ->bgRadialGradient($colors, $center)
            ->get();
    }

    /**
     * @param string $image
     * @param int $blurFactor
     * @param array|null $options
     *
     * @return string|null
     *
     * @throws GuzzleException
     */
    public function backgroundBlur(string $image, int $blurFactor, ?array $options = []): ?string
    {
        return $this->fromString($image)
            ->options($options)
            ->bgBlur($blurFactor)
            ->get();
    }

    /**
     * @param string $image
     * @param array|null $options
     *
     * @return string|null
     *
     * @throws GuzzleException
     */
    public function backgroundGrayscale(string $image, ?array $options = []): ?string
    {
        return $this->fromString($image)
            ->options($options)
            ->bgGrayscale()
            ->get();
    }

    /**
     * @param string $image
     * @param string $bgImage
     * @param array|null $options
     *
     * @return string|null
     *
     * @throws GuzzleException
     */
    public function backgroundImage(string $image, string $bgImage, ?array $options = []): ?string
    {
        return $this->fromString($image)
            ->options($options)
            ->bgImage($bgImage)
            ->get();
    }

    /**
     * @param string $image
     * @param string $bgImage
     * @param array|null $options
     *
     * @return string|null
     *
     * @throws GuzzleException
     */
    public function backgroundReplace(string $image, string $bgImage, ?array $options = []): ?string
    {

        return $this->backgroundImage($image, $bgImage, $options);
    }

    /**
     * Remove an object from the image using a mask
     *
     * @param string $image
     * @param string $mask
     * @param array|null $options
     *
     * @return string|null
     *
     * @throws GuzzleException
     */
    public function objectRemove(string $image, string $mask, ?array $options = []): ?string
    {
        return $this->fromString($image)
            ->options($options)
            ->mask($mask)
            ->getObject();
    }

    /**
     * Enhance (upscale) an image
     *
     * @param string $image
     * @param string|null $targetImage
     * @param array|null $options
     *
     * @return string|null
     *
     * @throws GuzzleException
     */
    public function enhanceImage(string $image, ?string $targetImage = null, ?array $options = []): ?string
    {
        $this->fromString($image)->options($options);
        if ($targetImage !== null) {
            $this->targetImage($targetImage);
        }

        return $this->getEnhanced();
    }

    /**
     * @return float
     */
    public function getTime(): float
    {
        return $this->time;
    }

    /**
     * Get source image from URL
     *
     * @param string $url
     * @param $context
     *
     * @return $this
     */
    public function fromUrl(string $url, $context = null): AcetoneApi
    {
        $this->imageBin = self::_readFile($url, $context, true, false);

        return $this;
    }

    /**
     * Get source image from file
     *
     * @param string $file
     * @param $context
     *
     * @return $this
     */
    public function fromFile(string $file, $context = null): AcetoneApi
    {
        $this->imageBin = self::_readFile($file, $context, false, false);

        return $this;
    }

    /**
     * Get source image from binary string
     *
     * @param string $string
     *
     * @return $this
     */
    public function fromString(string $string): AcetoneApi
    {
        $this->imageBin = $string;

        return $this;
    }

    /**
     * Get source image from base64 string
     *
     * @param string $base64
     *
     * @return $this
     */
    public function fromBase64(string $base64): AcetoneApi
    {
        $this->imageBin = base64_decode($base64, true);

        return $this;
    }

    /**
     * Set options for API processing
     *
     * @param array $options
     *
     * @return $this
     */
    public function options(array $options): AcetoneApi
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Set target size
     *
     * @param int $with
     * @param int $height
     * @param string|null $fg_fit
     * @param string|null $bg_fit
     *
     * @return $this
     */
    public function size(int $with, int $height, string $fg_fit = null, string $bg_fit = null): AcetoneApi
    {
        $this->options['size'] = [$with, $height];
        $this->options['fg_fit'] = $fg_fit ?: 'none';
        $this->options['bg_image_fit'] = $bg_fit ?: 'none';

        return $this;
    }

    /**
     * Crop target image to foreground fit
     *
     * @return $this
     */
    public function crop(): AcetoneApi
    {
        $this->options['fg_opt'] = 'crop';

        return $this;
    }

    /**
     * Just remove background
     *
     * @return $this
     */
    public function bgRemove(): AcetoneApi
    {
        $this->options['bg_mode'] = 'none';

        return $this;
    }

    /**
     * Set background color
     *
     * @param $color
     *
     * @return $this
     */
    public function bgColor($color): AcetoneApi
    {
        $this->options['bg_mode'] = 'color';
        $this->options['bg_colors'] = [self::_color($color)];

        return $this;
    }

    /**
     * Set linear gradient mode and options
     *
     * @param array $colors
     * @param int|null $vector
     *
     * @return $this
     */
    public function bgGradient(array $colors, ?int $vector = 0): AcetoneApi
    {
        $this->options['bg_mode'] = 'lineargradient';
        $this->options['bg_colors'] = [self::_color($colors[0]), self::_color($colors[1])];
        $this->options['bg_gradient_vector'] = $vector;

        return $this;
    }

    /**
     * Set radial gradient mode and options
     *
     * @param array $colors
     * @param array|null $center
     *
     * @return $this
     */
    public function bgRadialGradient(array $colors, ?array $center = [-1, -1]): AcetoneApi
    {
        $this->options['bg_mode'] = 'radialgradient';
        $this->options['bg_colors'] = [self::_color($colors[0]), self::_color($colors[1])];
        $this->options['bg_gradient_centre'] = $center;

        return $this;
    }

    /**
     * Set blur mode and options
     *
     * @param int $blurFactor
     *
     * @return $this
     */
    public function bgBlur(int $blurFactor): AcetoneApi
    {
        $this->options['bg_mode'] = 'blur';
        $this->options['bg_blur_factor'] = $blurFactor;

        return $this;
    }

    /**
     * Set grayscale mode
     *
     * @return $this
     */
    public function bgGrayscale(): AcetoneApi
    {
        $this->options['bg_mode'] = 'grayscale';

        return $this;
    }

    /**
     * Set background image from binary string
     *
     * @param string $bgImage
     *
     * @return $this
     */
    public function bgImage(string $bgImage): AcetoneApi
    {
        $this->bgImageBin = $bgImage;
        $this->options['bg_mode'] = 'image';

        return $this;
    }

    /**
     * Set background image from file
     *
     * @param string $bgImageFile
     * @param mixed $context
     *
     * @return $this
     */
    public function bgImageFile(string $bgImageFile, $context = null): AcetoneApi
    {
        $this->bgImageBin = self::_readFile($bgImageFile, $context, false, true);
        $this->options['bg_mode'] = 'image';

        return $this;
    }

    /**
     * Set output quality (1-100)
     *
     * @param int $quality
     *
     * @return $this
     */
    public function quality(int $quality): AcetoneApi
    {
        $this->options['quality'] = $quality;

        return $this;
    }

    /**
     * Toggle exact cutout mode
     *
     * @param bool $exact
     *
     * @return $this
     */
    public function exact(bool $exact = true): AcetoneApi
    {
        $this->options['exact'] = $exact;

        return $this;
    }

    /**
     * Add a drop shadow under the foreground
     *
     * @param int|null $power
     * @param int|null $offsetX
     * @param int|null $offsetY
     * @param array|string $color
     *
     * @return $this
     */
    public function shadow(?int $power = 50, ?int $offsetX = 15, ?int $offsetY = 15, $color = '#999999'): AcetoneApi
    {
        $this->options['add_shadow'] = true;
        $this->options['shadow_power'] = $power;
        $this->options['shadow_offset_x'] = $offsetX;
        $this->options['shadow_offset_y'] = $offsetY;
        $this->options['shadow_colour'] = self::_colorHex($color);

        return $this;
    }

    /**
     * Overlay a logo image (binary string)
     *
     * @param string $logoImage
     * @param array $options Raw logo_* options (logo_angle, logo_position, logo_opacity, logo_size, logo_padding, logo_correct)
     *
     * @return $this
     */
    public function logoImage(string $logoImage, array $options = []): AcetoneApi
    {
        $this->logoImageBin = $logoImage;
        $this->options['add_logo'] = true;
        if ($options) {
            $this->options = array_merge($this->options, $options);
        }

        return $this;
    }

    /**
     * Overlay a logo image from file
     *
     * @param string $logoImageFile
     * @param array $options Raw logo_* options
     * @param mixed $context
     *
     * @return $this
     */
    public function logoImageFile(string $logoImageFile, array $options = [], $context = null): AcetoneApi
    {
        return $this->logoImage(self::_readFile($logoImageFile, $context, false, true), $options);
    }

    /**
     * Set object mask from binary string (for object removal)
     *
     * @param string $mask
     *
     * @return $this
     */
    public function mask(string $mask): AcetoneApi
    {
        $this->maskBin = $mask;

        return $this;
    }

    /**
     * Set object mask from file (for object removal)
     *
     * @param string $maskFile
     * @param mixed $context
     *
     * @return $this
     */
    public function maskFile(string $maskFile, $context = null): AcetoneApi
    {
        $this->maskBin = self::_readFile($maskFile, $context, false, true);

        return $this;
    }

    /**
     * Set the fill color for the removed object area (object removal)
     *
     * @param array|string $color
     *
     * @return $this
     */
    public function objectBgColor($color): AcetoneApi
    {
        $this->options['bg_color'] = self::_colorHex($color);

        return $this;
    }

    /**
     * Set the target image from binary string (image enhance)
     *
     * @param string $targetImage
     *
     * @return $this
     */
    public function targetImage(string $targetImage): AcetoneApi
    {
        $this->targetImageBin = $targetImage;

        return $this;
    }

    /**
     * Set the target image from file (image enhance)
     *
     * @param string $targetImageFile
     * @param mixed $context
     *
     * @return $this
     */
    public function targetImageFile(string $targetImageFile, $context = null): AcetoneApi
    {
        $this->targetImageBin = self::_readFile($targetImageFile, $context, false, true);

        return $this;
    }

    /**
     * Set enhance mode
     *
     * @param string $mode
     *
     * @return $this
     */
    public function enhanceMode(string $mode = 'solo'): AcetoneApi
    {
        $this->options['enhance_mode'] = $mode;

        return $this;
    }

    /**
     * Get result image
     *
     * @param string|null $format
     *
     * @return string
     *
     * @throws GuzzleException
     * @throws AcetoneException
     */
    public function get(?string $format = null): ?string
    {
        return $this->_run('remove/background', [
            'bgimage'   => ($this->options['bg_mode'] === 'image') ? $this->bgImageBin : null,
            'logoimage' => $this->logoImageBin,
        ], $format);
    }

    /**
     * Get result of object removal (requires a mask)
     *
     * @param string|null $format
     *
     * @return string|null
     *
     * @throws GuzzleException
     * @throws AcetoneException
     */
    public function getObject(?string $format = null): ?string
    {
        if (empty($this->maskBin)) {
            throw new AcetoneException('Mask image not define');
        }

        return $this->_run('remove/object', ['mask' => $this->maskBin], $format);
    }

    /**
     * Get result of image enhance
     *
     * @param string|null $format
     *
     * @return string|null
     *
     * @throws GuzzleException
     * @throws AcetoneException
     */
    public function getEnhanced(?string $format = null): ?string
    {
        return $this->_run('enhance/image', ['target_image' => $this->targetImageBin], $format);
    }

    /**
     * Build the multipart request and call the given endpoint
     *
     * @param string $endpoint
     * @param array $extraFiles Map of part name => binary (skipped when binary is null/empty)
     * @param string|null $format
     *
     * @return string|null
     *
     * @throws GuzzleException
     * @throws AcetoneException
     */
    protected function _run(string $endpoint, array $extraFiles, ?string $format = null): ?string
    {
        if (empty($this->imageBin)) {
            throw new AcetoneException('Source image not define');
        }
        $fields = [
            [
                'name'     => 'image',
                'contents' => $this->imageBin,
                'filename' => self::_tmpName('image'),
            ],
        ];
        foreach ($extraFiles as $name => $contents) {
            if ($contents) {
                $fields[] = [
                    'name'     => $name,
                    'contents' => $contents,
                    'filename' => self::_tmpName($name),
                ];
            }
        }
        if ($format) {
            $format = strtolower($format);
            if (!in_array($format, ['jpg', 'jpeg', 'png', 'webp'])) {
                throw new AcetoneException(sprintf('Wrong output file format "%s"', $format));
            }
            $this->options['format'] = ($format === 'jpg') ? 'jpeg' : $format;
        }

        return $this->action($endpoint, $fields, $this->options);
    }

    /**
     * Save result image to file
     *
     * @param $filename
     *
     * @return int
     *
     * @throws GuzzleException
     * @throws AcetoneException
     */
    public function save($filename): int
    {
        return $this->_put($filename, $this->get(pathinfo($filename, PATHINFO_EXTENSION)));
    }

    /**
     * Save object-removal result to file
     *
     * @param $filename
     *
     * @return int
     *
     * @throws GuzzleException
     * @throws AcetoneException
     */
    public function saveObject($filename): int
    {
        return $this->_put($filename, $this->getObject(pathinfo($filename, PATHINFO_EXTENSION)));
    }

    /**
     * Save enhance result to file
     *
     * @param $filename
     *
     * @return int
     *
     * @throws GuzzleException
     * @throws AcetoneException
     */
    public function saveEnhanced($filename): int
    {
        return $this->_put($filename, $this->getEnhanced(pathinfo($filename, PATHINFO_EXTENSION)));
    }

    /**
     * Write result image to a file (creating the directory if needed)
     *
     * @param string $filename
     * @param string|null $image
     *
     * @return int
     *
     * @throws AcetoneException
     */
    protected function _put(string $filename, ?string $image): int
    {
        $dir = dirname($filename);
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            throw new AcetoneException(sprintf('Directory creation error "%s"', $dir));
        }

        $result = file_put_contents($filename, $image);
        if (!$result) {
            throw new AcetoneException(sprintf('Error writing to file "%s"', $filename));
        }

        return $result;
    }
}
