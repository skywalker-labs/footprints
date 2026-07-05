<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private function getConnectionName(): ?string
    {
        $conn = config('footprints.connection_name') ?: config('database.default');
        return is_string($conn) ? $conn : null;
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = config('footprints.table_name');
        $tableName = is_string($tableName) ? $tableName : 'visits';

        Schema::connection($this->getConnectionName())->create($tableName, function (Blueprint $table) {

            $table->increments('id');
            $columnName = config('footprints.column_name');
            $columnName = is_string($columnName) ? $columnName : 'user_id';
            $table->integer($columnName)->unsigned()->nullable();
            $table->string('footprint');
            $table->string('ip')->nullable();
            $table->string('landing_domain');
            $table->string('landing_page');
            $table->string('landing_params')->nullable();
            $table->string('referrer_domain')->nullable();
            $table->string('referrer_url')->nullable();
            $table->string('referrer')->nullable();
            $table->string('gclid')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('referral')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();

            $parameters = config('footprints.custom_parameters');
            if (is_array($parameters)) {
                foreach ($parameters as $parameter) {
                    if (is_string($parameter)) {
                        $table->string($parameter)->nullable();
                    }
                }
            }

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
        $tableName = config('footprints.table_name');
        $tableName = is_string($tableName) ? $tableName : 'visits';

        Schema::connection($this->getConnectionName())->drop($tableName);
    }
};
