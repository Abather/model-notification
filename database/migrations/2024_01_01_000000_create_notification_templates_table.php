<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notification_templates', static function (Blueprint $table) {
            $table->id();
            $table->string("model");
            $table->string("key");
            $table->string("lang")->default("ar");
            $table->string("channel");
            $table->text("template");
            $table->boolean("with_file")->default(false);
            $table->json("prob")->nullable();
            $table->timestamps();

            $table->unique(["model","lang", "key", "channel"],'templates_unique_keys');
        });
    }
};
