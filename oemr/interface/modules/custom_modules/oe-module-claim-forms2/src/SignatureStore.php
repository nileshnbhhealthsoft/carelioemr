<?php

namespace OpenEMR\Modules\ClaimForms;

/**
 * Per-provider signature and stamp images. A user can only ever read, write or
 * delete their own: every method takes the id of the logged-in user and the
 * file name comes from that id alone.
 */
class SignatureStore
{
    private const MAX_BYTES = 1048576;   // 1 MB upload
    private const MAX_PX = 1600;

    public static function path(int $userId, string $kind): ?string
    {
        $p = Storage::userImagePath($userId, $kind);
        return ($p && is_file($p)) ? $p : null;
    }

    /** Returns null on success, or an error message. Re-encodes to PNG, which also strips anything that is not image data. */
    public static function save(int $userId, string $kind, array $upload): ?string
    {
        $dest = Storage::userImagePath($userId, $kind);
        if (!$dest) {
            return 'Invalid image type';
        }
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
            return 'Upload failed';
        }
        if (($upload['size'] ?? 0) > self::MAX_BYTES) {
            return 'Image is larger than 1 MB';
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        $src = match ($mime) {
            'image/png' => @imagecreatefrompng($upload['tmp_name']),
            'image/jpeg' => @imagecreatefromjpeg($upload['tmp_name']),
            default => false,
        };
        if (!$src) {
            return 'Use a PNG or JPEG image';
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, self::MAX_PX / max($w, $h));
        $nw = max(1, (int)round($w * $scale));
        $nh = max(1, (int)round($h * $scale));
        $out = imagecreatetruecolor($nw, $nh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 255, 255, 255, 127));
        imagecopyresampled($out, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $ok = imagepng($out, $dest, 6);
        return $ok ? null : 'Could not save the image';
    }

    public static function delete(int $userId, string $kind): void
    {
        $p = self::path($userId, $kind);
        if ($p) {
            @unlink($p);
        }
    }
}
