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

use App\Helpers\DBTool;

/*
 * settings.listings_list.option
 */

class ListingsListSetting
{
	public static function getValues($value, $disk)
	{
		if (empty($value)) {
			
			$value['display_browse_jobs_link'] = '1';
			$value['min_salary'] = '0';
			$value['max_salary'] = '10000';
			$value['salary_slider_step'] = '50';
			$value['show_category_icon'] = '7';
			$value['cities_extended_searches'] = '1';
			if (DBTool::isMySqlMinVersion('5.7.6') && !DBTool::isMariaDB()) {
				$value['distance_calculation_formula'] = 'ST_Distance_Sphere';
			} else {
				$value['distance_calculation_formula'] = 'haversine';
			}
			$value['search_distance_max'] = '500';
			$value['search_distance_default'] = '50';
			$value['search_distance_interval'] = '100';
			$value['premium_first'] = '0';
			$value['premium_first_category'] = '1';
			$value['premium_first_location'] = '1';
			$value['free_listings_in_premium'] = '0';
			
		} else {
			
			if (!array_key_exists('display_browse_jobs_link', $value)) {
				$value['display_browse_jobs_link'] = '1';
			}
			if (!array_key_exists('min_salary', $value)) {
				$value['min_salary'] = '0';
			}
			if (!array_key_exists('max_salary', $value)) {
				$value['max_salary'] = '10000';
			}
			if (!array_key_exists('salary_slider_step', $value)) {
				$value['salary_slider_step'] = '50';
			}
			if (!array_key_exists('show_category_icon', $value)) {
				$value['show_category_icon'] = '7';
			}
			if (!array_key_exists('cities_extended_searches', $value)) {
				$value['cities_extended_searches'] = '1';
			}
			if (!array_key_exists('distance_calculation_formula', $value)) {
				if (DBTool::isMySqlMinVersion('5.7.6') && !DBTool::isMariaDB()) {
					$value['distance_calculation_formula'] = 'ST_Distance_Sphere';
				} else {
					$value['distance_calculation_formula'] = 'haversine';
				}
			}
			if (!array_key_exists('search_distance_max', $value)) {
				$value['search_distance_max'] = '500';
			}
			if (!array_key_exists('search_distance_default', $value)) {
				$value['search_distance_default'] = '50';
			}
			if (!array_key_exists('search_distance_interval', $value)) {
				$value['search_distance_interval'] = '100';
			}
			if (!array_key_exists('premium_first', $value)) {
				$value['premium_first'] = '0';
			}
			if (!array_key_exists('premium_first_category', $value)) {
				$value['premium_first_category'] = '1';
			}
			if (!array_key_exists('premium_first_location', $value)) {
				$value['premium_first_location'] = '1';
			}
			if (!array_key_exists('free_listings_in_premium', $value)) {
				$value['free_listings_in_premium'] = '0';
			}
			if (!array_key_exists('date_from_now', $value)) {
				if (array_key_exists('elapsed_time_from_now', $value)) {
					$value['date_from_now'] = $value['elapsed_time_from_now'];
				}
			}
			
		}
		
		return $value;
	}
	
