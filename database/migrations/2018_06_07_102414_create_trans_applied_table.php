<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransAppliedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_applied', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('tenant_id')->comment('影响的租户');
            $table->unsignedBigInteger('trans_id');
            $table->string('consumer',100)->comment('消费者');
            $table->string('producer',100)->comment('生产者');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trans_applied');
    }
}
