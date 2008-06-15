<?php
/*
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

	static public function by_size($file_path, $width, $height, $force = false)
	{
		$width = (empty($width) || ! is_numeric($width)) ? '92' : $width;
		$height = (empty($height) || ! is_numeric($height)) ? '96' : $height;
		$suffix = "{$width}x{$height}";
		$thumb = $ofile = $file_path;
		$ext = substr($thumb, strrpos($thumb, '.') + 1);
		$thumb = substr($thumb, 0, strrpos($thumb, '.')) . ".$suffix.$ext";
		if ($force) {
			// remove all cached thumbnails so they get regenerated
			$path = dirname($thumb);
			foreach (glob("$path/*.*x*.$ext") as $file) {
				@unlink($file);
			}
		}		
		$file = str_replace('//', '/', dirname($ofile) . "/" . basename($thumb));
		if (! file_exists($thumb)) {
			if (! Image::thumbnail($ofile, $suffix, $width, $height)) {
				return false;
			}
		}
		return str_replace(Config::get('site_root'), '', $file);
	}

	static public function thumbnail($file_path, $suffix = 'thumbnail', $desired_width = 92, $desired_height = 96)
	{
		$thumb = $file_path;
		$file = $file_path;
		$ext = substr($thumb, strrpos($thumb, '.') + 1);
		$thumb = substr($thumb, 0, strrpos($thumb, '.')) . ".$suffix.$ext";
		if (! file_exists($file)) {
			return false;
		}
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
			Image::convert($file, $thumb, $width, $height, $twidth, $theight);
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

	static public function convert($source, $destination, $width, $height, $desired_width, $desired_height)
	{
		Image::createImage($source, $destination, $width, $height, $desired_width, $desired_height);
		if (file_exists($destination)) {
			@chmod($destination, 0664);
		} else {
			die("Failed to generate thumbnail, check directory permissions and the availability of <b>gd.</b>");
		}
	}

	static public function createImage($source, $destination, $width, $height, $desired_width, $desired_height)
	{
		//die("about to createImage($source, $destination, $width, $height, $desired_width, $desired_height)");
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
		if (! @imagecopyresampled($tmp, $src, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height)) {
			@imagedestroy($src);
			return false;
		}
		@unlink($destination);
		switch ($info['mime']) {
			case 'image/jpeg':
				$ret = @imagejpeg($tmp, $destination, 100);
				break;
			case 'image/gif':
				$ret = @imagegif($tmp, $destination);
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