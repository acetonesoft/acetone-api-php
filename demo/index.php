<?php
## Interactive demo ##

require __DIR__ . '/../src/autoload.php';

function call($image, $imageBg, $options): ?string
{
    $file = __DIR__ . '/.api-key.php';
    if (!is_file($file)) {
        die('ERROR: Missing file ' . $file);
    }
    $apiKey = include $file;
    if (!$apiKey || $apiKey === '00000000-0000-0000-0000-000000000000') {
        die('ERROR: You need to insert a real API key to ' . $file);
    }

    $acetone = new \AcetoneSoft\Acetone\AcetoneApi($apiKey);

    if (!empty($options['bg_mode']) && $options['bg_mode'] === 'image' && $imageBg) {
        return $acetone->backgroundReplace($image, $imageBg);
    }

    return $acetone->backgroundRemove($image, $options);
}

function sendForm(): array
{
    $result = [];
    if (!empty($_POST['src_fg'])) {

        $options = [];
        foreach ($_POST as $key => $val) {
            if (strpos($key, '_opt_') === 0) {
                if ($key === '_opt_bg_colors') {
                    $options['bg_colors'] = (array)$val;
                }
                else {
                    $options[substr($key, 5)] = $val;
                }
            }
            $result[$key] = $val;
        }

        $srcFgImage = $_POST['src_fg'];
        $fileFg = realpath(__DIR__ . '/' . $srcFgImage);

        $srcBgImage = $_POST['src_bg'] ?? '';

        foreach (glob($fileFg . '-res-*.*') as $file) {
            unlink($file);
        }
        $suffix = '-res-' . time() . '.' . ($options['format'] ?: 'png');
        $fileRes = $fileFg . $suffix;
        $image = file_get_contents($fileFg);
        if ($srcBgImage) {
            $fileBg = realpath(__DIR__ . '/' . $srcBgImage);
            $imageBg = file_get_contents($fileBg);
        }
        else {
            $imageBg = null;
        }

        $resImage = $srcFgImage . $suffix;

        $image = call($image, $imageBg, $options);
        if ($image && file_put_contents($fileRes, $image)) {
            $result['resImage'] = $resImage;
            $result['resImageFile'] = $fileRes;
        }
    }

    return $result;
}

function colorCss($color)
{
    if (preg_match('/(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $color, $m)) {
        return 'rgb(' . $m[1] . ',' . $m[2] . ',' . $m[3] . ')';
    }
    if (preg_match('/^#?([0-9-a-f]+)$/i', $color, $m)) {
        return '#' . $m[1];
    }
    return $color;
}

$data = [
    'srcFgImage' => 'pics/fg-1200x1200.jpg',
    'srcBgImage' => 'pics/bg-1332x850.jpg',
    'resImage' => null,

    '_opt_format' => 'png',
    '_opt_bg_mode' => 'none',
    '_opt_bg_colors' => ['255,202,134', '134,202,255'],
    '_opt_bg_gradient_vector' => 0,
    '_opt_bg_blur_factor' => 10,
    '_opt_size' => [-1, -1],
    '_opt_bg_gradient_centre' => [-1, -1],
];

if ($send = sendForm()) {
    $data = array_merge($data, $send);
}
if (!empty($data['resImageFile'])) {
    list($width, $height, $type, $attr) = getimagesize($data['resImageFile'],$image_info);
    $data['_opt_size'][0] = $width;
    $data['_opt_size'][1] = $height;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AcetoneAI Demo</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<h1>AcetoneAI Demo</h1>
<table class="pics">
    <tr>
        <td>
            <div class="pics-bg trans"><img src="<?=$data['srcBgImage'];?>" alt=""></div>
        </td>
        <td>
            <div class="pics-fg trans"><img src="<?=$data['srcFgImage'];?>" alt=""></div>
        </td>
        <td>
            <form action="" method="post">
                <input type="hidden" name="src_bg" value="<?=$data['srcBgImage']?>">
                <input type="hidden" name="src_fg" value="<?=$data['srcFgImage']?>">

                <label>bg_mode: <select name="_opt_bg_mode">
                        <option <?=(($data['_opt_bg_mode']==='none') ? 'selected' : '')?>>none</option>
                        <option <?=(($data['_opt_bg_mode']==='color') ? 'selected' : '')?>>color</option>
                        <option <?=(($data['_opt_bg_mode']==='radialgradient') ? 'selected' : '')?>>radialgradient</option>
                        <option <?=(($data['_opt_bg_mode']==='lineargradient') ? 'selected' : '')?>>lineargradient</option>
                        <option <?=(($data['_opt_bg_mode']==='blur') ? 'selected' : '')?>>blur</option>
                        <option <?=(($data['_opt_bg_mode']==='grayscale') ? 'selected' : '')?>>grayscale</option>
                        <option <?=(($data['_opt_bg_mode']==='image') ? 'selected' : '')?>>image</option>
                    </select></label>
                <br>
                <label>format: <select name="_opt_format">
                        <option value="png" <?=(($data['_opt_format']==='png') ? 'selected' : '')?>>png</option>
                        <option value="jpeg" <?=(($data['_opt_format']==='jpeg') ? 'selected' : '')?>>jpeg</option>
                        <option value="webp" <?=(($data['_opt_format']==='webp') ? 'selected' : '')?>>webp</option>
                    </select></label>
                <label>size:
                    <input type="text" name="_opt_size[]" value="<?=$data['_opt_size'][0]?>" style="width: 36px;">
                    x
                    <input type="text" name="_opt_size[]" value="<?=$data['_opt_size'][1]?>" style="width: 36px;">
                </label>

                <label>bg_colors (primary): <input type="text" name="_opt_bg_colors[]" value="<?=htmlspecialchars($data['_opt_bg_colors'][0])?>" style="width:90px;">
                    <span class="color" style="background-color: <?=colorCss($data['_opt_bg_colors'][0])?>"></span>
                </label>
                <label>bg_colors (secondary): <input type="text" name="_opt_bg_colors[]" value="<?=htmlspecialchars($data['_opt_bg_colors'][1])?>" style="width:90px;">
                    <span class="color" style="background-color: <?=colorCss($data['_opt_bg_colors'][1])?>"></span>
                </label>
                <label>bg_gradient_vector: <input type="text" name="_opt_bg_gradient_vector" value="<?=$data['_opt_bg_gradient_vector']?>" style="width: 36px;"></label>
                <label>bg_gradient_centre:
                    <input type="text" name="_opt_bg_gradient_centre[]" value="<?=$data['_opt_bg_gradient_centre'][0]?>" style="width: 36px;">
                    x
                    <input type="text" name="_opt_bg_gradient_centre[]" value="<?=$data['_opt_bg_gradient_centre'][1]?>" style="width: 36px;">
                </label>
                <label>bg_blur_factor: <input type="text" name="_opt_bg_blur_factor" value="<?=$data['_opt_bg_blur_factor']?>" style="width: 36px;"></label>
                <br>
                <button type="submit">ACTION &gt;&gt;</button>
            </form>
        </td>
        <td>
            <div>
                <div class="pics-res trans"><img src="<?=$data['resImage'];?>" alt=""></div>
                <?=$data['resImage'] ? basename($data['resImage']): '' ?>
            </div>
        </td>
    </tr>
</table>

</body>
</html>