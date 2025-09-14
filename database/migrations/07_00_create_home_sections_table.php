<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up(): void
	{
		Schema::create('home_sections', function (Blueprint $table) {
			$table->increments('id');
			$table->string('method', 100);
			$table->string('name', 100);
			$table->string('view', 200);
			$table->text('field')->nullable();
			$table->text('value')->nullable();
			$table->integer('parent_id')->unsigned()->nullable();
			$table->integer('lft')->unsigned()->nullable();
			$table->integer('rgt')->unsigned()->nullable();
			$table->integer('depth')->unsigned()->nullable();
			$table->boolean('active')->nullable()->default(false);
			
			$table->unique(['method']);
			$table->index(['lft']);
			$table->index(['rgt']);
			$table->index(['active']);
		});
	}
	
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::dropIfExists('home_sections');
	}
};
