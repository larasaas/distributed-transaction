<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('producer',100)->comment('消息生产者');
            $table->string('consumer',100)->comment('消息消费者');
            $table->unsignedInteger('tenant_id')->comment('发起事务的租户');
            $table->unsignedInteger('user_id')->default(0)->comment('发起事务的用户');
            $table->json('message')->comment('事务消息体');
            $table->tinyInteger('status')->default(0)->comment('事务状态，0未处理，1已执行，2已取消 ');
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
        Schema::dropIfExists('transaction');
    }
}
