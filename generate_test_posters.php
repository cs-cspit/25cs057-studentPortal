<?php
/**
 * Generates verified seed posters and test files for Practical 12
 */

$postersDir = __DIR__ . '/uploads/event_posters';
$testFilesDir = __DIR__ . '/test_files';

if (!is_dir($postersDir)) mkdir($postersDir, 0777, true);
if (!is_dir($testFilesDir)) mkdir($testFilesDir, 0777, true);

function createPosterImage($filepath, $title, $category, $bgColor1, $bgColor2, $textColor, $width = 600, $height = 400, $padBytes = 0) {
    $img = imagecreatetruecolor($width, $height);
    
    // Draw gradient background
    for ($y = 0; $y < $height; $y++) {
        $ratio = $y / $height;
        $r = (int)($bgColor1[0] * (1 - $ratio) + $bgColor2[0] * $ratio);
        $g = (int)($bgColor1[1] * (1 - $ratio) + $bgColor2[1] * $ratio);
        $b = (int)($bgColor1[2] * (1 - $ratio) + $bgColor2[2] * $ratio);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $width, $y, $color);
    }

    // Grid lines / watermark pattern
    $gridColor = imagecolorallocatealpha($img, 255, 255, 255, 115);
    for ($x = 0; $x < $width; $x += 40) {
        imageline($img, $x, 0, $x, $height, $gridColor);
    }
    for ($y = 0; $y < $height; $y += 40) {
        imageline($img, 0, $y, $width, $y, $gridColor);
    }

    // Card border
    $border = imagecolorallocate($img, 255, 255, 255);
    imagesetthickness($img, 3);
    imagerectangle($img, 15, 15, $width - 16, $height - 16, $border);

    // Text details
    $txtCol = imagecolorallocate($img, $textColor[0], $textColor[1], $textColor[2]);
    $accentCol = imagecolorallocate($img, 255, 215, 0); // Gold
    $white = imagecolorallocate($img, 255, 255, 255);
    $tagBg = imagecolorallocate($img, 20, 30, 50);

    // Header badge
    imagefilledrectangle($img, 30, 30, 260, 65, $tagBg);
    imagestring($img, 5, 45, 40, "STUDENTHUB CAMPUS", $accentCol);

    // Category
    imagestring($img, 4, 35, 95, strtoupper($category), $accentCol);

    // Title (split if long)
    $lines = explode("\n", wordwrap($title, 32, "\n"));
    $yOffset = 135;
    foreach ($lines as $line) {
        imagestring($img, 5, 35, $yOffset, $line, $white);
        $yOffset += 28;
    }

    // Footer info
    imagestring($img, 4, 35, $height - 65, "CSPIT - CHARUSAT UNIVERSITY", $white);
    imagestring($img, 3, 35, $height - 40, "Official Campus Event Poster | Verified Entry", $accentCol);

    imagepng($img, $filepath, 6);
    imagedestroy($img);

    // If padding requested (for creating the exact file size, e.g. 150KB or 3.2MB)
    if ($padBytes > 0) {
        $curSize = filesize($filepath);
        if ($curSize < $padBytes) {
            $f = fopen($filepath, 'ab');
            $zeros = str_repeat("\x00", $padBytes - $curSize);
            fwrite($f, $zeros);
            fclose($f);
        }
    }
}

// 1. Generate Seed Event Posters (Events 1 to 5)
createPosterImage("$postersDir/poster_hacknova.png", "HackNova 2026\nNational 24-Hr Codefest", "Coding Competition", [26, 35, 126], [74, 20, 140], [255, 255, 255]);
createPosterImage("$postersDir/poster_deeplearning.png", "Deep Learning Workshop\nNeural Nets & PyTorch", "Technical Workshop", [13, 71, 161], [0, 150, 136], [255, 255, 255]);
createPosterImage("$postersDir/poster_cloudnative.png", "Cloud Native DevOps\n& Kubernetes Summit", "Industry Seminar", [1, 87, 155], [0, 96, 100], [255, 255, 255]);
createPosterImage("$postersDir/poster_spandan.png", "Spandan 2026\nAnnual Cultural Fiesta", "Cultural Festival", [183, 28, 28], [245, 124, 0], [255, 255, 255]);
createPosterImage("$postersDir/poster_cybershield.png", "CyberShield 2026\nEthical Hacking & Defense", "Security Workshop", [33, 33, 33], [98, 0, 234], [255, 255, 255]);

// 2. Generate test_poster_valid.png (Target: ~0.15 MB = 153,600 bytes)
createPosterImage("$testFilesDir/test_poster_valid.png", "AI Summit 2026\nStudent Innovation Expo", "Technical Summit", [20, 80, 160], [10, 140, 100], [255, 255, 255], 600, 400, 155000);

// 3. Generate test_invalid_doc.txt (.txt file)
file_put_contents("$testFilesDir/test_invalid_doc.txt", "This is a plain text file intended to test file format validation in StudentHub Campus Event Management.");

// 4. Generate sample_agenda.docx (.docx file)
file_put_contents("$testFilesDir/sample_agenda.docx", "PK\x03\x04Mock DOCX Archive for Testing Event Management Upload Guard");

// 5. Generate test_poster_large.png (Target: ~3.20 MB = 3,355,443 bytes)
createPosterImage("$testFilesDir/test_poster_large.png", "Oversized Event Poster\nSize Validation Test", "Special Event", [120, 20, 20], [60, 10, 10], [255, 255, 255], 800, 600, 3355443);

// 6. Generate test_fake_image.png (Renamed text/php script to verify server MIME rejection)
file_put_contents("$testFilesDir/test_fake_image.png", "<?php echo 'Malicious script disguised as PNG'; ?>");

echo "All seed posters and test files generated successfully!\n";
