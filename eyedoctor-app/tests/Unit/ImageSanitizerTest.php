<?php

use App\Services\ImageSanitizer;

function retinaCaptureImage(callable $writer): string
{
    ob_start();

    try {
        $writer();
        $bytes = ob_get_contents();
    } finally {
        ob_end_clean();
    }

    if (! is_string($bytes) || $bytes === '') {
        throw new RuntimeException('Test image generation failed.');
    }

    return $bytes;
}

function retinaCreateTestImage()
{
    $image = imagecreatetruecolor(12, 10);

    if ($image === false) {
        throw new RuntimeException('Could not create test image.');
    }

    $background = imagecolorallocate($image, 20, 40, 60);
    $detail = imagecolorallocate($image, 210, 70, 30);

    imagefill($image, 0, 0, $background);
    imagefilledellipse($image, 6, 5, 5, 5, $detail);

    return $image;
}

function retinaJpegMetadataSegment(int $marker, string $payload): string
{
    return "\xFF"
        .chr($marker)
        .pack('n', strlen($payload) + 2)
        .$payload;
}

function retinaCreateJpegWithMetadata(): string
{
    $image = retinaCreateTestImage();

    try {
        $jpeg = retinaCaptureImage(
            fn () => imagejpeg($image, null, 95)
        );
    } finally {
        imagedestroy($image);
    }

    $segments = [
        // APP1 EXIF-style segment.
        retinaJpegMetadataSegment(
            0xE1,
            "Exif\x00\x00RETINA_EXIF_PATIENT_123"
        ),

        // APP1 XMP-style segment.
        retinaJpegMetadataSegment(
            0xE1,
            "http://ns.adobe.com/xap/1.0/\x00"
                .'RETINA_XMP_PATIENT_456'
        ),

        // APP13 IPTC/Photoshop-style segment.
        retinaJpegMetadataSegment(
            0xED,
            "Photoshop 3.0\x00RETINA_IPTC_PATIENT_789"
        ),

        // JPEG COM segment.
        retinaJpegMetadataSegment(
            0xFE,
            'RETINA_JPEG_COMMENT_PATIENT_999'
        ),
    ];

    return substr($jpeg, 0, 2)
        .implode('', $segments)
        .substr($jpeg, 2);
}

function retinaCreatePngWithTextMetadata(): string
{
    $image = retinaCreateTestImage();

    try {
        $png = retinaCaptureImage(
            fn () => imagepng($image)
        );
    } finally {
        imagedestroy($image);
    }

    $metadata =
        "PatientName\x00Juan Dela Cruz | PatientID RETINA-SECRET-456";

    $chunkType = 'tEXt';

    $crc = hex2bin(
        hash('crc32b', $chunkType.$metadata)
    );

    if ($crc === false) {
        throw new RuntimeException('Could not generate PNG metadata CRC.');
    }

    $textChunk =
        pack('N', strlen($metadata))
        .$chunkType
        .$metadata
        .$crc;

    return substr($png, 0, 33)
        .$textChunk
        .substr($png, 33);
}

function retinaPixelMap(string $bytes): array
{
    $image = @imagecreatefromstring($bytes);

    if ($image === false) {
        throw new RuntimeException(
            'Could not decode image for pixel test.'
        );
    }

    try {
        $width = imagesx($image);
        $height = imagesy($image);
        $pixels = [];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $pixels[] = imagecolorat($image, $x, $y);
            }
        }

        return [
            'width' => $width,
            'height' => $height,
            'pixels' => $pixels,
        ];
    } finally {
        imagedestroy($image);
    }
}

test(
    'sanitizer strips jpeg exif xmp iptc and comment metadata',
    function () {
        $source = retinaCreateJpegWithMetadata();

        expect($source)
            ->toContain('RETINA_EXIF_PATIENT_123')
            ->toContain('RETINA_XMP_PATIENT_456')
            ->toContain('RETINA_IPTC_PATIENT_789')
            ->toContain('RETINA_JPEG_COMMENT_PATIENT_999');

        $sanitized = app(ImageSanitizer::class)
            ->sanitizeToPng($source);

        expect(substr($sanitized, 0, 8))
            ->toBe("\x89PNG\r\n\x1a\n");

        expect($sanitized)
            ->not->toContain('RETINA_EXIF_PATIENT_123')
            ->not->toContain('RETINA_XMP_PATIENT_456')
            ->not->toContain('RETINA_IPTC_PATIENT_789')
            ->not->toContain('RETINA_JPEG_COMMENT_PATIENT_999');
    }
);

test(
    'sanitizer removes png text metadata',
    function () {
        $source = retinaCreatePngWithTextMetadata();

        expect($source)->toContain('Juan Dela Cruz');
        expect($source)->toContain('RETINA-SECRET-456');

        $sanitized = app(ImageSanitizer::class)
            ->sanitizeToPng($source);

        expect(substr($sanitized, 0, 8))
            ->toBe("\x89PNG\r\n\x1a\n");

        expect($sanitized)->not->toContain('Juan Dela Cruz');
        expect($sanitized)->not->toContain('RETINA-SECRET-456');
    }
);

test(
    'sanitizer preserves decoded pixel values during png output',
    function () {
        $source = retinaCreateJpegWithMetadata();

        $before = retinaPixelMap($source);

        $sanitized = app(ImageSanitizer::class)
            ->sanitizeToPng($source);

        $after = retinaPixelMap($sanitized);

        expect($after['width'])->toBe($before['width']);
        expect($after['height'])->toBe($before['height']);
        expect($after['pixels'])->toBe($before['pixels']);
    }
);

test(
    'sanitizer fails closed for undecodable input',
    function () {
        expect(
            fn () => app(ImageSanitizer::class)
                ->sanitizeToPng('this-is-not-an-image')
        )->toThrow(
            RuntimeException::class,
            'Uploaded image could not be decoded safely.'
        );
    }
);
