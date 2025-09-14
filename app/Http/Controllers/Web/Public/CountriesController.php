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

namespace App\Http\Controllers\Web\Public;

use Larapen\LaravelMetaTags\Facades\MetaTag;

class CountriesController extends FrontController
{
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
    public function index()
    {
        $data = [];
        
        // Meta Tags
		[$title, $description, $keywords] = getMetaTag('countries');
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);

        return appView('countries', $data);
    }
}
