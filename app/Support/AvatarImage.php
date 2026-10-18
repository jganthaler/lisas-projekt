<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * The picture of a profile: the uploaded file, shrunk to a size that fits the
 * app, and the bytes that belong on the disk.
 *
 * A photo from a phone is easily 4000 x 3000 pixels and several megabytes
 * large - far too much for a circle of 64 pixels (the one in the header is
 * even smaller) and far too much for the upload of a phone. Every picture that
 * is longer than MAX_EDGE on its longest edge is therefore scaled down and
 * encoded again before it is stored; a picture that already fits is stored
 * untouched, so no quality is lost for nothing.
 *
 * Shrinking needs the GD extension. Where it is missing, the picture is stored
 * exactly as it was uploaded: still usable, only bigger than needed.
 */
final class AvatarImage
{
    /**
     * Longest edge of a stored picture, in pixels.
     *
     * The avatar is shown with 36 pixels in the header and with 64 in the
     * profile (see components/avatar.blade.php). 512 pixels covers a screen
     * with a very high density and still keeps a photo around 50 KB.
     */
    public const MAX_EDGE = 512;

    /** Quality of JPEG and WebP, from 0 (worst) to 100 (best). */
    private const QUALITY = 85;

    /** Compression of a PNG, from 0 (none) to 9 (smallest file). */
    private const PNG_COMPRESSION = 9;

    /**
     * The picture types that are scaled and encoded again, each with the
     * extension it is stored under. Everything else GD cannot write.
     *
     * @var array<int, string>
     */
    private const ENCODABLE = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    private function __construct(
        private readonly string $contents,
        private readonly string $extension,
    ) {}

    /**
     * Reads the uploaded picture and returns what belongs on the disk: the
     * bytes and the extension that fits them.
     */
    public static function fromUpload(UploadedFile $file): self
    {
        $contents = (string) file_get_contents((string) $file->getRealPath());
        $size = @getimagesizefromstring($contents);

        // The profile checks the upload with the "image" rule before it gets
        // here, so a header that cannot be read is unexpected. The picture is
        // then stored the way it came - which is still better than nothing.
        if ($size === false) {
            return new self($contents, self::extensionOf($file));
        }

        $type = (int) $size[2];

        if (! self::isEncodable($type)) {
            return new self($contents, self::extensionOf($file));
        }

        [$width, $height] = self::fit($size[0], $size[1]);

        // Small enough already: encoding it again would only cost quality.
        if ($width === $size[0] && $height === $size[1]) {
            return new self($contents, self::ENCODABLE[$type]);
        }

        // Should GD fail on this file after all, the upload itself is still
        // better than no picture.
        $shrunk = self::shrink($contents, $type, $width, $height) ?? $contents;

        return new self($shrunk, self::ENCODABLE[$type]);
    }

    /** The bytes that are stored on the disk. */
    public function contents(): string
    {
        return $this->contents;
    }

    /** The extension for those bytes: "jpg", "png" or "webp". */
    public function extension(): string
    {
        return $this->extension;
    }

    /**
     * The size a picture of the given size is stored in: the longest edge is
     * cut down to MAX_EDGE, the ratio stays. A picture that already fits keeps
     * its size - it is never blown up.
     *
     * @return array{0: int, 1: int} width and height, in pixels
     */
    public static function fit(int $width, int $height, int $maxEdge = self::MAX_EDGE): array
    {
        $longest = max($width, $height);

        if ($longest <= $maxEdge) {
            return [$width, $height];
        }

        $scale = $maxEdge / $longest;

        // Rounding must never produce a zero: a long, thin picture (a banner)
        // has to keep both of its edges.
        return [max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale))];
    }

    /**
     * Whether GD can read and write this kind of picture. Without the
     * extension - or without WebP, which depends on how GD was built - the
     * upload is left alone.
     */
    private static function isEncodable(int $type): bool
    {
        if (! extension_loaded('gd') || ! isset(self::ENCODABLE[$type])) {
            return false;
        }

        return $type !== IMAGETYPE_WEBP || function_exists('imagewebp');
    }

    /**
     * Scales the picture down and encodes it again - in the format it came in,
     * so that a PNG keeps its transparency. Null when GD cannot handle the
     * file.
     */
    private static function shrink(string $contents, int $type, int $width, int $height): ?string
    {
        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            return null;
        }

        $target = imagecreatetruecolor($width, $height);

        if ($target === false) {
            return null;
        }

        // The transparency of a PNG has to survive the scaling.
        imagealphablending($target, false);
        imagesavealpha($target, true);

        imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));

        ob_start();

        $encoded = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($target, null, self::QUALITY),
            IMAGETYPE_PNG => imagepng($target, null, self::PNG_COMPRESSION),
            default => imagewebp($target, null, self::QUALITY),
        };

        $picture = ob_get_clean();

        return $encoded && is_string($picture) && $picture !== '' ? $picture : null;
    }

    /** The extension of an upload whose header cannot be read. */
    private static function extensionOf(UploadedFile $file): string
    {
        return $file->extension() ?: 'jpg';
    }
}
