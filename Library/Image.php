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

function image_by_size($file_path, $width, $height)
{
	global $site_root;
	$width  = (empty($width)  || !is_numeric($width))  ? '92' : $width;
	$height = (empty($height) || !is_numeric($height)) ? '96' : $height;
	$suffix = "{$width}x{$height}";
	$thumb  = $ofile  = $file_path;
	$ext    = substr($thumb, strrpos($thumb,'.') + 1);
	$thumb  = substr($thumb, 0, strrpos($thumb, '.')).".$suffix.$ext";
	$file   = str_replace('//', '/', dirname($ofile)."/".basename($thumb));
	if (!file_exists($thumb)) {
		if (!image_thumbnail($ofile, $suffix, $width, $height)) {
			return false;
		}
	}
	return str_replace($site_root,'', $file);
}

function image_thumbnail($file_path, $suffix = 'thumbnail', $desired_width = 92, $desired_height = 96)
{
	global $site_root;
	$thumb = $file_path;
	$file  = $file_path;
	$ext   = substr($thumb, strrpos($thumb,'.')+1);
	$thumb = substr($thumb, 0, strrpos($thumb,'.')).".$suffix.$ext";
	if (!file_exists($file)) {
		return false;
	}
	if (!$size    = @GetImageSize($file)) {
		return false;
	}
	$width       = $size[0];
	$height      = $size[1];
	if ($width > 0 && $height > 0) {
		$wfactor = $desired_width  / $width;
		$hfactor = $desired_height / $height;
		if ($wfactor < $hfactor) {
			$factor = $wfactor;
		} else {
			$factor = $hfactor;
		}
	}
	if (isset($factor) && $factor < 1) {
		$twidth  = ceil($factor * $width);
		$theight = ceil($factor * $height);
		image_convert($file, $thumb, $twidth, $theight, $desired_width=$desired_width, $desired_height=$desired_height);
	} else {
		if (!file_exists($thumb)) {
			@symlink($file, $thumb);
		}
	}
	return true;
}

function image_convert($source, $destination, $width, $height, $desired_width=null, $desired_height=null)
{
	if (file_exists('/usr/bin/convert')) {
		$convert = "/usr/bin/convert";
	} elseif (file_exists('/usr/local/bin/convert')) {
		$convert = "/usr/local/bin/convert";
	} elseif (file_exists('/usr/X11R6/bin/convert')) {
		$convert = "/usr/X11R6/bin/convert";
	} else {
		// lets hope for the best and hope convert can be somewhere else in the shell's PATH
		$convert = "convert";
	}
    $size = $width.'x'.$height;
    $desired_size = $desired_width && $desired_height ? $desired_width.'x'.$desired_height : $size;
    @exec($convert.' '.escapeshellarg($source).' -thumbnail '.$size.' -size '.$desired_size.' '.escapeshellarg($destination));
	 if (file_exists($destination)) {
    	@chmod($destination, 0664);
    }
}
