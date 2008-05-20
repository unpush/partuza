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

	static public function by_size($file_path, $width, $height)
	{
		$width = (empty($width) || ! is_numeric($width)) ? '92' : $width;
		$height = (empty($height) || ! is_numeric($height)) ? '96' : $height;
		$suffix = "{$width}x{$height}";
		$thumb = $ofile = $file_path;
		$ext = substr($thumb, strrpos($thumb, '.') + 1);
		$thumb = substr($thumb, 0, strrpos($thumb, '.')) . ".$suffix.$ext";
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
			Image::convert($file, $thumb, $twidth, $theight, $desired_width = $desired_width, $desired_height = $desired_height);
		} else {
			if (! file_exists($thumb)) {
				if (!symlink($file, $thumb)) {
					die("Permission denied on generating thumbnail symlink");
				}
			}
		}
		return true;
	}

	static public function convert($source, $destination, $width, $height, $desired_width = null, $desired_height = null)
	{
		//TODO convert gives better results then gd and supports more formats, however it's not likely to work on windows
		// should add a if platform == win32 case and use gd then if available
		if (file_exists('/usr/bin/convert')) {
			$convert = "/usr/bin/convert";
		} elseif (file_exists('/usr/local/bin/convert')) {
			$convert = "/usr/local/bin/convert";
		} elseif (file_exists('/usr/X11R6/bin/convert')) {
			$convert = "/usr/X11R6/bin/convert";
		} elseif (file_exists('/sw/bin/convert')) {
			$convert = "/sw/bin/convert";
		} else {
			// lets hope for the best and hope convert can be somewhere else in the shell's PATH
			$convert = "convert";
		}
		$size = $width . 'x' . $height;
		$desired_size = $desired_width && $desired_height ? $desired_width . 'x' . $desired_height : $size;
		@exec($convert . ' ' . escapeshellarg($source) . ' -thumbnail ' . $size . ' -size ' . $desired_size . ' ' . escapeshellarg($destination));
		if (file_exists($destination)) {
			@chmod($destination, 0664);
		} else {
			die("Failed to generate thumbnail, check directory permissions and the availability of 'convert' (ImageMagick)");
		}
	}
}