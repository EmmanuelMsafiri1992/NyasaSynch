<?php

use App\Exceptions\Custom\CustomException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ===| DATABASE |===
try {
	
	// settings
	$field = '{"name":"value","label":"Logo","type":"image","upload":"true","disk":"uploads","default":"images/logo@2x.png"}';
	DB::table('settings')->where('key', '=', 'app_logo')->update(['value' => null, 'field' => $field]);
	
	$options = '{"default":"Default","blue":"Blue","yellow":"Yellow","green":"Green","red":"Red"}';
	$field = '{"name":"value","label":"Value","type":"select_from_array","options":' . $options . '}';
	DB::table('settings')->where('key', '=', 'app_theme')->update(['field' => $field]);
	
	$options = '{"smtp":"SMTP","mailgun":"Mailgun","mandrill":"Mandrill","ses":"Amazon SES","mail":"PHP Mail","sendmail":"Sendmail"}';
	$field = '{"name":"value","label":"Value","type":"select_from_array","options":' . $options . '}';
	DB::table('settings')->where('key', '=', 'mail_driver')->update(['field' => $field]);
	
	$allData = [
		[
			'key'         => 'upload_max_file_size',
			'name'        => 'Upload Max File Size',
			'value'       => '2500',
			'description' => 'Upload Max File Size (in KB)',
			'field'       => '{"name":"value","label":"Value","type":"text"}',
			'parent_id'   => 0,
			'lft'         => 25,
			'rgt'         => 25,
			'depth'       => 1,
			'active'      => 1,
			'created_at'  => null,
			'updated_at'  => '2017-01-13 11:21:08',
		],
		[
			'key'         => 'admin_notification',
			'name'        => 'settings_mail_admin_notification_label',
			'value'       => '0',
			'description' => 'settings_mail_admin_notification_hint',
			'field'       => '{"name":"value","label":"Activation","type":"checkbox"}',
			'parent_id'   => 0,
			'lft'         => 26,
			'rgt'         => 33,
			'depth'       => 1,
			'active'      => 1,
			'created_at'  => null,
			'updated_at'  => '2017-01-13 14:38:08',
		],
		[
			'key'         => 'payment_notification',
			'name'        => 'settings_mail_payment_notification_label',
			'value'       => '0',
			'description' => 'settings_mail_payment_notification_hint',
			'field'       => '{"name":"value","label":"Activation","type":"checkbox"}',
			'parent_id'   => 0,
			'lft'         => 26,
			'rgt'         => 33,
			'depth'       => 1,
			'active'      => 1,
			'created_at'  => null,
			'updated_at'  => '2017-01-13 14:38:08',
		],
	];
	foreach ($allData as $item) {
		$key = $item['key'] ?? '';
		
		DB::table('settings')->where('key', $key)->delete();
		
		$setting = \App\Models\Setting::where('key', $key)->first();
		if (empty($setting)) {
			DB::table('settings')->insert($item);
		}
	}
	
	// packs
	if (Schema::hasTable('packs')) {
		if (!Schema::hasColumn('packs', 'short_name') && Schema::hasColumn('packs', 'name')) {
			Schema::table('packs', function (Blueprint $table) {
				$table->string('short_name', 100)->nullable()->comment('In country language')->after('name');
			});
		}
		if (!Schema::hasColumn('packs', 'ribbon') && Schema::hasColumn('packs', 'short_name')) {
			Schema::table('packs', function (Blueprint $table) {
				$table->enum('ribbon', ['red', 'orange', 'green'])->nullable()->after('short_name');
			});
		}
		if (!Schema::hasColumn('packs', 'has_badge') && Schema::hasColumn('packs', 'ribbon')) {
			Schema::table('packs', function (Blueprint $table) {
				$table->boolean('has_badge')->unsigned()->default(0)->after('ribbon');
			});
		}
		if (Schema::hasColumn('packs', 'description')) {
			Schema::table('packs', function (Blueprint $table) {
				$table->string('description', 255)->nullable()->comment('In country language')->change();
			});
		}
		
		DB::table('packs')->where('translation_of', 1)->update(['short_name' => 'FREE', 'ribbon' => null, 'has_badge' => 0]);
		DB::table('packs')->where('translation_of', 2)->update(['short_name' => 'Urgent', 'ribbon' => 'red', 'has_badge' => 0]);
		DB::table('packs')->where('translation_of', 3)->update(['short_name' => 'Premium', 'ribbon' => 'green', 'has_badge' => 1]);
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
