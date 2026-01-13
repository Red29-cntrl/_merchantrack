<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->default('AL-NES GENERAL MERCHANDISE');
            $table->string('business_type')->nullable();
            $table->string('address')->nullable();
            $table->string('proprietor')->nullable();
            $table->string('vat_reg_tin')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('bir_authority_to_print')->nullable();
            $table->date('atp_date_issued')->nullable();
            $table->date('atp_valid_until')->nullable();
            $table->string('printer_name')->nullable();
            $table->string('printer_tin')->nullable();
            $table->string('printer_accreditation_no')->nullable();
            $table->date('printer_accreditation_date')->nullable();
            $table->text('receipt_footer_note')->nullable();
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
        Schema::dropIfExists('business_settings');
    }
}
