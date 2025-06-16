<?php
//    Pastèque Web back office
//
//    Copyright (C) 2013 Scil (http://scil.coop)
//
//    This file is part of Pastèque.
//
//    Pastèque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pastèque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pastèque.  If not, see <http://www.gnu.org/licenses/>.

namespace Pasteque\Server\System;

use \Pasteque\Server\Model\Image;

/** Thumbnail generator. */
class Thumbnailer
{
    /** Keep image format when thumbnailing. */
    const FORMAT_ORIGINAL = 'original';
    /** Create JPEG thumbnails. */
    const FORMAT_JPEG = 'jpeg';
    /** Create PNG thumbnails. */
    const FORMAT_PNG = 'png';

    private $outputWidth;
    private $outputHeight;
    private $outputFormat;
    private $outputJpegQuality;
    private $outputPngCompression;

    public function construct() {
        $this->outputWidth = 128;
        $this->outputHeight = 128;
        $this->outputFormat = 'png';
        $this->outputJpegQuality = 50;
        $this->outputPngCompression = 9;
    }

    /** Create a thumbnail from the given binary data.
     * @param $data Binary input data.
     * @return An Image with mimeType and image set. False on failure. */
    public function thumbnail($data) {
        $result = new Image();
        $imgData = @getimagesizefromstring($data);
        if ($imgData === false) {
            return false;
        }
        $imgWidth = $imgData[0];
        $imgHeight = $imgData[1];
        $ratio = 1;
        $imgType = $imgData[2]; // A gd constant
        if ($imgWidth > $this->outputWidth
                && $imgHeight > $this->outputHeight) {
            $widthRatio = $this->outputWidth / $imgWidth;
            $heightRatio = $this->outputHeight / $imgHeight;
            // Use the smallest ratio to resize without cropping
            $ratio = min($widthRatio, $heightRatio);
        }
        $destWidth = round($imgWidth * $ratio);
        $destHeight = round($imgHeight * $ratio);
        // Read input
        $src = @imagecreatefromstring($data);
        if ($src === false) {
            return false;
        }
        // Create thumbnail
        $dst = imagecreatetruecolor($destWidth, $destHeight);
        // Copy image
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $destWidth, $destHeight,
                $imgWidth, $imgHeight);
        // Handle jpg rotation
        if ($imgType == IMG_JPG || $imgType == IMAGETYPE_JPEG) {
            $exif = exif_read_data('data://image/jpeg;base64,'
                    . \base64_encode($data));
            if ($exif !== false && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];
                switch($orientation) {
                case 3:
                    $dst = imagerotate($dst, 180, 0);
                    break;
                case 6:
                    $dst = imagerotate($dst, -90, 0);
                    break;
                case 8:
                    $dst = imagerotate($dst, 90, 0);
                    break;
                }
            }
        }
        // Write output
        switch ($this->outputFormat) {
            case static::FORMAT_JPEG:
                $result->setMimeType('image/jpeg');
                $result->setImage($this->createJpeg($dst));
                return $result;
            case static::FORMAT_PNG:
                $result->setMimeType('image/png');
                $result->setImage($this->createPng($dst));
                return $result;
            case static::FORMAT_ORIGINAL:
            default:
                switch ($imgType) {
                    case IMG_JPG:
                    case IMG_JPEG:
                        $result->setMimeType('image/jpeg');
                        $result->setImage($this->createJpeg($dst));
                        return $result;
                    case IMG_PNG:
                    default:
                        $result->setMimeType('image/png');
                        $result->setImage($this->createPng($dst));
                        return $result;
                }
        }
    }

    /** Create and return a jpeg from gd buffer. */
    protected function createJpeg($buffer) {
        ob_start();
        $done = imagejpeg($buffer, null, $this->outputJpegQuality);
        $data = ob_get_contents();
        ob_end_clean();
        if ($done === false) { return false; }
        return $data;
    }

    /** Create and return a png from gd buffer. */
    protected function createPng($buffer) {
        ob_start();
        $done = imagepng($buffer, null, $this->outputPngCompression);
        $data = ob_get_contents();
        ob_end_clean();
        if ($done === false) { return false; }
        return $data;
    }

    public function getOutputWidth() { return $this->outputWidth; }
    public function setOutputWidth($width) { $this->outputWidth = $width; }
    public function getOutputHeight() { return $this->outputHeight; }
    public function setOutputHeight($height) { $this->outputHeight = $height; }
    public function getOutputFormat() { return $this->outputFormat; }
    public function setOutputFormat($format) {
        switch (strtolower($format)) {
            case 'jpeg':
            case 'jpg':
                $this->outputFormat = static::FORMAT_JPEG;
                break;
            case 'png':
                $this->outputFormat = static::FORMAT_PNG;
                break;
            case 'original':
            case '':
                $this->outputFormat = static::FORMAT_ORIGINAL;
                break;
            default:
                throw new \UnexpectedValueException(sprintf('Format %s is not supported', $format));
        }
    }
    public function getOutputJpegQuality() { return $this->outputJpegQuality; }
    /** Set Jpeg quality for thumbnail images (0 to 100) */
    public function setOutputJpegQuality($jpegQuality) {
        $this->outputJpegQuality = $jpegQuality;
    }
    public function getOutputPngCompression() { return $this->outputPngCompression; }
    /** Set Png compression level for thumbnail images (0 to 9) */
    public function setOutputPngCompression($pngCompression) {
        $this->outputPngCompression = $pngCompression;
    }

}
