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
            $table->string('consumer',20)->comment('消费者');
            $table->json('data')->comment('应用明细');   //用于失败后（后置事务）撤销的依据（调用公共撤销事务）
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
