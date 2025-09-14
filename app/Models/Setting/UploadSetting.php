<?php
/*
 * Nyasajob - Job Board Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com/nyasajob
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
 */

namespace App\Models\Setting;

/*
 * settings.upload.option
 */

class UploadSetting
{
	public static function getValues($value, $disk)
	{
		$config = config('larapen.media.resize.namedOptions.default');
		$method = data_get($config, 'method', 'resize');
		$width = data_get($config, 'width', 900);
		$height = data_get($config, 'height', 900);
		$ratio = data_get($config, 'ratio', '1');
		$upsize = data_get($config, 'upsize', '0');
		$position = data_get($config, 'position', 'center');
		$relative = data_get($config, 'relative', false);
		$bgColor = data_get($config, 'bgColor', 'ffffff');
		
		$resizeOptionsNamesArray = array_keys((array)config('larapen.media.resize.namedOptions'));
		
		if (empty($value)) {
			
			$value['file_types'] = 'pdf,doc,docx,word,rtf,rtx,ppt,pptx,odt,odp,wps,jpeg,jpg,bmp,png';
			$value['min_file_size'] = '0';
			$value['max_file_size'] = '2500';
			
			$value['image_types'] = 'jpg,jpeg,gif,png';
			$value['image_quality'] = '90';
			$value['min_image_size'] = '0';
			$value['max_image_size'] = '2500';
			
			// default
			$settingKeyPrefix = 'img_resize_default';
			$value[$settingKeyPrefix . '_method'] = $method;
			$value[$settingKeyPrefix . '_width'] = $width;
			$value[$settingKeyPrefix . '_height'] = $height;
			$value[$settingKeyPrefix . '_ratio'] = $ratio;
			$value[$settingKeyPrefix . '_upsize'] = $upsize;
			$value[$settingKeyPrefix . '_position'] = $position;
			$value[$settingKeyPrefix . '_relative'] = $relative;
			$value[$settingKeyPrefix . '_bgColor'] = $bgColor;
			
			// others
			foreach ($resizeOptionsNamesArray as $optionsName) {
				$config = config('larapen.media.resize.namedOptions.' . $optionsName);
				$settingKeyPrefix = 'img_resize_' . str_replace('-', '_', $optionsName);
				
				$value[$settingKeyPrefix . '_method'] = data_get($config, 'method', $method);
				$value[$settingKeyPrefix . '_width'] = data_get($config, 'width', $width);
				$value[$settingKeyPrefix . '_height'] = data_get($config, 'height', $height);
				$value[$settingKeyPrefix . '_ratio'] = data_get($config, 'ratio', $ratio);
				$value[$settingKeyPrefix . '_upsize'] = data_get($config, 'upsize', $upsize);
				$value[$settingKeyPrefix . '_position'] = data_get($config, 'position', $position);
				$value[$settingKeyPrefix . '_relative'] = data_get($config, 'relative', $relative);
				$value[$settingKeyPrefix . '_bgColor'] = data_get($config, 'bgColor', $bgColor);
			}
			
		} else {
			
			if (!array_key_exists('file_types', $value)) {
				$value['file_types'] = 'pdf,doc,docx,word,rtf,rtx,ppt,pptx,odt,odp,wps,jpeg,jpg,bmp,png';
			}
			if (!array_key_exists('min_file_size', $value)) {
				$value['min_file_size'] = '0';
			}
			if (!array_key_exists('max_file_size', $value)) {
				$value['max_file_size'] = '2500';
			}
			
			if (!array_key_exists('image_types', $value)) {
				$value['image_types'] = 'jpg,jpeg,gif,png';
			}
			if (!array_key_exists('image_quality', $value)) {
				$value['image_quality'] = '90';
			}
			if (!array_key_exists('min_image_size', $value)) {
				$value['min_image_size'] = '0';
			}
			if (!array_key_exists('max_image_size', $value)) {
				$value['max_image_size'] = '2500';
			}
			
			// default
			$settingKeyPrefix = 'img_resize_default';
			if (!array_key_exists($settingKeyPrefix . '_method', $value)) {
				$value[$settingKeyPrefix . '_method'] = $method;
			}
			if (!array_key_exists($settingKeyPrefix . '_width', $value)) {
				$value[$settingKeyPrefix . '_width'] = $width;
			}
			if (!array_key_exists($settingKeyPrefix . '_height', $value)) {
				$value[$settingKeyPrefix . '_height'] = $height;
			}
			if (!array_key_exists($settingKeyPrefix . '_ratio', $value)) {
				$value[$settingKeyPrefix . '_ratio'] = $ratio;
			}
			if (!array_key_exists($settingKeyPrefix . '_upsize', $value)) {
				$value[$settingKeyPrefix . '_upsize'] = $upsize;
			}
			if (!array_key_exists($settingKeyPrefix . '_position', $value)) {
				$value[$settingKeyPrefix . '_position'] = $position;
			}
			if (!array_key_exists($settingKeyPrefix . '_relative', $value)) {
				$value[$settingKeyPrefix . '_relative'] = $relative;
			}
			if (!array_key_exists($settingKeyPrefix . '_bgColor', $value)) {
				$value[$settingKeyPrefix . '_bgColor'] = $bgColor;
			}
			
			// others
			foreach ($resizeOptionsNamesArray as $optionsName) {
				$config = config('larapen.media.resize.namedOptions.' . $optionsName);
				$settingKeyPrefix = 'img_resize_' . str_replace('-', '_', $optionsName);
				
				if (!array_key_exists($settingKeyPrefix . '_method', $value)) {
					$value[$settingKeyPrefix . '_method'] = data_get($config, 'method', $method);
				}
				if (!array_key_exists($settingKeyPrefix . '_width', $value)) {
					$value[$settingKeyPrefix . '_width'] = data_get($config, 'width', $width);
				}
				if (!array_key_exists($settingKeyPrefix . '_height', $value)) {
					$value[$settingKeyPrefix . '_height'] = data_get($config, 'height', $height);
				}
				if (!array_key_exists($settingKeyPrefix . '_ratio', $value)) {
					$value[$settingKeyPrefix . '_ratio'] = data_get($config, 'ratio', $ratio);
				}
				if (!array_key_exists($settingKeyPrefix . '_upsize', $value)) {
					$value[$settingKeyPrefix . '_upsize'] = data_get($config, 'upsize', $upsize);
				}
				if (!array_key_exists($settingKeyPrefix . '_position', $value)) {
					$value[$settingKeyPrefix . '_position'] = data_get($config, 'position', $position);
				}
				if (!array_key_exists($settingKeyPrefix . '_relative', $value)) {
					$value[$settingKeyPrefix . '_relative'] = data_get($config, 'relative', $relative);
				}
				if (!array_key_exists($settingKeyPrefix . '_bgColor', $value)) {
					$value[$settingKeyPrefix . '_bgColor'] = data_get($config, 'bgColor', $bgColor);
				}
			}
			
		}
		
		// Get right values
		if (is_array($value)) {
			// Numeric values (keys: upload, ...)
			foreach ($value as $k => $v) {
				if (
					(str($k)->startsWith(['img_resize_']) && str($k)->endsWith(['_width', '_height']))
					|| str($k)->endsWith(['_file_size', '_image_size'])
				) {
					$value[$k] = forceToInt($v);
				}
			}
			
			// 'bgcolor' & 'relative' get format
			foreach ($resizeOptionsNamesArray as $optionsName) {
				$settingKeyPrefix = 'img_resize_' . str_replace('-', '_', $optionsName);
				
				if (array_key_exists($settingKeyPrefix . '_bgColor', $value)) {
					$value[$settingKeyPrefix . '_relative'] = ($value[$settingKeyPrefix . '_relative'] == '1');
					$value[$settingKeyPrefix . '_bgColor'] = str_replace('#', '', $value[$settingKeyPrefix . '_bgColor']);
					if (isAdminPanel()) {
						$value[$settingKeyPrefix . '_relative'] = ($value[$settingKeyPrefix . '_relative']) ? '1' : '0';
						$value[$settingKeyPrefix . '_bgColor'] = '#' . $value[$settingKeyPrefix . '_bgColor'];
					}
				}
			}
		}
		
		return $value;
	}
	
