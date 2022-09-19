<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('filename');
            $table->longText('description')->nullable();
            $table->string('extension')->nullable();
            $table->string('mime_type');
            $table->bigInteger('size')->nullable(); // bytes
            $table->string('owner')->nullable();

            // permission related
            $table->boolean('is_private')->nullable()->default(false);
            $table->string('password')->nullable();

            $table->longText('location')->nullable();
            $table->timestamps();


        });

        Schema::table('files', function (Blueprint $table) {
            $table->foreignUuid('parent_id')->nullable()->references('id')->on('files');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