	public static function setValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName)
	{
		$fields = [
			[
				'name'  => 'separator_1',
				'type'  => 'custom_html',
				'value' => trans('admin.list_html_displaying'),
			],
			[
				'name'              => 'display_browse_jobs_link',
				'label'             => trans('admin.browse_jobs_link_in_header_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.browse_jobs_link_in_header_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 mb-4',
				],
			],
			[
				'name'              => 'display_states_search_tip',
				'label'             => trans('admin.display_states_search_tip_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.display_states_search_tip_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 mb-4',
				],
			],
			[
				'name'              => 'fake_locations_results',
				'label'             => trans('admin.fake_locations_results_label'),
				'type'              => 'select2_from_array',
				'options'           => [
					0 => trans('admin.fake_locations_results_op_1'),
					1 => trans('admin.fake_locations_results_op_2'),
					2 => trans('admin.fake_locations_results_op_3'),
				],
				'hint'              => trans('admin.fake_locations_results_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
				'newline'           => true,
			],
			
			[
				'name'              => 'min_salary',
				'label'             => trans('admin.min_salary_label'),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'step' => 1,
				],
				'hint'              => trans('admin.min_salary_hint'),
				'wrapperAttributes' => [
					'class' => 'col-lg-4 col-md-6',
				],
			],
			[
				'name'              => 'max_salary',
				'label'             => trans('admin.max_salary_label'),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 1,
					'step' => 1,
				],
				'hint'              => trans('admin.max_salary_hint'),
				'wrapperAttributes' => [
					'class' => 'col-lg-4 col-md-6',
				],
			],
			[
				'name'              => 'salary_slider_step',
				'label'             => trans('admin.salary_slider_step_label'),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 1,
					'step' => 1,
				],
				'hint'              => trans('admin.salary_slider_step_hint'),
				'wrapperAttributes' => [
					'class' => 'col-lg-4 col-md-6',
				],
			],
			[
				'name'              => 'show_cats_in_top',
				'label'             => trans('admin.show_cats_in_top_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.show_cats_in_top_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'show_category_icon',
				'label'             => trans('admin.show_category_icon_label'),
				'type'              => 'select2_from_array',
				'options'           => [
					1 => trans('admin.show_category_icon_op_1'),
					2 => trans('admin.show_category_icon_op_2'),
					3 => trans('admin.show_category_icon_op_3'),
					4 => trans('admin.show_category_icon_op_4'),
					5 => trans('admin.show_category_icon_op_5'),
					6 => trans('admin.show_category_icon_op_6'),
					7 => trans('admin.show_category_icon_op_7'),
					8 => trans('admin.show_category_icon_op_8'),
				],
				'hint'              => trans('admin.show_category_icon_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'show_listings_tags',
				'label'             => trans('admin.show_listings_tags_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.show_listings_tags_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			
			[
				'name'  => 'count_posts_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.count_posts_title'),
			],
			[
				'name'              => 'count_categories_listings',
				'label'             => trans('admin.count_categories_listings_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.count_categories_listings_hint', ['extendedSearches' => trans('admin.cities_extended_searches_label')]),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'count_cities_listings',
				'label'             => trans('admin.count_cities_listings_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.count_cities_listings_hint', ['extendedSearches' => trans('admin.cities_extended_searches_label')]),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			
			[
				'name'  => 'dates_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.dates_title'),
			],
			[
				'name'              => 'hide_date',
				'label'             => trans('admin.hide_date_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.listing_hide_date_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'  => 'php_specific_date_format',
				'type'  => 'custom_html',
				'value' => trans('admin.php_specific_date_format_info'),
				'wrapperAttributes' => [
					'class' => 'col-md-12 date-field',
				],
			],
			[
				'name'              => 'date_from_now',
				'label'             => trans('admin.date_from_now_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.listing_date_from_now_hint', [
					'app'          => trans('settings.app'),
					'appUrl'       => admin_url('settings/find/app'),
					'languagesUrl' => admin_url('languages'),
				]),
				'wrapperAttributes' => [
					'class' => 'col-md-12 date-field',
				],
			],
			
			[
				'name'  => 'listing_info',
				'type'  => 'custom_html',
				'value' => trans('admin.listing_info_title'),
			],
			[
				'name'  => 'listing_info_description',
				'type'  => 'custom_html',
				'value' => trans('admin.listing_info_description'),
			],
			[
				'name'              => 'hide_post_type',
				'label'             => trans('admin.hide_post_type_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.hide_post_type_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'hide_category',
				'label'             => trans('admin.hide_category_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.hide_category_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'hide_location',
				'label'             => trans('admin.hide_location_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.hide_location_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'hide_salary',
				'label'             => trans('admin.hide_salary_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.hide_salary_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'hide_excerpt',
				'label'             => trans('admin.hide_excerpt_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.hide_excerpt_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			
			[
				'name'  => 'separator_2',
				'type'  => 'custom_html',
				'value' => trans('admin.list_html_distance'),
			],
			[
				'name'              => 'cities_extended_searches',
				'label'             => trans('admin.cities_extended_searches_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.cities_extended_searches_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-12',
				],
			],
			[
				'name'              => 'distance_calculation_formula',
				'label'             => trans('admin.distance_calculation_formula_label'),
				'type'              => 'select2_from_array',
				'options'           => self::distanceCalculationFormula(),
				'hint'              => trans('admin.distance_calculation_formula_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 extended-searches',
				],
			],
			[
				'name'              => 'search_distance_default',
				'label'             => trans('admin.Default Search Distance'),
				'type'              => 'select2_from_array',
				'options'           => [
					200 => '200',
					100 => '100',
					50  => '50',
					25  => '25',
					20  => '20',
					10  => '10',
					0   => '0',
				],
				'hint'              => trans('admin.Default search radius distance'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 extended-searches',
				],
			],
			[
				'name'              => 'separator_3',
				'type'              => 'custom_html',
				'value'             => '<div style="clear: both;"></div>',
				'wrapperAttributes' => [
					'class' => 'col-md-12 extended-searches',
				],
			],
			[
				'name'              => 'search_distance_max',
				'label'             => trans('admin.Max Search Distance'),
				'type'              => 'select2_from_array',
				'options'           => [
					1000 => '1000',
					900  => '900',
					800  => '800',
					700  => '700',
					600  => '600',
					500  => '500',
					400  => '400',
					300  => '300',
					200  => '200',
					100  => '100',
					50   => '50',
					0    => '0',
				],
				'hint'              => trans('admin.Max search radius distance'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 extended-searches',
				],
			],
			[
				'name'              => 'search_distance_interval',
				'label'             => trans('admin.Distance Interval'),
				'type'              => 'select2_from_array',
				'options'           => [
					250 => '250',
					200 => '200',
					100 => '100',
					50  => '50',
					25  => '25',
					20  => '20',
					10  => '10',
					5   => '5',
				],
				'hint'              => trans('admin.The interval between filter distances'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 extended-searches',
				],
			],
			
			[
				'name'  => 'premium_listings',
				'type'  => 'custom_html',
				'value' => trans('admin.premium_listings'),
			],
			[
				'name'  => 'premium_listings_notes',
				'type'  => 'custom_html',
				'value' => trans('admin.premium_listings_notes'),
			],
			[
				'name'  => 'premium_listings_in_searches_title',
				'type'  => 'custom_html',
				'value' => trans('admin.premium_listings_in_searches_title'),
			],
			[
				'name'              => 'premium_first',
				'label'             => trans('admin.premium_first_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.premium_first_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'premium_first_category',
				'label'             => trans('admin.premium_first_category_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.premium_first_category_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'premium_first_location',
				'label'             => trans('admin.premium_first_location_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.premium_first_location_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'  => 'free_listings_in_premium_title',
				'type'  => 'custom_html',
				'value' => trans('admin.free_listings_in_premium_title'),
			],
			[
				'name'              => 'free_listings_in_premium',
				'label'             => trans('admin.free_listings_in_premium_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.free_listings_in_premium_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-12',
				],
			],
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields);
	}
	
	/**
	 * @return array
	 */
	private static function distanceCalculationFormula(): array
	{
		$array = [
			'haversine'  => trans('admin.haversine_formula'),
			'orthodromy' => trans('admin.orthodromy_formula'),
		];
		if (DBTool::isMySqlMinVersion('5.7.6') && !DBTool::isMariaDB()) {
			$array['ST_Distance_Sphere'] = trans('admin.mysql_spherical_calculation');
		}
		
		return $array;
	}
}