	public static function setValues($value, $setting)
	{
		// Numeric values (keys: upload, ...)
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (
					(str($k)->startsWith(['img_resize_']) && str($k)->endsWith(['_width', '_height']))
					|| str($k)->endsWith(['_file_size', '_image_size'])
				) {
					$value[$k] = forceToInt($v);
				}
			}
		}
		
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$fields = [
			[
				'name'  => 'upload_files_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.upload_files_sep_value'),
			],
			[
				'name'              => 'file_types',
				'label'             => trans('admin.file_types_label'),
				'type'              => 'text',
				'hint'              => trans('admin.file_types_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'min_file_size',
				'label'             => trans('admin.min_file_size_label'),
				'type'              => 'number',
				'hint'              => trans('admin.min_file_size_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-3',
				],
			],
			[
				'name'              => 'max_file_size',
				'label'             => trans('admin.max_file_size_label'),
				'type'              => 'number',
				'hint'              => trans('admin.max_file_size_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-3',
				],
			],
			[
				'name'  => 'upload_images_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.upload_images_sep_value'),
			],
			[
				'name'              => 'image_types',
				'label'             => trans('admin.image_types_label'),
				'type'              => 'text',
				'hint'              => trans('admin.image_types_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'image_quality',
				'label'             => trans('admin.image_quality_label'),
				'type'              => 'select2_from_array',
				'options'           => collect(generateNumberRange(10, 100, 10))->mapWithKeys(fn ($i) => [$i => $i])->toArray(),
				'hint'              => trans('admin.image_quality_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'min_image_size',
				'label'             => trans('admin.min_image_size_label'),
				'type'              => 'text',
				'hint'              => trans('admin.min_image_size_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'max_image_size',
				'label'             => trans('admin.max_image_size_label'),
				'type'              => 'text',
				'hint'              => trans('admin.max_image_size_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'  => 'img_resize_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.img_resize_sep_value'),
			],
			[
				'name'  => 'img_resize_default_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.img_resize_default_sep_value'),
			],
			[
				'name'              => 'img_resize_default_width',
				'label'             => trans('admin.img_resize_width_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_width_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_default_height',
				'label'             => trans('admin.img_resize_height_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_height_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_default_ratio',
				'label'             => trans('admin.img_resize_ratio_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_ratio_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_default_upsize',
				'label'             => trans('admin.img_resize_upsize_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_upsize_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			
			// logo
			[
				'name'  => 'img_resize_logo_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.img_resize_logo_sep_value'),
			],
			[
				'name'              => 'img_resize_logo_method',
				'label'             => trans('admin.img_resize_type_resize_method_label'),
				'type'              => 'select2_from_array',
				'options'           => self::resizeMethods(),
				'hint'              => trans('admin.img_resize_type_resize_method_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
				'newline'           => true,
			],
			
			[
				'name'              => 'img_resize_logo_width',
				'label'             => trans('admin.img_resize_width_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_width_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_logo_height',
				'label'             => trans('admin.img_resize_height_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_height_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_logo_ratio',
				'label'             => trans('admin.img_resize_ratio_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_ratio_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_logo_upsize',
				'label'             => trans('admin.img_resize_upsize_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_upsize_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_logo_position',
				'label'             => trans('admin.img_resize_type_position_label'),
				'type'              => 'select2_from_array',
				'options'           => self::resizePositions(),
				'hint'              => trans('admin.img_resize_type_position_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			[
				'name'              => 'img_resize_logo_relative',
				'label'             => trans('admin.img_resize_type_relative_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_type_relative_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			[
				'name'              => 'img_resize_logo_bgColor',
				'label'             => trans('admin.img_resize_type_bgColor_label'),
				'type'              => 'color_picker',
				'attributes'        => [
					'placeholder' => '#FFFFFF',
				],
				'hint'              => trans('admin.img_resize_type_bg_color_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			
			/*
			// logo-admin
			[
				'name'  => 'img_resize_logo_admin_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.img_resize_logo_admin_sep_value'),
			],
			[
				'name'              => 'img_resize_logo_admin_method',
				'label'             => trans('admin.img_resize_type_resize_method_label'),
				'type'              => 'select2_from_array',
				'options'           => self::resizeMethods(),
				'hint'              => trans('admin.img_resize_type_resize_method_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
				'newline' => true,
			],
			[
				'name'              => 'img_resize_logo_admin_width',
				'label'             => trans('admin.img_resize_width_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_width_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_logo_admin_height',
				'label'             => trans('admin.img_resize_height_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_height_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_logo_admin_ratio',
				'label'             => trans('admin.img_resize_ratio_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_ratio_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_logo_admin_upsize',
				'label'             => trans('admin.img_resize_upsize_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_upsize_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_logo_admin_position',
				'label'             => trans('admin.img_resize_type_position_label'),
				'type'              => 'select2_from_array',
				'options'           => self::resizePositions(),
				'hint'              => trans('admin.img_resize_type_position_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			[
				'name'              => 'img_resize_logo_admin_relative',
				'label'             => trans('admin.img_resize_type_relative_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_type_relative_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			[
				'name'              => 'img_resize_logo_admin_bgColor',
				'label'             => trans('admin.img_resize_type_bgColor_label'),
				'type'              => 'color_picker',
				'attributes'        => [
					'placeholder' => '#FFFFFF',
				],
				'hint'              => trans('admin.img_resize_type_bg_color_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			*/
			
			// asset.cat
			[
				'name'  => 'img_resize_cat_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.img_resize_cat_sep_value'),
			],
			[
				'name'              => 'img_resize_cat_width',
				'label'             => trans('admin.img_resize_width_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_width_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_cat_height',
				'label'             => trans('admin.img_resize_height_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_height_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_cat_ratio',
				'label'             => trans('admin.img_resize_ratio_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_ratio_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_cat_upsize',
				'label'             => trans('admin.img_resize_upsize_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_upsize_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'  => 'img_resize_type_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.img_resize_type_sep_value'),
			],
			[
				'name'  => 'img_resize_small_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.img_resize_small_sep_value'),
			],
			[
				'name'              => 'img_resize_picture_sm_method',
				'label'             => trans('admin.img_resize_type_resize_method_label'),
				'type'              => 'select2_from_array',
				'options'           => self::resizeMethods(),
				'hint'              => trans('admin.img_resize_type_resize_method_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
				'newline'           => true,
			],
			
			[
				'name'              => 'img_resize_picture_sm_width',
				'label'             => trans('admin.img_resize_type_width_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_type_width_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_picture_sm_height',
				'label'             => trans('admin.img_resize_type_height_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_type_height_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_picture_sm_ratio',
				'label'             => trans('admin.img_resize_type_ratio_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_type_ratio_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_picture_sm_upsize',
				'label'             => trans('admin.img_resize_type_upsize_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_type_upsize_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_picture_sm_position',
				'label'             => trans('admin.img_resize_type_position_label'),
				'type'              => 'select2_from_array',
				'options'           => self::resizePositions(),
				'hint'              => trans('admin.img_resize_type_position_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			[
				'name'              => 'img_resize_picture_sm_relative',
				'label'             => trans('admin.img_resize_type_relative_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_type_relative_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			[
				'name'              => 'img_resize_picture_sm_bgColor',
				'label'             => trans('admin.img_resize_type_bgColor_label'),
				'type'              => 'color_picker',
				'attributes'        => [
					'placeholder' => '#FFFFFF',
				],
				'hint'              => trans('admin.img_resize_type_bg_color_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			[
				'name'  => 'img_resize_medium_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.img_resize_medium_sep_value'),
			],
			[
				'name'              => 'img_resize_picture_md_method',
				'label'             => trans('admin.img_resize_type_resize_method_label'),
				'type'              => 'select2_from_array',
				'options'           => self::resizeMethods(),
				'hint'              => trans('admin.img_resize_type_resize_method_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
				'newline'           => true,
			],
			
			[
				'name'              => 'img_resize_picture_md_width',
				'label'             => trans('admin.img_resize_type_width_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_type_width_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_picture_md_height',
				'label'             => trans('admin.img_resize_type_height_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_type_height_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_picture_md_ratio',
				'label'             => trans('admin.img_resize_type_ratio_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_type_ratio_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_picture_md_upsize',
				'label'             => trans('admin.img_resize_type_upsize_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_type_upsize_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_picture_md_position',
				'label'             => trans('admin.img_resize_type_position_label'),
				'type'              => 'select2_from_array',
				'options'           => self::resizePositions(),
				'hint'              => trans('admin.img_resize_type_position_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			[
				'name'              => 'img_resize_picture_md_relative',
				'label'             => trans('admin.img_resize_type_relative_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_type_relative_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			[
				'name'              => 'img_resize_picture_md_bgColor',
				'label'             => trans('admin.img_resize_type_bgColor_label'),
				'type'              => 'color_picker',
				'attributes'        => [
					'placeholder' => '#FFFFFF',
				],
				'hint'              => trans('admin.img_resize_type_bg_color_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			[
				'name'  => 'img_resize_large_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.img_resize_large_sep_value'),
			],
			[
				'name'              => 'img_resize_picture_lg_method',
				'label'             => trans('admin.img_resize_type_resize_method_label'),
				'type'              => 'select2_from_array',
				'options'           => self::resizeMethods(),
				'hint'              => trans('admin.img_resize_type_resize_method_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
				'newline'           => true,
			],
			
			[
				'name'              => 'img_resize_picture_lg_width',
				'label'             => trans('admin.img_resize_type_width_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_type_width_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_picture_lg_height',
				'label'             => trans('admin.img_resize_type_height_label'),
				'type'              => 'number',
				'hint'              => trans('admin.img_resize_type_height_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_picture_lg_ratio',
				'label'             => trans('admin.img_resize_type_ratio_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_type_ratio_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_picture_lg_upsize',
				'label'             => trans('admin.img_resize_type_upsize_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_type_upsize_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'img_resize_picture_lg_position',
				'label'             => trans('admin.img_resize_type_position_label'),
				'type'              => 'select2_from_array',
				'options'           => self::resizePositions(),
				'hint'              => trans('admin.img_resize_type_position_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			[
				'name'              => 'img_resize_picture_lg_relative',
				'label'             => trans('admin.img_resize_type_relative_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.img_resize_type_relative_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
			[
				'name'              => 'img_resize_picture_lg_bgColor',
				'label'             => trans('admin.img_resize_type_bgColor_label'),
				'type'              => 'color_picker',
				'attributes'        => [
					'placeholder' => '#FFFFFF',
				],
				'hint'              => trans('admin.img_resize_type_bg_color_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4',
				],
			],
		];
		
		if (
			doesUserHavePermission(auth()->user(), 'clear-images-thumbnails')
			|| userHasSuperAdminPermissions()
		) {
			$fields = array_merge($fields, [
				[
					'name'  => 'clear_images_thumbnails_sep',
					'type'  => 'custom_html',
					'value' => trans('admin.clear_images_thumbnails_sep_value'),
				],
				[
					'name'  => 'clear_images_thumbnails_bnt',
					'type'  => 'custom_html',
					'value' => trans('admin.clear_images_thumbnails_btn_value'),
				],
				[
					'name'  => 'clear_images_thumbnails_info',
					'type'  => 'custom_html',
					'value' => trans('admin.clear_images_thumbnails_info_value'),
				],
			]);
		}
		
		return $fields;
	}
	
	/**
	 * @return array
	 */
	private static function resizeMethods(): array
	{
		// Note: This is not Intervention referrers
		$methods = config('larapen.media.resize.methods');
		
		return collect($methods)
			->mapWithKeys(fn ($item) => [$item => ucfirst($item)])
			->toArray();
	}
	
	/**
	 * @return array
	 */
	private static function resizePositions(): array
	{
		// Note: These are Intervention referrers
		$positions = config('larapen.media.resize.positions');
		
		return collect($positions)
			->mapWithKeys(fn ($item) => [$item => str($item)->headline()->toString()])
			->toArray();
	}
}
