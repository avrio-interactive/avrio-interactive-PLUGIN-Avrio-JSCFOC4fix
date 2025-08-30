<?php

namespace Avrio\Jscfoc4fix\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class InitializePlugin extends Migration
{
    public function up()
    {
        // This plugin doesn't need any database tables
        // This migration exists only to ensure proper plugin initialization
    }

    public function down()
    {
        // Nothing to rollback
    }
}
