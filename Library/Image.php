<?php
/**
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

class Image {

  static public function by_size($file_path, $width, $height, $force = false) {
    $width = (empty($width) || ! is_numeric($width)) ? '96' : $width;
    $height = (empty($height) || ! is_numeric($height)) ? '96' : $height;
    $suffix = "{$width}x{$height}";
    $thumb = $ofile = $file_path;
    $ext = substr($thumb, strrpos($thumb, '.') + 1);
    $part1 = substr($thumb, 0, strrpos($thumb, '.'));
    $part2 = substr($part1, 0, strrpos($part1, '.'));
    if (empty($part2)) {
      $thumb = $part1 . ".$suffix.$ext";
      $prefix = $part1;
    } else {
      $thumb = $part2 . ".$suffix.$ext";
      $prefix = $part2;
    }
    if ($force) {
      // remove all cached thumbnails so they get regenerated
      foreach (glob("$prefix*.*x*.$ext") as $file) {
        @unlink($file);
      }
    }
    $file = str_replace('//', '/', dirname($ofile) . "/" . basename($thumb));
    if (! file_exists($thumb)) {
      if (! Image::thumbnail($ofile, $suffix, $width, $height)) {
        return false;
      }
    }
    return str_replace(PartuzaConfig::get('site_root'), '', $file);
  }

  static public function thumbnail($file_path, $suffix = 'thumbnail', $desired_width = 96, $desired_height = 96) {
    $thumb = $file_path;
    $file = $file_path;
    $ext = substr($thumb, strrpos($thumb, '.') + 1);
    $thumb = substr($thumb, 0, strrpos($thumb, '.')) . ".$suffix.$ext";
    // test to see if there are two suffixes in the same file name like 96x96.64x64
    // if there are then simply remove the first one and leave the second one
    // matches [set of chars].[set of chars].[set of chars].jpg
    $pattern = '/.*\..*\..*\.jpg/';
    if (preg_match($pattern, $thumb)) {
      $part1 = substr($file_path, 0, strpos($file_path, '.'));
      $thumb = $part1 . '.' . $desired_width . 'x' . $desired_width . '.jpg';
    }
    if (! file_exists($file)) {
      return false;
    }
    // These are the ratio calculations		
    if (! $size = @GetImageSize($file)) {
      return false;
    }
    $width = $size[0];
    $height = $size[1];
    if ($width > 0 && $height > 0) {
      $wfactor = $desired_width / $width;
      $hfactor = $desired_height / $height;
      if ($wfactor < $hfactor) {
        $factor = $wfactor;
      } else {
        $factor = $hfactor;
      }
    }
    if (isset($factor) && $factor < 1) {
      $twidth = ceil($factor * $width);
      $theight = ceil($factor * $height);
      Image::convert($file, $thumb, $twidth, $theight);
    } else {
      if (file_exists($thumb)) {
        @unlink($thumb);
      }
      if (function_exists('symlink')) {
        if (! symlink($file, $thumb)) {
          die("Permission denied on generating thumbnail symlink");
        }
      } else {
        // php on windows doesn't know how to symlink so copy instead
        if (! copy($file, $thumb)) {
          die("Permission denied on generating thumbnail copy");
        }
      }
    }
    return true;
  }

  static public function convert($source, $destination, $desired_width = null, $desired_height = null) {
    Image::createImage($source, $destination, $desired_width, $desired_height);
    if (file_exists($destination)) {
      @chmod($destination, 0664);
    } else {
      die("Failed to generate thumbnail, check directory permissions and the availability of <b>gd.</b>");
    }
  }

  static public function createImage($source, $destination, $desired_width, $desired_height) {
    // Capture the original size of the uploaded image
    if (! $info = getimagesize($source)) {
      return false;
    }
    $src = false;
    switch ($info['mime']) {
      case 'image/jpeg':
        $src = imagecreatefromjpeg($source);
        break;
      case 'image/gif':
        $src = imagecreatefromgif($source);
        break;
      case 'image/png':
        $src = imagecreatefrompng($source);
        break;
    }
    if (! $src) {
      return false;
    }
    $tmp = @imagecreatetruecolor($desired_width, $desired_height);
    // this line actually does the image resizing
    // copying from the original image into the $tmp image
    if (! @imagecopyresampled($tmp, $src, 0, 0, 0, 0, $desired_width, $desired_height, $info[0], $info[1])) {
      @imagedestroy($src);
      return false;
    }
    @unlink($destination);
    switch ($info['mime']) {
      case 'image/jpeg':
        $ret = @imagejpeg($tmp, $destination, 100);
        break;
      case 'image/gif':
        $ret = @imagegif($tmp, $destination, 100);
        break;
      case 'image/png':
        $ret = @imagepng($tmp, $destination, 100);
        break;
    }
    @imagedestroy($src);
    @imagedestroy($tmp);
    if (! $ret) {
      return false;
    }
    return true;
  }
}