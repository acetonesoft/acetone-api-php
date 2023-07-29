<?php

namespace avadim\Acetone;

use GuzzleHttp\Exception\GuzzleException;

class AcetoneApi
{
    const IMG_FIT_NONE = 'none';
    const IMG_FIT_COVER = 'сover';
    const IMG_FIT_CONTAIN = 'сontain';
    const IMG_FIT_STRETCH = 'stretch';

    private \GuzzleHttp\Client $client;
    private float $time;

    private string $baseUrl = 'https://api.acetone.ai/api/v1/';
    private string $apiKey;


    protected string $imageBin;
    protected string $imageType;
    protected ?string $bgImageBin = null;
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
            $requestParams['query'] = preg_replace('/%5B(\d+)%5D=/', '=', http_build_query($options));
        }

        $time = microtime(true);
        $response = $client->request('POST', $action, $requestParams);
        $this->time = microtime(true) - $time;

        return (string)$response->getBody();
    }

    /**
     * @return string[]
     */
    protected static function _tmpNames(): array
    {
        $id = time() . '-' . uniqid('');

        return [$id . '-fg.tmp', $id . '-bg.tmp'];
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
        if (empty($this->imageBin)) {
            throw new AcetoneException('Source image not define');
        }
        $names = $this->_tmpNames();
        $fields = [
            [
                'name'     => 'image',
                'contents' => $this->imageBin,
                'filename' => $names[0],
            ],
        ];
        if ($this->options['bg_mode'] === 'image' && $this->bgImageBin) {
            $fields[] = [
                'name'     => 'bgimage',
                'contents' => $this->bgImageBin,
                'filename' => $names[1],
            ];
        }
        if ($format) {
            $format = strtolower($format);
            if (!in_array($format, ['jpg', 'jpeg', 'png', 'webp'])) {
                throw new AcetoneException(sprintf('Wrong output file format "%s"', $format));
            }
            $this->options['extension'] = ($format === 'jpg') ? 'jpeg' : $format;
        }

        return $this->action('remove/background', $fields, $this->options);
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
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $image = $this->get($extension);
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
